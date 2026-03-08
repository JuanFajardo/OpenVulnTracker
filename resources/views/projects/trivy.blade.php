@extends('layouts.app')

@section('title', $project->name . ' — Trivy')
@section('subtitle', 'Container & Filesystem Scan')

@section('header-actions')
    {{-- Branch selector --}}
    @if(count($branches) > 0)
    <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-teal-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
        </svg>
        <form method="GET" action="{{ route('projects.trivy', $project) }}" id="branch-form">
            <select name="branch" onchange="document.getElementById('branch-form').submit()"
                    class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-700 font-mono focus:ring-2 focus:ring-teal-400 focus:outline-none">
                @foreach($branches as $b)
                    <option value="{{ $b }}" {{ $b === $selectedBranch ? 'selected' : '' }}>{{ $b }}</option>
                @endforeach
            </select>
        </form>
    </div>
    @endif

    <a href="{{ route('projects.show', $project) }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $project->name }}
    </a>
@endsection

@section('content')

@if(!$scan)
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center shadow-sm">
        <p class="text-gray-500">No hay escaneos Trivy para la rama <code class="font-mono">{{ $selectedBranch }}</code>.</p>
    </div>
@else

{{-- Branch + scan info bar --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-4 mb-5 flex items-center gap-4 flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
        <span class="font-mono text-sm text-teal-700 bg-teal-100 px-3 py-1.5 rounded-full font-semibold">
            🌿 {{ $selectedBranch }}
        </span>
        @if($scan->artifact_name)
        <span class="font-mono text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
            {{ $scan->artifact_name }}
        </span>
        @endif
        @if($scan->os_family)
        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
            {{ $scan->os_family }}{{ $scan->os_name ? ' '.$scan->os_name : '' }}
        </span>
        @endif
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">
            Escaneo: <strong>{{ $scan->scan_date?->format('d M Y H:i') }}</strong>
        </span>
        @if($scan->uploader)
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">
            Por <strong>{{ $scan->uploader->name }}</strong>
            via <span class="font-medium">{{ $scan->source_label }}</span>
        </span>
        @endif
    </div>
    <div class="ml-auto flex items-center gap-3">
        <a href="{{ route('projects.trivy.show', [$project, $scan]) }}"
           class="text-xs text-gray-500 hover:text-gray-700 underline">
            Ver detalle completo
        </a>
    </div>
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-5 gap-4 mb-5">
    @foreach([
        ['total_critical', 'Critical', 'border-red-100',    'text-red-600'],
        ['total_high',     'High',     'border-orange-100', 'text-orange-500'],
        ['total_medium',   'Medium',   'border-yellow-100', 'text-yellow-600'],
        ['total_low',      'Low',      'border-blue-100',   'text-blue-600'],
        ['total_unknown',  'Unknown',  'border-gray-100',   'text-gray-500'],
    ] as [$field, $label, $border, $text])
    <div class="bg-white rounded-xl border {{ $border }} p-4 shadow-sm text-center">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $text }} mt-1">{{ $scan->$field }}</p>
    </div>
    @endforeach
</div>

{{-- Search filter (server-side, for large datasets) --}}
<div class="mb-4">
    <form method="GET" action="{{ route('projects.trivy', $project) }}" class="flex gap-2">
        <input type="hidden" name="branch" value="{{ $selectedBranch }}">
        <div class="relative flex-1">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="Buscar CVE, paquete..."
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-teal-400 focus:outline-none bg-white">
        </div>
        <button type="submit"
                class="px-4 py-2.5 bg-teal-600 text-white text-sm rounded-lg hover:bg-teal-700 transition-colors">
            Buscar
        </button>
        @if($search)
        <a href="{{ route('projects.trivy', $project) . '?branch=' . urlencode($selectedBranch) }}"
           class="px-4 py-2.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
            Limpiar
        </a>
        @endif
    </form>
</div>

{{-- Package/vulnerability table (grouped by package, SCA-style) --}}
@include('projects._trivy_deps_table', ['vulns' => $vulns])

@endif
@endsection
