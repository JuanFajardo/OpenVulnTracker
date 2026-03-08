<?php

namespace App\Console\Commands;

use App\Models\Dependency;
use App\Models\Project;
use App\Models\Scan;
use App\Models\Vulnerability;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportDependencyCheck extends Command
{
    protected $signature = 'vuln:import
                            {file        : Ruta al reporte JSON de dependency-check}
                            {--force     : Reimportar aunque ya exista un escaneo con la misma fecha}
                            {--project=  : Forzar nombre del proyecto (sobreescribe el del JSON)}
                            {--branch=   : Forzar rama (sobreescribe la detectada del nombre del proyecto)}
                            {--user=     : ID del usuario que realiza la importación}';

    protected $description = 'Importa un reporte JSON de OWASP Dependency-Check a la base de datos';

    public function handle(): int
    {
        ini_set('memory_limit', '512M');
        set_time_limit(0);

        $filePath = $this->argument('file');

        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: {$filePath}");
            return self::FAILURE;
        }

        $fileSize = round(filesize($filePath) / 1024 / 1024, 1);
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

        $fullName     = $report['projectInfo']['name'] ?? basename($filePath, '.json');
        $engineVer    = $report['scanInfo']['engineVersion'] ?? null;
        $dependencies = $report['dependencies'] ?? [];
        unset($report);

        // La fecha del escaneo es el momento de la carga, no la fecha del reporte JSON
        $reportDate = now();

        // Parse project name and branch from "Nombre-Proyecto_RAMA"
        [$projectName, $branch] = Project::parseName($fullName);

        // Allow project/branch override via options
        if ($this->option('project')) {
            $projectName = $this->option('project');
        }
        if ($this->option('branch')) {
            $branch = $this->option('branch');
        }

        $userId = $this->option('user') ? (int)$this->option('user') : null;

        $vulnerable = array_filter($dependencies, fn($d) => !empty($d['vulnerabilities']));
        $this->info(sprintf(
            'Proyecto: %s | Rama: %s | Total: %d | Vulnerables: %d',
            $projectName, $branch, count($dependencies), count($vulnerable)
        ));

        $totalDeps = count($dependencies);

        DB::transaction(function () use (
            $projectName, $branch, $reportDate, $engineVer,
            $filePath, $dependencies, $totalDeps, $userId
        ) {
            $project = Project::firstOrCreate(
                ['name' => $projectName],
                ['name_hash' => hash('sha256', $projectName)]
            );

            $scan = Scan::create([
                'project_id'                    => $project->id,
                'branch'                        => $branch,
                'engine_version'                => $engineVer,
                'report_date'                   => $reportDate,
                'uploaded_at'                   => now(),
                'upload_source'                 => 'cli',
                'uploaded_by'                   => $userId,
                'scan_file_path'                => $filePath,
                'total_dependencies'            => $totalDeps,
            ]);

            $counts = ['CRITICAL' => 0, 'HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0, 'INFO' => 0];
            $totalVulnerableDeps = 0;

            $bar = $this->output->createProgressBar($totalDeps);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
            $bar->setMessage('Iniciando...');
            $bar->start();

            foreach ($dependencies as $dep) {
                $rawVulns = $dep['vulnerabilities'] ?? [];
                if (empty($rawVulns)) {
                    $bar->advance();
                    continue;
                }

                $bar->setMessage(substr($dep['fileName'] ?? '', 0, 40));

                $isTransitive = $this->isTransitive($dep['projectReferences'] ?? []);
                [$pkgId, $pkgName, $pkgVersion, $pkgEco] = $this->parsePackageId($dep);

                $dependency = Dependency::create([
                    'scan_id'            => $scan->id,
                    'file_name'          => $dep['fileName'] ?? '',
                    'file_path'          => $dep['filePath'] ?? null,
                    'is_virtual'         => (bool)($dep['isVirtual'] ?? false),
                    'is_transitive'      => $isTransitive,
                    'package_id'         => $pkgId,
                    'package_name'       => $pkgName,
                    'package_version'    => $pkgVersion,
                    'package_ecosystem'  => $pkgEco,
                    'description'        => $dep['description'] ?? null,
                    'license'            => $dep['license'] ?? null,
                    'md5'                => $dep['md5'] ?? null,
                    'sha1'               => $dep['sha1'] ?? null,
                    'sha256'             => $dep['sha256'] ?? null,
                    'project_references' => !empty($dep['projectReferences']) ? $dep['projectReferences'] : null,
                ]);

                $totalVulnerableDeps++;
                $vulnRows = [];

                foreach ($rawVulns as $vuln) {
                    $severity = $this->normalizeSeverity($vuln['severity'] ?? 'INFO');
                    $cvssv2   = $vuln['cvssv2'] ?? [];
                    $cvssv3   = $vuln['cvssv3'] ?? [];

                    $vulnRows[] = [
                        'dependency_id'                 => $dependency->id,
                        'source'                        => $vuln['source'] ?? null,
                        'name'                          => $vuln['name'] ?? 'UNKNOWN',
                        'severity'                      => $severity,
                        'unscored'                      => ($vuln['unscored'] ?? 'false') === 'true',
                        'cvssv2_score'                  => isset($cvssv2['score']) ? (float)$cvssv2['score'] : null,
                        'cvssv2_severity'               => $cvssv2['severity'] ?? null,
                        'cvssv2_access_vector'          => $cvssv2['accessVector'] ?? null,
                        'cvssv2_access_complexity'      => $cvssv2['accessComplexity'] ?? null,
                        'cvssv2_exploitability_score'   => isset($cvssv2['exploitabilityScore']) ? (float)$cvssv2['exploitabilityScore'] : null,
                        'cvssv2_impact_score'           => isset($cvssv2['impactScore']) ? (float)$cvssv2['impactScore'] : null,
                        'cvssv3_base_score'             => isset($cvssv3['baseScore']) ? (float)$cvssv3['baseScore'] : null,
                        'cvssv3_base_severity'          => $cvssv3['baseSeverity'] ?? null,
                        'cvssv3_attack_vector'          => $cvssv3['attackVector'] ?? null,
                        'cvssv3_attack_complexity'      => $cvssv3['attackComplexity'] ?? null,
                        'cvssv3_privileges_required'    => $cvssv3['privilegesRequired'] ?? null,
                        'cvssv3_user_interaction'       => $cvssv3['userInteraction'] ?? null,
                        'cvssv3_confidentiality_impact' => $cvssv3['confidentialityImpact'] ?? null,
                        'cvssv3_integrity_impact'       => $cvssv3['integrityImpact'] ?? null,
                        'cvssv3_availability_impact'    => $cvssv3['availabilityImpact'] ?? null,
                        'cvssv3_exploitability_score'   => isset($cvssv3['exploitabilityScore']) ? (float)$cvssv3['exploitabilityScore'] : null,
                        'cvssv3_impact_score'           => isset($cvssv3['impactScore']) ? (float)$cvssv3['impactScore'] : null,
                        'cwes'                          => !empty($vuln['cwes']) ? json_encode($vuln['cwes']) : null,
                        'description'                   => $vuln['description'] ?? null,
                        'notes'                         => $vuln['notes'] ?? null,
                        'references'                    => !empty($vuln['references']) ? json_encode($vuln['references']) : null,
                        'vulnerable_software'           => !empty($vuln['vulnerableSoftware']) ? json_encode($vuln['vulnerableSoftware']) : null,
                        'created_at'                    => now(),
                        'updated_at'                    => now(),
                    ];

                    $counts[$severity] = ($counts[$severity] ?? 0) + 1;
                }

                if (!empty($vulnRows)) {
                    Vulnerability::insert($vulnRows);
                }

                $bar->advance();
            }

            $bar->setMessage('¡Completado!');
            $bar->finish();
            $this->newLine();

            $scan->update([
                'total_critical'                => $counts['CRITICAL'],
                'total_high'                    => $counts['HIGH'],
                'total_medium'                  => $counts['MEDIUM'],
                'total_low'                     => $counts['LOW'],
                'total_info'                    => $counts['INFO'],
                'total_vulnerable_dependencies' => $totalVulnerableDeps,
            ]);

            $this->table(
                ['Severidad', 'Cantidad'],
                collect($counts)->map(fn($c, $s) => [$s, $c])->values()->toArray()
            );
        });

        $this->info('Importación completada exitosamente.');
        return self::SUCCESS;
    }

    private function isTransitive(array $refs): bool
    {
        foreach ($refs as $ref) {
            if (stripos((string)$ref, 'transitive') !== false) {
                return true;
            }
        }
        return false;
    }

    private function parsePackageId(array $dep): array
    {
        $pkgId = $pkgName = $pkgVersion = $pkgEco = null;

        if (!empty($dep['packages'][0]['id'])) {
            $pkgId = $dep['packages'][0]['id'];
            if (preg_match('/^pkg:([^\/]+)\/(.+)@(.+)$/', $pkgId, $m)) {
                $pkgEco     = $m[1];
                $pkgName    = $m[2];
                $pkgVersion = $m[3];
            }
        }

        if (!$pkgName && !empty($dep['fileName'])) {
            $fn = $dep['fileName'];
            if (preg_match('/^(.+):(\d[\d.\-\w]*)$/', $fn, $m)) {
                $pkgName    = $m[1];
                $pkgVersion = $m[2];
            } else {
                $pkgName = pathinfo($fn, PATHINFO_FILENAME);
            }
        }

        return [$pkgId, $pkgName, $pkgVersion, $pkgEco];
    }

    private function normalizeSeverity(string $severity): string
    {
        return match(strtoupper($severity)) {
            'MODERATE'      => 'MEDIUM',
            'INFORMATIONAL' => 'INFO',
            default         => strtoupper($severity),
        };
    }
}
