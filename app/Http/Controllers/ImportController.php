<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Scan;
use App\Models\SastScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    public function create()
    {
        return view('import.create');
    }

    public function store(Request $request)
    {
        $isAjax = $request->ajax();

        $validator = Validator::make($request->all(), [
            'report'    => ['required', 'file', 'max:204800'], // 200 MB
            'scan_type' => ['nullable', 'in:auto,sca,trivy,sast'],
        ], [
            'report.required' => 'Selecciona un archivo JSON.',
            'report.file'     => 'El campo debe ser un archivo.',
            'report.max'      => 'El archivo no puede superar los 200 MB.',
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json([
                    'success' => false,
                    'error'   => $validator->errors()->first('report'),
                ], 422);
            }
            return back()->withErrors($validator);
        }

        $file     = $request->file('report');
        $filename = Str::random(40) . '.json';
        $path     = $file->storeAs('reports', $filename, 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            $scanType = $request->input('scan_type', 'auto');
            $type     = ($scanType === 'auto') ? $this->detectScanType($fullPath) : $scanType;
            $command  = match($type) {
                'trivy' => 'trivy:import',
                'sast'  => 'sast:import',
                default => 'vuln:import',
            };

            // El nombre del proyecto y la rama se extraen siempre del nombre
            // del archivo subido (ej. "proyecto-dos_main.json").
            $originalBase = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            [$origProject, $origBranch] = Project::parseName($originalBase);

            $options = [
                'file'      => $fullPath,
                '--user'    => auth()->id(),
                '--project' => $origProject,
                '--branch'  => $origBranch,
            ];

            $exitCode = Artisan::call($command, $options);

            if ($exitCode !== 0) {
                Storage::disk('local')->delete($path);
                $output = strip_tags(Artisan::output());
                if ($isAjax) {
                    return response()->json(['success' => false, 'error' => $output], 422);
                }
                return back()->with('error', 'Error al importar: ' . $output);
            }

            if ($type === 'sca') {
                $this->markLatestScanAsWeb(auth()->id());
            } elseif ($type === 'sast') {
                SastScan::orderByDesc('id')->first()?->update([
                    'upload_source' => 'web',
                    'uploaded_by'   => auth()->id(),
                    'uploaded_at'   => now(),
                ]);
            }

        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            if ($isAjax) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Error: ' . $e->getMessage());
        }

        if ($isAjax) {
            return response()->json([
                'success'  => true,
                'message'  => 'Reporte importado exitosamente.',
                'redirect' => route('projects.index'),
            ]);
        }

        return redirect()->route('projects.index')
            ->with('success', 'Reporte importado exitosamente.');
    }

    private function markLatestScanAsWeb(int $userId): void
    {
        Scan::orderByDesc('id')->first()?->update([
            'upload_source' => 'web',
            'uploaded_by'   => $userId,
            'uploaded_at'   => now(),
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
