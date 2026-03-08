<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\SastFinding;
use App\Models\SastScan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportSast extends Command
{
    protected $signature = 'sast:import
                            {file        : Ruta al reporte JSON de Semgrep}
                            {--project=  : Nombre del proyecto (sobreescribe el detectado del nombre del archivo)}
                            {--branch=   : Forzar rama (sobreescribe la detectada del nombre del archivo)}
                            {--user=     : ID del usuario que realiza la importación}';

    protected $description = 'Importa un reporte JSON de Semgrep (SAST) a la base de datos';

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
        $this->info("Leyendo reporte SAST ({$fileSize} MB): " . basename($filePath));

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

        // Validar que sea un reporte Semgrep (tiene "results" como clave raíz)
        if (!array_key_exists('results', $report)) {
            $this->error('El archivo no parece ser un reporte Semgrep (falta la clave "results").');
            return self::FAILURE;
        }

        // Proyecto y rama vienen del nombre del archivo (o de las opciones --project/--branch)
        // "juice_main.json" → project: "juice", branch: "main"
        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        [$projectName, $branch] = Project::parseName($baseName);

        if ($this->option('project')) {
            $projectName = $this->option('project');
        }
        if ($this->option('branch')) {
            $branch = $this->option('branch');
        }

        $userId          = $this->option('user') ? (int) $this->option('user') : null;
        $semgrepVersion  = $report['version'] ?? null;
        $results         = $report['results'] ?? [];
        $totalFindings   = count($results);

        unset($report);

        $this->info(sprintf(
            'Proyecto: %s | Rama: %s | Versión Semgrep: %s | Hallazgos: %d',
            $projectName, $branch, $semgrepVersion ?? '?', $totalFindings
        ));

        DB::transaction(function () use (
            $projectName, $branch, $semgrepVersion,
            $userId, $results, $totalFindings
        ) {
            $project = Project::firstOrCreate(
                ['name' => $projectName],
                ['name_hash' => hash('sha256', $projectName)]
            );

            $scan = SastScan::create([
                'project_id'      => $project->id,
                'branch'          => $branch,
                'semgrep_version' => $semgrepVersion,
                'scan_date'       => now(),
                'uploaded_at'     => now(),
                'upload_source'   => 'cli',
                'uploaded_by'     => $userId,
            ]);

            $counts = ['ERROR' => 0, 'WARNING' => 0, 'INFO' => 0];

            $bar = $this->output->createProgressBar($totalFindings ?: 1);
            $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
            $bar->setMessage('Procesando...');
            $bar->start();

            $rows = [];
            foreach ($results as $result) {
                $checkId  = $result['check_id'] ?? 'UNKNOWN';
                $filePath = $result['path']      ?? '';
                $extra    = $result['extra']     ?? [];
                $meta     = $extra['metadata']   ?? [];

                // Semgrep severity: ERROR | WARNING | INFO | EXPERIMENT
                // Agrupamos EXPERIMENT con INFO
                $rawSev  = strtoupper($extra['severity'] ?? 'INFO');
                $severity = in_array($rawSev, ['ERROR', 'WARNING', 'INFO']) ? $rawSev : 'INFO';
                $counts[$severity] = ($counts[$severity] ?? 0) + 1;

                $start = $result['start'] ?? [];
                $end   = $result['end']   ?? [];

                $bar->setMessage($checkId);

                $rows[] = [
                    'sast_scan_id'  => $scan->id,
                    'check_id'      => mb_substr($checkId, 0, 500),
                    'file_path'     => mb_substr($filePath, 0, 500),
                    'line_start'    => isset($start['line'])  ? (int) $start['line']  : null,
                    'col_start'     => isset($start['col'])   ? (int) $start['col']   : null,
                    'line_end'      => isset($end['line'])    ? (int) $end['line']    : null,
                    'col_end'       => isset($end['col'])     ? (int) $end['col']     : null,
                    'severity'      => $severity,
                    'confidence'    => isset($meta['confidence']) ? strtoupper(mb_substr($meta['confidence'], 0, 10)) : null,
                    'message'       => $extra['message']  ?? '',
                    'code_snippet'  => isset($extra['lines']) ? mb_substr($extra['lines'], 0, 1000) : null,
                    'category'      => isset($meta['category']) ? mb_substr($meta['category'], 0, 50) : null,
                    'cwe'           => !empty($meta['cwe'])        ? json_encode($meta['cwe'])        : null,
                    'owasp'         => !empty($meta['owasp'])      ? json_encode($meta['owasp'])      : null,
                    'references'    => !empty($meta['references']) ? json_encode($meta['references']) : null,
                    'fix'           => isset($meta['fix'])         ? mb_substr($meta['fix'], 0, 2000) : null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ];

                $bar->advance();

                // Insertar en lotes de 200 para no superar límites de MySQL
                if (count($rows) >= 200) {
                    SastFinding::insert($rows);
                    $rows = [];
                }
            }

            if (!empty($rows)) {
                SastFinding::insert($rows);
            }

            $bar->setMessage('¡Completado!');
            $bar->finish();
            $this->newLine();

            $scan->update([
                'total_error'    => $counts['ERROR'],
                'total_warning'  => $counts['WARNING'],
                'total_info'     => $counts['INFO'],
                'total_findings' => array_sum($counts),
            ]);

            $this->table(
                ['Severidad', 'Cantidad'],
                collect($counts)->map(fn($c, $s) => [$s, $c])->values()->toArray()
            );
        });

        $this->info('Importación SAST completada exitosamente.');
        return self::SUCCESS;
    }
}
