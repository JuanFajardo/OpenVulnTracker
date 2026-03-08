@extends('layouts.app')

@section('title', 'Proyectos')
@section('subtitle', 'Resumen de vulnerabilidades por proyecto y módulo de escaneo')

@section('header-actions')
    <a href="{{ route('import.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
        </svg>
        Importar Reporte
    </a>
@endsection

@section('content')

@if($projects->isEmpty())
    <div class="flex flex-col items-center justify-center py-24 text-center">
        <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-gray-700 mb-1">Sin proyectos</h3>
        <p class="text-gray-500 text-sm mb-6">Importa un reporte JSON para empezar.</p>
        <a href="{{ route('import.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
            Importar primer reporte
        </a>
    </div>
@else

{{-- Top stats --}}
@php
    $totalProjects   = $projects->count();
    $totalCritSca    = $projects->sum(fn($p) => $p->latestScan?->total_critical ?? 0);
    $totalCritTrivy  = $projects->sum(fn($p) => $p->latestTrivyScan?->total_critical ?? 0);
    $totalHighSca    = $projects->sum(fn($p) => $p->latestScan?->total_high ?? 0);
    $totalHighTrivy  = $projects->sum(fn($p) => $p->latestTrivyScan?->total_high ?? 0);
    $totalSastError  = $projects->sum(fn($p) => $p->latestSastScan?->total_error ?? 0);
    $totalSastWarn   = $projects->sum(fn($p) => $p->latestSastScan?->total_warning ?? 0);
    $totalSastInfo   = $projects->sum(fn($p) => $p->latestSastScan?->total_info ?? 0);
@endphp

<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Proyectos</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $totalProjects }}</p>
    </div>
    <div class="bg-white rounded-xl border border-red-100 p-5 shadow-sm">
        <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Critical (SCA + Images)</p>
        <p class="text-3xl font-bold text-red-600 mt-1">{{ number_format($totalCritSca + $totalCritTrivy) }}</p>
        <p class="text-xs text-gray-400 mt-1">SCA: {{ $totalCritSca }} &bull; Trivy: {{ $totalCritTrivy }}</p>
    </div>
    <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm">
        <p class="text-xs font-semibold text-orange-500 uppercase tracking-wide">High (SCA + Images)</p>
        <p class="text-3xl font-bold text-orange-500 mt-1">{{ number_format($totalHighSca + $totalHighTrivy) }}</p>
        <p class="text-xs text-gray-400 mt-1">SCA: {{ $totalHighSca }} &bull; Trivy: {{ $totalHighTrivy }}</p>
    </div>
    <div class="bg-white rounded-xl border border-purple-100 p-5 shadow-sm">
        <p class="text-xs font-semibold text-purple-500 uppercase tracking-wide">SAST (Semgrep)</p>
        <p class="text-3xl font-bold text-purple-600 mt-1">{{ number_format($totalSastError + $totalSastWarn + $totalSastInfo) }}</p>
        <p class="text-xs text-gray-400 mt-1">E: {{ $totalSastError }} &bull; W: {{ $totalSastWarn }} &bull; I: {{ $totalSastInfo }}</p>
    </div>
</div>

{{-- Projects table --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Proyectos escaneados</h2>
        <span class="text-sm text-gray-500">{{ $projects->count() }} proyectos</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left border-b border-gray-100">
                    <th class="px-5 py-3 font-semibold text-gray-600">Proyecto</th>
                    <th class="px-4 py-3 font-semibold text-gray-600">Último Escaneo</th>
                    {{-- SCA --}}
                    <th class="px-4 py-3 font-semibold text-red-600 border-l border-gray-100">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            SCA
                        </div>
                    </th>
                    {{-- Images --}}
                    <th class="px-4 py-3 font-semibold text-teal-600 border-l border-gray-100">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Images
                        </div>
                    </th>
                    {{-- SAST --}}
                    <th class="px-4 py-3 font-semibold text-purple-500 border-l border-gray-100">
                        <div class="flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                            SAST
                        </div>
                    </th>
                    <th class="px-4 py-3 font-semibold text-gray-500 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($projects as $project)
                @php
                    $scan  = $project->latestScan;
                    $trivy = $project->latestTrivyScan;
                    $sast  = $project->latestSastScan;
                    // Most recent date across all scan types
                    $dates    = collect([$scan?->report_date, $trivy?->scan_date, $sast?->scan_date])->filter();
                    $lastDate = $dates->max();
                @endphp
                <tr class="hover:bg-gray-50 transition-colors align-middle">

                    {{-- Project name --}}
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-gray-700 to-gray-900 rounded-lg flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                {{ strtoupper(substr($project->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $project->name }}</p>
                                <p class="text-xs text-gray-400 font-mono">{{ substr($project->name_hash, 0, 12) }}…</p>
                            </div>
                        </div>
                    </td>

                    {{-- Last Scan --}}
                    <td class="px-4 py-4 text-gray-600">
                        @if($lastDate)
                            <span class="text-sm font-medium">{{ $lastDate->format('d M Y') }}</span>
                            <p class="text-xs text-gray-400">{{ $lastDate->diffForHumans() }}</p>
                        @else
                            <span class="text-gray-400 text-sm">—</span>
                        @endif
                    </td>

                    {{-- SCA risk --}}
                    <td class="px-4 py-4 border-l border-gray-50">
                        @if($scan)
                            @include('projects._risk_pills', [
                                'critical' => $scan->total_critical,
                                'high'     => $scan->total_high,
                                'medium'   => $scan->total_medium,
                            ])
                        @else
                            <span class="text-xs text-gray-300 italic">Sin datos</span>
                        @endif
                    </td>

                    {{-- Images (Trivy) risk --}}
                    <td class="px-4 py-4 border-l border-gray-50">
                        @if($trivy)
                            @include('projects._risk_pills', [
                                'critical' => $trivy->total_critical,
                                'high'     => $trivy->total_high,
                                'medium'   => $trivy->total_medium,
                            ])
                        @else
                            <span class="text-xs text-gray-300 italic">Sin datos</span>
                        @endif
                    </td>

                    {{-- SAST risk --}}
                    <td class="px-4 py-4 border-l border-gray-50">
                        @if($sast)
                            <div class="flex flex-wrap gap-1.5">
                                @if($sast->total_error > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-red-100 text-red-700 text-xs font-bold rounded-full">
                                    E: {{ $sast->total_error }}
                                </span>
                                @endif
                                @if($sast->total_warning > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">
                                    W: {{ $sast->total_warning }}
                                </span>
                                @endif
                                @if($sast->total_info > 0)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs font-bold rounded-full">
                                    I: {{ $sast->total_info }}
                                </span>
                                @endif
                                @if($sast->total_findings === 0)
                                <span class="text-xs text-green-600 font-medium">Sin hallazgos</span>
                                @endif
                            </div>
                        @else
                            <span class="text-xs text-gray-300 italic">Sin datos</span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-4 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('projects.show', $project) }}"
                               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-900 hover:bg-gray-700 text-white text-xs font-semibold rounded-lg transition-colors whitespace-nowrap">
                                Ver más
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('projects.destroy', $project) }}"
                                  onsubmit="return confirm('¿Eliminar el proyecto {{ addslashes($project->name) }} y todos sus datos?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors"
                                        title="Eliminar proyecto">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endsection
