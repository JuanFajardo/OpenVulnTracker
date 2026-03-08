<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SastScan;
use Illuminate\Http\Request;

class SastController extends Controller
{
    /**
     * GET /projects/{project}/sast
     * Último escaneo SAST por rama, con selector de rama.
     */
    public function index(Project $project, Request $request)
    {
        $branches       = $project->sastScans()->distinct()->orderBy('branch')->pluck('branch')->toArray();
        $selectedBranch = $request->query('branch', $branches[0] ?? 'main');

        $scan = $project->latestSastScanForBranch($selectedBranch);

        $findings = collect();
        $search   = $request->query('q', '');
        $severity = $request->query('severity', '');

        if ($scan) {
            $query = $scan->findings();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('check_id',   'like', "%{$search}%")
                      ->orWhere('file_path', 'like', "%{$search}%")
                      ->orWhere('message',   'like', "%{$search}%");
                });
            }

            if ($severity) {
                $query->where('severity', strtoupper($severity));
            }

            $findings = $query
                ->orderByRaw("FIELD(severity,'ERROR','WARNING','INFO')")
                ->orderBy('file_path')
                ->orderBy('line_start')
                ->get();
        }

        return view('projects.sast', compact(
            'project', 'branches', 'selectedBranch',
            'scan', 'findings', 'search', 'severity'
        ));
    }

    /**
     * GET /projects/{project}/sast/{sastScan}
     * Vista de detalle de un escaneo SAST histórico.
     */
    public function showScan(Project $project, SastScan $sastScan, Request $request)
    {
        $search   = $request->query('q', '');
        $severity = $request->query('severity', '');

        $query = $sastScan->findings();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('check_id',   'like', "%{$search}%")
                  ->orWhere('file_path', 'like', "%{$search}%")
                  ->orWhere('message',   'like', "%{$search}%");
            });
        }

        if ($severity) {
            $query->where('severity', strtoupper($severity));
        }

        $findings = $query
            ->orderByRaw("FIELD(severity,'ERROR','WARNING','INFO')")
            ->orderBy('file_path')
            ->orderBy('line_start')
            ->get();

        $scans = $project->sastScans()
                         ->where('branch', $sastScan->branch)
                         ->orderByDesc('scan_date')
                         ->get();

        return view('projects.sast_detail', compact(
            'project', 'sastScan', 'findings', 'scans', 'search', 'severity'
        ));
    }

    /**
     * DELETE /projects/{project}/sast/{sastScan}
     */
    public function destroyScan(Project $project, SastScan $sastScan)
    {
        $branch = $sastScan->branch;
        $sastScan->delete();

        $hasSca   = $project->scans()->exists();
        $hasTrivy = $project->trivyScans()->exists();
        $hasSast  = $project->sastScans()->exists();

        if (!$hasSca && !$hasTrivy && !$hasSast) {
            $project->delete();
            return redirect()->route('projects.index')
                ->with('success', 'Escaneo SAST eliminado. Proyecto removido por no tener más escaneos.');
        }

        return redirect()->route('projects.sast', $project)
            ->with('success', "Escaneo SAST de rama '{$branch}' eliminado.");
    }
}
