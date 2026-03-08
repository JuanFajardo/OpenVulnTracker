<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Scan;
use App\Models\SastScan;
use App\Models\TrivyScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImportController extends Controller
{
    /**
     * POST /api/import
     *
     * Uso con curl:
     *
     *   curl -X POST \
     *     -H "Authorization: Bearer vt_xxxx..." \
     *     -F "file=@dependency-check-report.json" \
     *     -F "branch=main" \
     *     http://<host>/OpenAppSec/vulntracker/public/api/import
     */
    public function store(Request $request): JsonResponse
    {
        // Validación manual para garantizar respuesta JSON sin importar el header Accept
        $validator = Validator::make($request->all(), [
            'file'      => ['required', 'file', 'max:204800'], // 200 MB — mime se valida manualmente abajo
            'branch'    => ['nullable', 'string', 'max:100'],
            'scan_type' => ['nullable', 'in:auto,sca,trivy,sast'],
        ], [
            'file.required'      => 'Se requiere el campo "file" con el reporte JSON.',
            'file.file'          => 'El campo "file" debe ser un archivo (usa -F "file=@ruta/archivo.json" en curl).',
            'file.max'           => 'El archivo no puede superar los 200 MB.',
            'scan_type.in'       => 'scan_type debe ser: auto, sca, trivy o sast.',
        ]);

        // Validar extensión manualmente (finfo detecta JSON como text/plain en muchos sistemas)
        if (!$validator->fails() && $request->hasFile('file')) {
            $ext = strtolower($request->file('file')->getClientOriginalExtension());
            if ($ext !== 'json') {
                $validator->after(function ($v) {
                    $v->errors()->add('file', 'El archivo debe tener extensión .json');
                });
            }
        }

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error'   => 'Error de validación.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        /** @var \App\Models\ApiToken $token */
        $token = $request->attributes->get('api_token');

        // storeAs() con .json explícito — store() usa el MIME detectado (text/plain → .txt)
        $filename = \Illuminate\Support\Str::random(40) . '.json';
        $path     = $request->file('file')->storeAs('reports', $filename, 'local');
        // Storage::path() devuelve la ruta con separadores correctos para el SO (Windows/Linux)
        $fullPath = Storage::disk('local')->path($path);

        $scanType = $request->input('scan_type', 'auto');
        $type     = ($scanType === 'auto') ? $this->detectScanType($fullPath) : $scanType;
        $command  = match($type) {
            'trivy' => 'trivy:import',
            'sast'  => 'sast:import',
            default => 'vuln:import',
        };

        try {
            $options = [
                'file'   => $fullPath,
                '--user' => $token->created_by,
            ];

            // Para SAST y Trivy, el nombre del proyecto y la rama se extraen
            // del nombre original del archivo (ej. "juice_main.json").
            // El parámetro --branch de la request puede sobreescribir la rama.
            if (in_array($type, ['sast', 'trivy'])) {
                $originalBase = pathinfo($request->file('file')->getClientOriginalName(), PATHINFO_FILENAME);
                [$origProject, $origBranch] = Project::parseName($originalBase);
                $options['--project'] = $origProject;
                $options['--branch']  = $request->filled('branch') ? $request->input('branch') : $origBranch;
            } elseif ($request->filled('branch')) {
                $options['--branch'] = $request->input('branch');
            }

            $exitCode = Artisan::call($command, $options);
            $output   = trim(Artisan::output());

        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }

        Storage::disk('local')->delete($path);

        if ($exitCode !== 0) {
            return response()->json([
                'success' => false,
                'error'   => 'El proceso de importación terminó con errores.',
                'detail'  => strip_tags($output),
            ], 422);
        }

        // Marcar el escaneo más reciente como subido vía API
        match($type) {
            'trivy' => TrivyScan::orderByDesc('id')->first()?->update([
                'upload_source' => 'api',
                'uploaded_by'   => $token->created_by,
                'uploaded_at'   => now(),
            ]),
            'sast'  => SastScan::orderByDesc('id')->first()?->update([
                'upload_source' => 'api',
                'uploaded_by'   => $token->created_by,
                'uploaded_at'   => now(),
            ]),
            default => Scan::orderByDesc('id')->first()?->update([
                'upload_source' => 'api',
                'uploaded_by'   => $token->created_by,
                'uploaded_at'   => now(),
            ]),
        };

        return response()->json([
            'success' => true,
            'message' => 'Reporte importado exitosamente.',
            'detail'  => strip_tags($output),
        ]);
    }

    /**
     * Detecta el tipo de reporte leyendo los primeros 2048 bytes.
     * Retorna 'trivy', 'sast' o 'sca'.
     */
    private function detectScanType(string $fullPath): string
    {
        $handle = fopen($fullPath, 'r');
        if (!$handle) return 'sca';
        $chunk = fread($handle, 2048);
        fclose($handle);

        // Trivy: siempre tiene SchemaVersion (PascalCase) + Results
        if (str_contains($chunk, '"SchemaVersion"') && str_contains($chunk, '"Results"')) {
            return 'trivy';
        }
        // Semgrep SAST: tiene "check_id" en los resultados, o "results" lowercase sin "projectInfo"
        if (str_contains($chunk, '"check_id"') ||
            (str_contains($chunk, '"results"') && !str_contains($chunk, '"projectInfo"'))) {
            return 'sast';
        }
        return 'sca';
    }
}
