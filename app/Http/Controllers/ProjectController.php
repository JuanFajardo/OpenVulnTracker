<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Scan;
use App\Models\SastScan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    // ── Dashboard ────────────────────────────────────────────────────────────

    public function index()
    {
        $projects = Project::with(['latestScan', 'latestTrivyScan', 'latestSastScan'])->orderBy('name')->get();
        return view('projects.index', compact('projects'));
    }

    // ── Submenu landing ──────────────────────────────────────────────────────

    public function menu(Project $project)
    {
        $branches  = $project->branches();
        $latestScan = $project->latestScan;
        return view('projects.menu', compact('project', 'branches', 'latestScan'));
    }

    // ── Historial ────────────────────────────────────────────────────────────

    public function history(Project $project)
    {
        // Gather SCA scans — toBase() converts Eloquent Collection to plain
        // Support\Collection so map() doesn't try to call getKey() on arrays
        $scaRows = $project->scans()
            ->with('uploader')
            ->get()
            ->toBase()
            ->map(fn($s) => [
                'type'   => 'sca',
                'branch' => $s->branch,
                'date'   => $s->report_date,
                'item'   => $s,
            ]);

        // Gather Trivy scans
        $trivyRows = $project->trivyScans()
            ->with('uploader')
            ->get()
            ->toBase()
            ->map(fn($s) => [
                'type'   => 'trivy',
                'branch' => $s->branch,
                'date'   => $s->scan_date,
                'item'   => $s,
            ]);

        // Gather SAST scans
        $sastRows = $project->sastScans()
            ->with('uploader')
            ->get()
            ->toBase()
            ->map(fn($s) => [
                'type'   => 'sast',
                'branch' => $s->branch,
                'date'   => $s->scan_date,
                'item'   => $s,
            ]);

        // Merge, sort newest-first, then group by branch
        $allByBranch = $scaRows->merge($trivyRows)->merge($sastRows)
            ->sortByDesc('date')
            ->groupBy('branch');

        return view('projects.history', compact('project', 'allByBranch'));
    }

    // ── SCA (latest scan per branch) ─────────────────────────────────────────

    public function sca(Project $project, Request $request)
    {
        $branches      = $project->branches();
        $selectedBranch = $request->query('branch', $branches[0] ?? 'main');

        $scan = $project->latestScanForBranch($selectedBranch);

        if (!$scan) {
            return view('projects.sca', compact('project', 'branches', 'selectedBranch'))
                ->with('scan', null);
        }

        $search = $request->query('q', '');

        $directQuery    = $scan->dependencies()->where('is_transitive', false)
                               ->whereHas('vulnerabilities')
                               ->with(['vulnerabilities.state.user']);
        $transitiveQuery = $scan->dependencies()->where('is_transitive', true)
                                ->whereHas('vulnerabilities')
                                ->with(['vulnerabilities.state.user']);

        if ($search) {
            $filter = function ($q) use ($search) {
                $q->where('package_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhereHas('vulnerabilities', fn($v) => $v->where('name', 'like', "%{$search}%"));
            };
            $directQuery->where($filter);
            $transitiveQuery->where($filter);
        }

        $directDeps     = $directQuery->get();
        $transitiveDeps = $transitiveQuery->get();

        return view('projects.sca', compact(
            'project', 'branches', 'selectedBranch', 'scan',
            'directDeps', 'transitiveDeps', 'search'
        ));
    }

    // ── Detalle de escaneo específico (desde Historial) ──────────────────────

    public function showScan(Project $project, Scan $scan, Request $request)
    {
        $search = $request->query('q', '');

        $directQuery    = $scan->dependencies()->where('is_transitive', false)
                               ->with(['vulnerabilities.state.user']);
        $transitiveQuery = $scan->dependencies()->where('is_transitive', true)
                                ->with(['vulnerabilities.state.user']);

        if ($search) {
            $filter = function ($q) use ($search) {
                $q->where('package_name', 'like', "%{$search}%")
                  ->orWhere('file_name', 'like', "%{$search}%")
                  ->orWhereHas('vulnerabilities', fn($v) => $v->where('name', 'like', "%{$search}%"));
            };
            $directQuery->where($filter);
            $transitiveQuery->where($filter);
        }

        $directDeps     = $directQuery->get();
        $transitiveDeps = $transitiveQuery->get();
        $scans          = $project->scans()->where('branch', $scan->branch)->orderByDesc('report_date')->get();

        return view('projects.scan_detail', compact(
            'project', 'scan', 'directDeps', 'transitiveDeps', 'scans', 'search'
        ));
    }

    // ── Eliminar ─────────────────────────────────────────────────────────────

    public function destroy(Project $project)
    {
        $name = $project->name;
        $project->delete();
        return redirect()->route('projects.index')
            ->with('success', "Proyecto '{$name}' eliminado.");
    }

    public function destroyScan(Project $project, Scan $scan)
    {
        $branch = $scan->branch;
        $scan->delete();

        if ($project->scans()->count() === 0) {
            $project->delete();
            return redirect()->route('projects.index')
                ->with('success', 'Escaneo eliminado. Proyecto removido por no tener más escaneos.');
        }

        return redirect()->route('projects.history', $project)
            ->with('success', "Escaneo de rama '{$branch}' eliminado.");
    }
}
