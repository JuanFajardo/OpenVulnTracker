@extends('layouts.app')

@section('title', $project->name . ' — Escaneo del ' . ($scan->report_date?->format('d M Y') ?? '—'))
@section('subtitle', 'Detalle de escaneo histórico · Rama: ' . $scan->branch)

@section('header-actions')
    <a href="{{ route('projects.history', $project) }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Historial
    </a>
    <a href="{{ route('projects.show', $project) }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $project->name }}
    </a>
@endsection

@section('content')

{{-- Scan info bar --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-4 mb-5 flex items-center gap-4 flex-wrap">
    <div class="flex items-center gap-2">
        <span class="font-mono text-sm text-purple-700 bg-purple-100 px-3 py-1.5 rounded-full font-semibold">
            🌿 {{ $scan->branch }}
        </span>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">
            Escaneo:
            <strong>{{ $scan->report_date?->format('d M Y H:i') }}</strong>
        </span>
        <span class="text-gray-300">|</span>
        <span class="text-sm text-gray-600">
            Cargado: <strong>{{ $scan->uploaded_at?->format('d M Y H:i') ?? $scan->created_at->format('d M Y H:i') }}</strong>
            via
            <span class="font-medium">{{ $scan->source_label }}</span>
            @if($scan->uploader)
                por <strong>{{ $scan->uploader->name }}</strong>
            @endif
        </span>
    </div>

    {{-- Other scans for this branch (navigation) --}}
    @if($scans->count() > 1)
    <div class="ml-auto flex items-center gap-2">
        <span class="text-xs text-gray-400">Otros escaneos de esta rama:</span>
        <div class="flex gap-1 flex-wrap">
            @foreach($scans as $s)
            @if($s->id !== $scan->id)
            <a href="{{ route('projects.scans.show', [$project, $s]) }}"
               class="text-xs px-2.5 py-1 border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors whitespace-nowrap">
                {{ $s->report_date?->format('d M Y') }}
            </a>
            @endif
            @endforeach
        </div>
    </div>
    @endif
</div>

{{-- Summary cards --}}
<div class="grid grid-cols-5 gap-4 mb-5">
    @foreach([
        ['total_critical', 'Critical', 'border-red-100',    'text-red-600'],
        ['total_high',     'High',     'border-orange-100', 'text-orange-500'],
        ['total_medium',   'Medium',   'border-yellow-100', 'text-yellow-600'],
        ['total_low',      'Low',      'border-blue-100',   'text-blue-600'],
        ['total_info',     'Info',     'border-gray-100',   'text-gray-500'],
    ] as [$field, $label, $border, $text])
    <div class="bg-white rounded-xl border {{ $border }} p-4 shadow-sm text-center">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $text }} mt-1">{{ $scan->$field }}</p>
    </div>
    @endforeach
</div>

{{-- Stats secondary --}}
<div class="grid grid-cols-2 gap-4 mb-5">
    <div class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Total Dependencias</p>
            <p class="text-xl font-bold text-gray-900">{{ number_format($scan->total_dependencies) }}</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-red-100 p-4 shadow-sm flex items-center gap-4">
        <div class="w-10 h-10 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">Deps. Vulnerables</p>
            <p class="text-xl font-bold text-red-600">{{ number_format($scan->total_vulnerable_dependencies) }}</p>
        </div>
    </div>
</div>

{{-- Search --}}
<div class="mb-4">
    <form method="GET" action="{{ route('projects.scans.show', [$project, $scan]) }}">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="Buscar librería o CVE/GHSA..."
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-purple-400 focus:outline-none bg-white"
                   @input.debounce.400ms="$el.form.submit()">
        </div>
    </form>
</div>

{{-- Tabs direct / transitive --}}
@php
    $returnUrl = route('projects.scans.show', [$project, $scan]) . ($search ? '?q='.urlencode($search) : '');
@endphp

<div x-data="{ tab: 'direct' }">
    <div class="flex border-b border-gray-200 mb-5 gap-1">
        @foreach(['direct' => ['Directas', $directDeps->count()], 'transitive' => ['Transitivas', $transitiveDeps->count()]] as $key => [$label, $count])
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'border-b-2 border-purple-600 text-purple-700 bg-purple-50' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition-colors -mb-px flex items-center gap-2">
            Dependencias {{ $label }}
            <span class="text-xs px-2 py-0.5 rounded-full"
                  :class="tab === '{{ $key }}' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'">
                {{ $count }}
            </span>
        </button>
        @endforeach
    </div>

    <div x-show="tab === 'direct'" x-transition>
        @include('projects._sca_table', ['deps' => $directDeps, 'returnUrl' => $returnUrl])
    </div>
    <div x-show="tab === 'transitive'" x-cloak x-transition>
        @include('projects._sca_table', ['deps' => $transitiveDeps, 'returnUrl' => $returnUrl])
    </div>
</div>

@endsection
