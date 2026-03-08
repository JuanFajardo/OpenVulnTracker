@extends('layouts.app')

@section('title', $project->name)
@section('subtitle', 'Escaneado el ' . ($scan->report_date?->format('d M Y H:i') ?? 'fecha desconocida') . ' · Engine v' . $scan->engine_version)

@section('header-actions')
    {{-- Scan history selector --}}
    @if($scans->count() > 1)
    <form method="GET" action="{{ route('projects.show', $project) }}" class="flex items-center gap-2">
        <label class="text-xs text-gray-500">Scan:</label>
        <select name="scan_id" onchange="this.form.submit()"
                class="text-sm border border-gray-200 rounded-lg px-3 py-2 bg-white text-gray-700 focus:ring-2 focus:ring-red-500 focus:outline-none">
            @foreach($scans as $s)
            <option value="{{ $s->id }}" {{ $s->id === $scan->id ? 'selected' : '' }}>
                {{ $s->report_date?->format('d M Y H:i') }}
            </option>
            @endforeach
        </select>
    </form>
    @endif

    {{-- Delete scan --}}
    <form method="POST" action="{{ route('projects.scans.destroy', [$project, $scan]) }}"
          onsubmit="return confirm('¿Eliminar este escaneo? Esta acción no se puede deshacer.')">
        @csrf @method('DELETE')
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-3 py-2 border border-red-200 text-red-600 hover:bg-red-50 text-sm rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Eliminar escaneo
        </button>
    </form>

    <a href="{{ route('projects.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')

{{-- Summary cards --}}
<div class="grid grid-cols-6 gap-4 mb-6">
    <div class="col-span-2 bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">SHA-256 del Proyecto</p>
        <p class="text-xs font-mono text-gray-600 break-all leading-relaxed">{{ $project->name_hash }}</p>
        <div class="mt-3 flex gap-3 text-xs text-gray-500">
            <span><span class="font-semibold text-gray-700">{{ number_format($scan->total_dependencies) }}</span> dependencias totales</span>
            <span><span class="font-semibold text-gray-700">{{ number_format($scan->total_vulnerable_dependencies) }}</span> vulnerables</span>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-red-100 p-5 shadow-sm text-center">
        <p class="text-xs font-semibold text-red-500 uppercase tracking-wide">Critical</p>
        <p class="text-3xl font-bold text-red-600 mt-1">{{ $scan->total_critical }}</p>
    </div>
    <div class="bg-white rounded-xl border border-orange-100 p-5 shadow-sm text-center">
        <p class="text-xs font-semibold text-orange-500 uppercase tracking-wide">High</p>
        <p class="text-3xl font-bold text-orange-500 mt-1">{{ $scan->total_high }}</p>
    </div>
    <div class="bg-white rounded-xl border border-yellow-100 p-5 shadow-sm text-center">
        <p class="text-xs font-semibold text-yellow-600 uppercase tracking-wide">Medium</p>
        <p class="text-3xl font-bold text-yellow-600 mt-1">{{ $scan->total_medium }}</p>
    </div>
    <div class="bg-white rounded-xl border border-blue-100 p-5 shadow-sm text-center">
        <p class="text-xs font-semibold text-blue-500 uppercase tracking-wide">Low / Info</p>
        <p class="text-3xl font-bold text-blue-600 mt-1">{{ $scan->total_low + $scan->total_info }}</p>
    </div>
</div>

{{-- Search bar --}}
<div class="mb-4" x-data="{ search: '{{ $search }}' }">
    <form method="GET" action="{{ route('projects.show', $project) }}" id="search-form">
        @if(request('scan_id'))
            <input type="hidden" name="scan_id" value="{{ request('scan_id') }}">
        @endif
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="q" value="{{ $search }}"
                   placeholder="Buscar librería o CVE/GHSA..."
                   class="w-full pl-10 pr-4 py-2.5 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-red-500 focus:outline-none bg-white"
                   @input.debounce.400ms="$el.form.submit()">
            @if($search)
            <a href="{{ route('projects.show', array_filter(['project' => $project->id, 'scan_id' => request('scan_id')])) }}"
               class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
            </a>
            @endif
        </div>
    </form>
</div>

{{-- Tabs --}}
<div x-data="{ tab: 'direct' }">
    <div class="flex border-b border-gray-200 mb-5 gap-1">
        <button @click="tab = 'direct'"
                :class="tab === 'direct' ? 'border-b-2 border-red-600 text-red-600 bg-red-50' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition-colors -mb-px flex items-center gap-2">
            Dependencias Directas
            <span class="text-xs px-2 py-0.5 rounded-full"
                  :class="tab === 'direct' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                {{ $directDeps->count() }}
            </span>
        </button>
        <button @click="tab = 'transitive'"
                :class="tab === 'transitive' ? 'border-b-2 border-red-600 text-red-600 bg-red-50' : 'text-gray-500 hover:text-gray-700'"
                class="px-5 py-2.5 text-sm font-medium rounded-t-lg transition-colors -mb-px flex items-center gap-2">
            Dependencias Transitivas
            <span class="text-xs px-2 py-0.5 rounded-full"
                  :class="tab === 'transitive' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600'">
                {{ $transitiveDeps->count() }}
            </span>
        </button>
    </div>

    <div x-show="tab === 'direct'" x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        @include('projects._deps_table', ['deps' => $directDeps, 'emptyMsg' => 'No hay dependencias directas vulnerables.'])
    </div>

    <div x-show="tab === 'transitive'" x-cloak x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
        @include('projects._deps_table', ['deps' => $transitiveDeps, 'emptyMsg' => 'No hay dependencias transitivas vulnerables.'])
    </div>
</div>

@endsection
