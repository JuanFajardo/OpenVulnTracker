<?php

namespace App\Http\Controllers;

use App\Models\Dependency;
use App\Models\Project;

class DependencyController extends Controller
{
    public function show(Project $project, Dependency $dependency)
    {
        $dependency->load('vulnerabilities', 'scan.project');

        return view('dependencies.show', compact('project', 'dependency'));
    }
}
