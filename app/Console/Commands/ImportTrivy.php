<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\TrivyScan;
use App\Models\TrivyVulnerability;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTrivy extends Command
{
    protected $signature = 'trivy:import
                            {file        : Ruta al reporte JSON de Trivy}
                            {--project=  : Nombre del proyecto (sobreescribe el detectado del nombre del archivo)}
                            {--branch=   : Forzar rama (sobreescribe la detectada del nombre del archivo)}
                            {--user=     : ID del usuario que realiza la importación}';

    protected $description = 'Importa un reporte JSON de Trivy (container/filesystem scan) a la base de datos';

    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return self::FAILURE;
        }

        $fileSize = round(filesize($filePath) / 1024 / 1024, 2);
        $this->info("Leyendo reporte ({$fileSize} MB): " . basename($filePath));

        $content = file_get_contents($filePath);
        if ($content === false) {
            $this->error('No se pudo leer el archivo.');
            return self::FAILURE;
        }

        $report = json_decode($content, true);
        unset($content);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('JSON inválido: ' . json_last_error_msg());
            return self::FAILURE;
        }

        // Validar que sea un reporte Trivy
        if (!isset($report['SchemaVersion']) || !isset($report['Results'])) {
            $this->error('El archivo no parece ser un reporte Trivy (faltan SchemaVersion o Results).');
            return self::FAILURE;
        }

        // Proyecto y rama vienen del nombre del archivo (o de las opciones --project/--branch)
        // "dvna_main.json" → project: "dvna", branch: "main"
        $baseName    = pathinfo($filePath, PATHINFO_FILENAME);
        [$projectName, $branch] = Project::parseName($baseName);

        if ($this->option('project')) {
            $projectName = $this->option('project');
        }
        if ($this->option('branch')) {
            $branch = $this->option('branch');
        }

        $userId = $this->option('user') ? (int) $this->option('user') : null;

        // Metadatos del reporte
        $artifactName  = $report['ArtifactName']  ?? null;
        $artifactType  = $report['ArtifactType']  ?? null;
        $schemaVersion = $report['SchemaVersion'] ?? null;
        $osMeta        = $report['Metadata']['OS'] ?? [];
        $osFamily      = $osMeta['Family'] ?? null;
        $osName        = $osMeta['Name']   ?? null;

        $results = $report['Results'] ?? [];
        unset($report);

        // Contar vulnerabilidades
        $totalVulns = 0;
        foreach ($results as $result) {
            $totalVulns += count($result['Vulnerabilities'] ?? []);
        }

        $this->info(sprintf(
            'Proyecto: %s | Rama: %s | Artefacto: %s | Vulns totales: %d',
            $projectName, $branch, $artifactName ?? '?', $totalVulns
        ));

        DB::transaction(function () use (
            $projectName, $branch, $artifactName, $artifactType,
            $schemaVersion, $osFamily, $osName, $userId,
            $results, $totalVulns
        ) {
            $project = Project::firstOrCreate(
                ['name' => $projectName],
                ['name_hash' => hash('sha256', $projectName)]
            );

            $scan = TrivyScan::create([
                'project_id'     => $project->id,
                'branch'         => $branch,
                'artifact_name'  => $artifactName,
                'artifact_type'  => $artifactType,
                'os_family'      => $osFamily,
                'os_name'        => $osName,
                'schema_version' => $schemaVersion,
                'scan_date'      => now(),
                'uploaded_at'    => now(),
                'upload_source'  => 'cli',
                'uploaded_by'    => $userId,
            ]);

            $counts = [
                'CRITICAL' => 0, 'HIGH' => 0,
                'MEDIUM'   => 0, 'LOW'  => 0, 'UNKNOWN' => 0,
            ];

            $bar = $this->output->createProgressBar($totalVulns ?: 1);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
            $bar->setMessage('Procesando...');
            $bar->start();

            foreach ($results as $result) {
                $target      = $result['Target'] ?? null;
                $targetClass = $result['Class']  ?? null;
                $targetType  = $result['Type']   ?? null;
                $vulns       = $result['Vulnerabilities'] ?? [];

                if (empty($vulns)) {
                    continue;
                }

                $rows = [];
                foreach ($vulns as $v) {
                    $severity = strtoupper($v['Severity'] ?? 'UNKNOWN');
                    $counts[$severity] = ($counts[$severity] ?? 0) + 1;

                    // Elegir la mejor fuente CVSS: nvd > ghsa > primera disponible
                    [$cvssSource, $cvssV2Score, $cvssV2Vector, $cvssV3Score, $cvssV3Vector]
                        = $this->extractCvss($v['CVSS'] ?? []);

                    // Parsear fechas con tolerancia
                    $publishedDate     = $this->safeDate($v['PublishedDate']     ?? null);
                    $lastModifiedDate  = $this->safeDate($v['LastModifiedDate']  ?? null);

                    $bar->setMessage($v['VulnerabilityID'] ?? '');

                    $rows[] = [
                        'trivy_scan_id'      => $scan->id,
                        'target'             => $target,
                        'target_class'       => $targetClass,
                        'target_type'        => $targetType,
                        'pkg_name'           => $v['PkgName']          ?? '',
                        'pkg_version'        => $v['InstalledVersion'] ?? null,
                        'pkg_path'           => $v['PkgPath']          ?? null,
                        'fixed_version'      => $v['FixedVersion']     ?? null,
                        'status'             => $v['Status']           ?? null,
                        'vuln_id'            => $v['VulnerabilityID']  ?? 'UNKNOWN',
                        'severity'           => $severity,
                        'severity_source'    => $v['SeveritySource']   ?? null,
                        'title'              => isset($v['Title']) ? mb_substr($v['Title'], 0, 500) : null,
                        'description'        => $v['Description']      ?? null,
                        'primary_url'        => $v['PrimaryURL']       ?? null,
                        'cvss_source'        => $cvssSource,
                        'cvss_v2_score'      => $cvssV2Score,
                        'cvss_v2_vector'     => $cvssV2Vector,
                        'cvss_v3_score'      => $cvssV3Score,
                        'cvss_v3_vector'     => $cvssV3Vector,
                        'cwes'               => !empty($v['CweIDs']) ? json_encode($v['CweIDs']) : null,
                        'references'         => !empty($v['References']) ? json_encode($v['References']) : null,
                        'published_date'     => $publishedDate,
                        'last_modified_date' => $lastModifiedDate,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ];

                    $bar->advance();
                }

                if (!empty($rows)) {
                    // Insertar en lotes de 200 para no sobrepasar límites de MySQL
                    foreach (array_chunk($rows, 200) as $chunk) {
                        TrivyVulnerability::insert($chunk);
                    }
                }
            }

            $bar->setMessage('¡Completado!');
            $bar->finish();
            $this->newLine();

            $scan->update([
                'total_critical'       => $counts['CRITICAL'],
                'total_high'           => $counts['HIGH'],
                'total_medium'         => $counts['MEDIUM'],
                'total_low'            => $counts['LOW'],
                'total_unknown'        => $counts['UNKNOWN'] ?? 0,
                'total_vulnerabilities' => array_sum($counts),
            ]);

            $this->table(
                ['Severidad', 'Cantidad'],
                collect($counts)->map(fn($c, $s) => [$s, $c])->values()->toArray()
            );
        });

        $this->info('Importación Trivy completada exitosamente.');
        return self::SUCCESS;
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Extrae el mejor CVSS disponible del mapa de fuentes.
     * Prioridad: nvd > ghsa > primera fuente disponible.
     */
    private function extractCvss(array $cvssMap): array
    {
        if (empty($cvssMap)) {
            return [null, null, null, null, null];
        }

        $preferred = ['nvd', 'ghsa'];
        $source    = null;
        $data      = null;

        foreach ($preferred as $pref) {
            if (isset($cvssMap[$pref])) {
                $source = $pref;
                $data   = $cvssMap[$pref];
                break;
            }
        }

        if ($data === null) {
            $source = array_key_first($cvssMap);
            $data   = $cvssMap[$source];
        }

        return [
            $source,
            isset($data['V2Score'])  ? (float) $data['V2Score']  : null,
            $data['V2Vector'] ?? null,
            isset($data['V3Score'])  ? (float) $data['V3Score']  : null,
            $data['V3Vector'] ?? null,
        ];
    }

    /** Parsea una fecha ISO 8601 de Trivy de forma segura. */
    private function safeDate(?string $date): ?string
    {
        if (!$date) return null;
        try {
            return \Carbon\Carbon::parse($date)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }
}
