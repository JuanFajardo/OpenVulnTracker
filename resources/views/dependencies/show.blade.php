@extends('layouts.app')

@section('title', $dependency->package_name ?: $dependency->file_name)
@section('subtitle', $dependency->vulnerabilities->count() . ' vulnerabilidades encontradas · Proyecto: ' . $project->name)

@section('header-actions')
    <a href="{{ route('projects.show', $project) }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver a {{ $project->name }}
    </a>
@endsection

@section('content')

@php
    $severityColors = [
        'CRITICAL' => ['badge' => 'bg-red-600 text-white',    'border' => 'border-red-200',    'bg' => 'bg-red-50',    'text' => 'text-red-700',    'bar' => 'bg-red-500'],
        'HIGH'     => ['badge' => 'bg-orange-500 text-white', 'border' => 'border-orange-200', 'bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'bar' => 'bg-orange-500'],
        'MEDIUM'   => ['badge' => 'bg-yellow-500 text-white', 'border' => 'border-yellow-200', 'bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'bar' => 'bg-yellow-400'],
        'LOW'      => ['badge' => 'bg-blue-500 text-white',   'border' => 'border-blue-200',   'bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'bar' => 'bg-blue-400'],
        'INFO'     => ['badge' => 'bg-gray-400 text-white',   'border' => 'border-gray-200',   'bg' => 'bg-gray-50',   'text' => 'text-gray-600',   'bar' => 'bg-gray-300'],
    ];
@endphp

{{-- Library info --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-6">
    <div class="flex items-start justify-between gap-6 flex-wrap">
        <div class="flex-1 min-w-0">
            <div class="flex items-center flex-wrap gap-3 mb-3">
                <h2 class="text-2xl font-bold text-gray-900 break-all">
                    {{ $dependency->package_name ?: $dependency->file_name }}
                </h2>
                @if($dependency->package_version)
                    <span class="font-mono text-sm bg-gray-100 text-gray-700 px-3 py-1 rounded-full border border-gray-200">
                        v{{ $dependency->package_version }}
                    </span>
                @endif
                @if($dependency->is_transitive)
                    <span class="text-xs bg-purple-100 text-purple-700 border border-purple-200 px-2.5 py-1 rounded-full font-semibold">Transitiva</span>
                @else
                    <span class="text-xs bg-emerald-100 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full font-semibold">Directa</span>
                @endif
                @if($dependency->package_ecosystem)
                    <span class="text-xs bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full font-medium uppercase tracking-wide">
                        {{ $dependency->package_ecosystem }}
                    </span>
                @endif
            </div>

            @if($dependency->description)
                <p class="text-gray-600 leading-relaxed mb-4">{{ $dependency->description }}</p>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                @if($dependency->package_id)
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 font-medium w-24 flex-shrink-0">Package ID:</span>
                    <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded break-all">{{ $dependency->package_id }}</code>
                </div>
                @endif
                @if($dependency->license)
                <div class="flex items-center gap-2">
                    <span class="text-gray-500 font-medium w-24 flex-shrink-0">Licencia:</span>
                    <span class="text-gray-700">{{ $dependency->license }}</span>
                </div>
                @endif
                @if($dependency->file_path)
                <div class="flex items-start gap-2 sm:col-span-2">
                    <span class="text-gray-500 font-medium w-24 flex-shrink-0">Ruta:</span>
                    <code class="text-xs text-gray-500 break-all">{{ $dependency->file_path }}</code>
                </div>
                @endif
            </div>
        </div>

        {{-- Total vuln counter --}}
        <div class="text-center flex-shrink-0 bg-gray-50 rounded-xl border border-gray-200 px-8 py-4">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Vulnerabilidades</p>
            <p class="text-5xl font-black text-gray-900">{{ $dependency->vulnerabilities->count() }}</p>
            <p class="text-xs text-gray-400 mt-1">en este componente</p>
        </div>
    </div>

    {{-- Hashes --}}
    @if($dependency->sha256 || $dependency->sha1 || $dependency->md5)
    <div class="mt-5 pt-5 border-t border-gray-100 grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach(['sha256' => 'SHA-256', 'sha1' => 'SHA-1', 'md5' => 'MD5'] as $field => $label)
            @if($dependency->$field)
            <div>
                <p class="text-xs text-gray-400 font-medium mb-1">{{ $label }}</p>
                <code class="text-xs font-mono text-gray-600 break-all">{{ $dependency->$field }}</code>
            </div>
            @endif
        @endforeach
    </div>
    @endif
</div>

{{-- Severity summary bar --}}
@php
    $bySeverity = $dependency->vulnerabilities->groupBy('severity');
    $order = ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO'];
    $total = $dependency->vulnerabilities->count();
@endphp
<div class="grid grid-cols-5 gap-3 mb-6">
    @foreach($order as $sev)
    @php
        $count = $bySeverity->get($sev, collect())->count();
        $c = $severityColors[$sev];
        $pct = $total > 0 ? round($count / $total * 100) : 0;
    @endphp
    <div class="bg-white rounded-xl border {{ $c['border'] }} p-4 shadow-sm">
        <div class="flex items-center justify-between mb-2">
            <span class="inline-flex px-2.5 py-1 {{ $c['badge'] }} text-xs font-bold rounded-full">{{ $sev }}</span>
            <span class="text-2xl font-bold {{ $c['text'] }}">{{ $count }}</span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-1.5">
            <div class="{{ $c['bar'] }} h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>
    </div>
    @endforeach
</div>

{{-- Vulnerabilities accordion --}}
<div class="space-y-3">
    <h3 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">
        Detalle de Vulnerabilidades
    </h3>

    @foreach($dependency->vulnerabilities->sortByDesc(fn($v) => match(strtoupper($v->severity ?? '')) {
        'CRITICAL' => 4, 'HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1, default => 0
    }) as $vuln)
    @php
        $sev = strtoupper($vuln->severity ?? 'INFO');
        $c = $severityColors[$sev] ?? $severityColors['INFO'];
    @endphp

    <div class="bg-white rounded-xl border {{ $c['border'] }} shadow-sm overflow-hidden"
         x-data="{ open: '{{ $sev }}' === 'CRITICAL' || '{{ $sev }}' === 'HIGH' }">

        {{-- Header (always visible) --}}
        <button @click="open = !open"
                class="w-full text-left px-5 py-4 flex items-center justify-between gap-4 hover:bg-gray-50/80 transition-colors">
            <div class="flex items-center gap-3 flex-1 min-w-0 flex-wrap">
                <span class="inline-flex items-center px-3 py-1.5 {{ $c['badge'] }} text-xs font-bold rounded-lg flex-shrink-0">
                    {{ $sev }}
                </span>
                <p class="font-bold text-gray-900 font-mono text-sm">{{ $vuln->name }}</p>
                @if($vuln->source)
                    <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded">{{ $vuln->source }}</span>
                @endif
                @if($vuln->unscored)
                    <span class="text-xs text-amber-600 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded">Unscored</span>
                @endif
                {{-- CVSS score pill --}}
                @if($vuln->cvssv3_base_score)
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg px-2.5 py-1">
                        <span class="text-xs text-gray-500">CVSSv3</span>
                        <span class="font-bold text-gray-900 text-sm">{{ number_format($vuln->cvssv3_base_score, 1) }}</span>
                    </div>
                @elseif($vuln->cvssv2_score)
                    <div class="flex items-center gap-1 bg-gray-100 rounded-lg px-2.5 py-1">
                        <span class="text-xs text-gray-500">CVSSv2</span>
                        <span class="font-bold text-gray-900 text-sm">{{ number_format($vuln->cvssv2_score, 1) }}</span>
                    </div>
                @endif
                {{-- CWEs --}}
                @if($vuln->cwes)
                    @foreach($vuln->cwes as $cwe)
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded font-mono">{{ $cwe }}</span>
                    @endforeach
                @endif
            </div>
            <svg :class="open ? 'rotate-180' : ''"
                 class="w-5 h-5 text-gray-400 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        {{-- Body --}}
        <div x-show="open" x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
             class="{{ $c['bg'] }} border-t {{ $c['border'] }}">
            <div class="px-5 py-5 space-y-5">

                {{-- Description --}}
                @if($vuln->description)
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Descripción</h4>
                    <div class="bg-white rounded-lg border border-gray-100 p-4">
                        <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $vuln->description }}</p>
                    </div>
                </div>
                @endif

                {{-- CVSS --}}
                @if($vuln->cvssv3_base_score || $vuln->cvssv2_score)
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">Métricas CVSS</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                        @if($vuln->cvssv3_base_score)
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="font-bold text-gray-800">CVSS v3</p>
                                    <p class="text-xs text-gray-400">{{ $vuln->cvssv3_base_severity }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-3xl font-black {{ $c['text'] }}">{{ number_format($vuln->cvssv3_base_score, 1) }}</span>
                                    <span class="text-sm text-gray-400"> / 10</span>
                                </div>
                            </div>
                            {{-- Score bar --}}
                            <div class="w-full bg-gray-100 rounded-full h-2 mb-4">
                                <div class="{{ $c['bar'] }} h-2 rounded-full"
                                     style="width: {{ ($vuln->cvssv3_base_score / 10) * 100 }}%"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                @foreach([
                                    'Attack Vector'    => $vuln->cvssv3_attack_vector,
                                    'Complexity'       => $vuln->cvssv3_attack_complexity,
                                    'Privileges Req.'  => $vuln->cvssv3_privileges_required,
                                    'User Interaction' => $vuln->cvssv3_user_interaction,
                                    'Confidentiality'  => $vuln->cvssv3_confidentiality_impact,
                                    'Integrity'        => $vuln->cvssv3_integrity_impact,
                                    'Availability'     => $vuln->cvssv3_availability_impact,
                                    'Exploitability'   => $vuln->cvssv3_exploitability_score,
                                    'Impact Score'     => $vuln->cvssv3_impact_score,
                                ] as $label => $value)
                                    @if($value)
                                    <div>
                                        <span class="text-gray-400">{{ $label }}:</span>
                                        <span class="text-gray-700 font-medium ml-1">{{ $value }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif

                        @if($vuln->cvssv2_score)
                        <div class="bg-white rounded-lg border border-gray-200 p-4">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="font-bold text-gray-800">CVSS v2</p>
                                    <p class="text-xs text-gray-400">{{ $vuln->cvssv2_severity }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="text-3xl font-black text-gray-700">{{ number_format($vuln->cvssv2_score, 1) }}</span>
                                    <span class="text-sm text-gray-400"> / 10</span>
                                </div>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2 mb-4">
                                <div class="bg-gray-400 h-2 rounded-full"
                                     style="width: {{ ($vuln->cvssv2_score / 10) * 100 }}%"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                                @foreach([
                                    'Access Vector'    => $vuln->cvssv2_access_vector,
                                    'Complexity'       => $vuln->cvssv2_access_complexity,
                                    'Exploitability'   => $vuln->cvssv2_exploitability_score,
                                    'Impact Score'     => $vuln->cvssv2_impact_score,
                                ] as $label => $value)
                                    @if($value)
                                    <div>
                                        <span class="text-gray-400">{{ $label }}:</span>
                                        <span class="text-gray-700 font-medium ml-1">{{ $value }}</span>
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Affected versions --}}
                @if($vuln->vulnerable_software && count($vuln->vulnerable_software) > 0)
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Versiones Afectadas</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($vuln->vulnerable_software as $vs)
                            @php $sw = $vs['software'] ?? []; @endphp
                            <div class="bg-white rounded-lg border border-gray-200 px-3 py-2 text-xs">
                                <code class="text-gray-600">{{ $sw['id'] ?? '' }}</code>
                                @if(isset($sw['versionEndExcluding']))
                                    <span class="text-red-600 font-semibold ml-1">&lt; {{ $sw['versionEndExcluding'] }}</span>
                                @endif
                                @if(isset($sw['versionEndIncluding']))
                                    <span class="text-red-600 font-semibold ml-1">&le; {{ $sw['versionEndIncluding'] }}</span>
                                @endif
                                @if(isset($sw['versionStartIncluding']))
                                    <span class="text-gray-500 ml-1">&ge; {{ $sw['versionStartIncluding'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- References --}}
                @if($vuln->references && count($vuln->references) > 0)
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Referencias</h4>
                    <div class="bg-white rounded-lg border border-gray-100 divide-y divide-gray-50">
                        @foreach(array_filter($vuln->references, fn($r) => !empty($r['url'])) as $ref)
                        <div class="flex items-start gap-2.5 px-4 py-2.5">
                            <svg class="w-3.5 h-3.5 text-blue-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            <a href="{{ $ref['url'] }}" target="_blank" rel="noopener noreferrer"
                               class="text-xs text-blue-600 hover:text-blue-800 hover:underline break-all">
                                {{ $ref['name'] ?? $ref['url'] }}
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @if($vuln->notes)
                <div>
                    <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Notas</h4>
                    <p class="text-sm text-gray-600 bg-white rounded-lg border border-gray-100 px-4 py-3">{{ $vuln->notes }}</p>
                </div>
                @endif

            </div>
        </div>
    </div>
    @endforeach
</div>

@endsection
