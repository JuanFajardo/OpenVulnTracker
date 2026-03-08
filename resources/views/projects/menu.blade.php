@extends('layouts.app')

@section('title', $project->name)
@section('subtitle', 'Selecciona una vista del proyecto')

@section('header-actions')
    <form method="POST" action="{{ route('projects.destroy', $project) }}"
          onsubmit="return confirm('¿Eliminar el proyecto {{ addslashes($project->name) }} y todos sus datos?')">
        @csrf @method('DELETE')
        <button type="submit"
                class="inline-flex items-center gap-1.5 px-3 py-2 border border-red-200 text-red-600 hover:bg-red-50 text-sm rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Eliminar proyecto
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

{{-- Project header card --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-8">
    <div class="flex items-start gap-5">
        <div class="w-14 h-14 bg-gradient-to-br from-gray-800 to-gray-900 rounded-xl flex items-center justify-center text-white font-black text-2xl flex-shrink-0 shadow">
            {{ strtoupper(substr($project->name, 0, 1)) }}
        </div>
        <div class="flex-1">
            <h2 class="text-2xl font-black text-gray-900 mb-1">{{ $project->name }}</h2>
            <div class="flex items-center flex-wrap gap-3 text-sm text-gray-500">
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    {{ count($branches) }} {{ count($branches) === 1 ? 'rama' : 'ramas' }}:
                    @foreach($branches as $b)
                        <span class="font-mono text-xs bg-purple-100 text-purple-700 px-2 py-0.5 rounded-full">{{ $b }}</span>
                    @endforeach
                </span>
                @if($latestScan)
                <span class="flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Último escaneo: {{ $latestScan->report_date?->format('d M Y') }}
                </span>
                @endif
            </div>
            <div class="mt-2 flex items-center gap-1">
                <span class="text-xs text-gray-400">SHA-256:</span>
                <code class="text-xs font-mono text-gray-500">{{ $project->name_hash }}</code>
            </div>
        </div>
    </div>
</div>

@php
    $hasSca   = $project->scans()->exists();
    $hasTrivy = $project->trivyScans()->exists();
    $hasSast  = $project->sastScans()->exists();
@endphp

{{-- Menu options — always 4 cards --}}
<div class="grid grid-cols-4 gap-5">

    {{-- ── Historial — siempre activo --}}
    <a href="{{ route('projects.history', $project) }}"
       class="group bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-gray-300 p-6 flex flex-col items-center text-center transition-all duration-200">
        <div class="w-14 h-14 bg-gray-100 group-hover:bg-gray-900 rounded-2xl flex items-center justify-center mb-4 transition-colors">
            <svg class="w-7 h-7 text-gray-600 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1.5 group-hover:text-gray-700 transition-colors">Historial</h3>
        <p class="text-xs text-gray-500 leading-relaxed">Todos los escaneos (SCA, Images, SAST) ordenados por rama y fecha.</p>
        <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 group-hover:text-gray-700 transition-colors">
            Ver historial <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>

    {{-- ── SCA --}}
    @if($hasSca)
    <a href="{{ route('projects.sca', $project) }}"
       class="group bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-red-200 p-6 flex flex-col items-center text-center transition-all duration-200">
        <div class="w-14 h-14 bg-red-50 group-hover:bg-red-600 rounded-2xl flex items-center justify-center mb-4 transition-colors">
            <svg class="w-7 h-7 text-red-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1.5 group-hover:text-red-600 transition-colors">SCA</h3>
        <p class="text-xs text-gray-500 leading-relaxed">Análisis de dependencias con OWASP Dependency-Check. Triaje por librería.</p>
        <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 group-hover:text-red-600 transition-colors">
            Ver dependencias <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>
    @else
    <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-6 flex flex-col items-center text-center opacity-40">
        <div class="w-14 h-14 bg-red-50 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-400 mb-1.5">SCA</h3>
        <p class="text-xs text-gray-400">Sin escaneos SCA.</p>
    </div>
    @endif

    {{-- ── Images (Trivy) --}}
    @if($hasTrivy)
    <a href="{{ route('projects.trivy', $project) }}"
       class="group bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-teal-200 p-6 flex flex-col items-center text-center transition-all duration-200">
        <div class="w-14 h-14 bg-teal-50 group-hover:bg-teal-600 rounded-2xl flex items-center justify-center mb-4 transition-colors">
            <svg class="w-7 h-7 text-teal-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1.5 group-hover:text-teal-600 transition-colors">Images</h3>
        <p class="text-xs text-gray-500 leading-relaxed">Escaneo de imagen de contenedor con Trivy. Paquetes del OS y dependencias.</p>
        <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 group-hover:text-teal-600 transition-colors">
            Ver vulnerabilidades <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>
    @else
    <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-6 flex flex-col items-center text-center opacity-40">
        <div class="w-14 h-14 bg-teal-50 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-teal-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-400 mb-1.5">Images</h3>
        <p class="text-xs text-gray-400">Sin escaneos de imagen.</p>
    </div>
    @endif

    {{-- ── SAST (Semgrep) --}}
    @if($hasSast)
    <a href="{{ route('projects.sast', $project) }}"
       class="group bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md hover:border-purple-200 p-6 flex flex-col items-center text-center transition-all duration-200">
        <div class="w-14 h-14 bg-purple-50 group-hover:bg-purple-600 rounded-2xl flex items-center justify-center mb-4 transition-colors">
            <svg class="w-7 h-7 text-purple-500 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-900 mb-1.5 group-hover:text-purple-600 transition-colors">SAST</h3>
        <p class="text-xs text-gray-500 leading-relaxed">Análisis estático con Semgrep. Hallazgos por archivo y regla.</p>
        <div class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold text-gray-400 group-hover:text-purple-600 transition-colors">
            Ver hallazgos <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </div>
    </a>
    @else
    <div class="bg-white rounded-2xl border border-dashed border-gray-200 p-6 flex flex-col items-center text-center opacity-40">
        <div class="w-14 h-14 bg-purple-50 rounded-2xl flex items-center justify-center mb-4">
            <svg class="w-7 h-7 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
            </svg>
        </div>
        <h3 class="text-lg font-bold text-gray-400 mb-1.5">SAST</h3>
        <p class="text-xs text-gray-400">Sin escaneos SAST.</p>
    </div>
    @endif

</div>

@endsection
