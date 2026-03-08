<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\TrivyScan;
use Illuminate\Http\Request;

class TrivyController extends Controller
{
    /**
     * GET /projects/{project}/trivy
     * Latest Trivy scan per branch, with branch selector.
     */
    public function index(Project $project, Request $request)
    {
        $branches       = $project->trivyScans()->distinct()->orderBy('branch')->pluck('branch')->toArray();
        $selectedBranch = $request->query('branch', $branches[0] ?? 'main');

        $scan = $project->latestTrivyScanForBranch($selectedBranch);

        $vulns      = collect();
        $targets    = collect();
        $search     = $request->query('q', '');
        $severity   = $request->query('severity', '');

        if ($scan) {
            $query = $scan->vulnerabilities();

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('vuln_id',  'like', "%{$search}%")
                      ->orWhere('pkg_name', 'like', "%{$search}%")
                      ->orWhere('title',    'like', "%{$search}%");
                });
            }

            if ($severity) {
                $query->where('severity', strtoupper($severity));
            }

            $vulns   = $query->orderByRaw("FIELD(severity,'CRITICAL','HIGH','MEDIUM','LOW','UNKNOWN')")
                             ->orderBy('pkg_name')
                             ->get();

            $targets = $scan->vulnerabilities()->distinct()->orderBy('target')->pluck('target');
        }

        return view('projects.trivy', compact(
            'project', 'branches', 'selectedBranch',
            'scan', 'vulns', 'targets', 'search', 'severity'
        ));
    }

    /**
     * GET /projects/{project}/trivy/{trivyScan}
     * Detail view for a specific historical Trivy scan.
     */
    public function showScan(Project $project, TrivyScan $trivyScan, Request $request)
    {
        $search   = $request->query('q', '');
        $severity = $request->query('severity', '');

        $query = $trivyScan->vulnerabilities();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('vuln_id',  'like', "%{$search}%")
                  ->orWhere('pkg_name', 'like', "%{$search}%")
                  ->orWhere('title',    'like', "%{$search}%");
            });
        }

        if ($severity) {
            $query->where('severity', strtoupper($severity));
        }

        $vulns   = $query->orderByRaw("FIELD(severity,'CRITICAL','HIGH','MEDIUM','LOW','UNKNOWN')")
                         ->orderBy('pkg_name')
                         ->get();

        $targets = $trivyScan->vulnerabilities()->distinct()->orderBy('target')->pluck('target');

        $scans = $project->trivyScans()
                         ->where('branch', $trivyScan->branch)
                         ->orderByDesc('scan_date')
                         ->get();

        return view('projects.trivy_detail', compact(
            'project', 'trivyScan', 'vulns', 'targets', 'scans', 'search', 'severity'
        ));
    }

    /**
     * DELETE /projects/{project}/trivy/{trivyScan}
     */
    public function destroyScan(Project $project, TrivyScan $trivyScan)
    {
        $branch = $trivyScan->branch;
        $trivyScan->delete();

        // If the project has no scans of any kind, remove it
        $hasSca   = $project->scans()->exists();
        $hasTrivy = $project->trivyScans()->exists();
        $hasSast  = $project->sastScans()->exists();

        if (!$hasSca && !$hasTrivy && !$hasSast) {
            $project->delete();
            return redirect()->route('projects.index')
                ->with('success', 'Escaneo Trivy eliminado. Proyecto removido por no tener más escaneos.');
        }

        return redirect()->route('projects.trivy', $project)
            ->with('success', "Escaneo Trivy de rama '{$branch}' eliminado.");
    }
}
