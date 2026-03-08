@extends('layouts.app')

@section('title', $project->name . ' — Trivy')
@section('subtitle', 'Detalle de escaneo Trivy · ' . $trivyScan->scan_date?->format('d M Y H:i'))

@section('header-actions')
    {{-- Scan selector --}}
    @if($scans->count() > 1)
    <form method="GET" action="" id="scan-form">
        <select name="" onchange="window.location = this.value"
                class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-700 font-mono focus:ring-2 focus:ring-teal-400 focus:outline-none">
            @foreach($scans as $s)
            <option value="{{ route('projects.trivy.show', [$project, $s]) }}"
                    {{ $s->id === $trivyScan->id ? 'selected' : '' }}>
                {{ $s->scan_date?->format('d M Y H:i') }} ({{ $s->branch }})
            </option>
            @endforeach
        </select>
    </form>
    @endif

    <a href="{{ route('projects.trivy', $project) . '?branch=' . urlencode($trivyScan->branch) }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Trivy
    </a>
@endsection

@section('content')

{{-- Scan info bar --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-4 mb-5 flex items-center gap-4 flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
        <span class="font-mono text-sm text-teal-700 bg-teal-100 px-3 py-1.5 rounded-full font-semibold">
            🌿 {{ $trivyScan->branch }}
        </span>
        @if($trivyScan->artifact_name)
        <span class="font-mono text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded-full">
            {{ $trivyScan->artifact_name }}
        </span>
        @endif
        @if($trivyScan->os_family)
        <span class="text-xs text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
            {{ $trivyScan->os_family }}{{ $trivyScan->os_name ? ' '.$trivyScan->os_name : '' }}
        </span>
        @endif
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">
            Escaneo: <strong>{{ $trivyScan->scan_date?->format('d M Y H:i') }}</strong>
        </span>
    </div>
    {{-- Delete button --}}
    <div class="ml-auto">
        <form method="POST"
              action="{{ route('projects.trivy.destroy', [$project, $trivyScan]) }}"
              onsubmit="return confirm('¿Eliminar este escaneo Trivy?')">
            @csrf @method('DELETE')
            <button type="submit"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 border border-red-200 text-red-600 hover:bg-red-50 text-xs rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Eliminar escaneo
            </button>
        </form>
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
        <p class="text-2xl font-bold {{ $text }} mt-1">{{ $trivyScan->$field }}</p>
    </div>
    @endforeach
</div>

{{-- Search --}}
<div class="mb-4">
    <form method="GET" action="{{ route('projects.trivy.show', [$project, $trivyScan]) }}" class="flex gap-2">
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
        <a href="{{ route('projects.trivy.show', [$project, $trivyScan]) }}"
           class="px-4 py-2.5 border border-gray-200 text-gray-600 text-sm rounded-lg hover:bg-gray-50 transition-colors">
            Limpiar
        </a>
        @endif
    </form>
</div>

{{-- Package/vulnerability table (grouped) --}}
@include('projects._trivy_deps_table', ['vulns' => $vulns])

@endsection
