@extends('layouts.app')

@section('title', $project->name . ' — Historial')
@section('subtitle', 'Todos los escaneos históricos por rama')

@section('header-actions')
    <a href="{{ route('projects.show', $project) }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        {{ $project->name }}
    </a>
@endsection

@section('content')

@if($allByBranch->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-16 text-center shadow-sm">
        <p class="text-gray-500">No hay escaneos registrados para este proyecto.</p>
    </div>
@else

@foreach($allByBranch as $branch => $rows)
<div class="mb-8">

    {{-- Branch header --}}
    <div class="flex items-center gap-3 mb-3">
        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-lg font-bold text-gray-900">{{ $project->name }}</h2>
            <span class="font-mono text-sm text-purple-700 bg-purple-100 px-2.5 py-0.5 rounded-full">{{ $branch }}</span>
        </div>
        <span class="ml-auto text-xs text-gray-400">{{ $rows->count() }} escaneo(s)</span>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left border-b border-gray-100">
                        <th class="px-5 py-3 font-semibold text-gray-600">Fecha</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Tipo</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Cargado el</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Fuente</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Cargado por</th>
                        <th class="px-4 py-3 font-semibold text-red-500 text-center">Crit.</th>
                        <th class="px-4 py-3 font-semibold text-orange-500 text-center">Alto</th>
                        <th class="px-4 py-3 font-semibold text-yellow-600 text-center">Medio</th>
                        <th class="px-4 py-3 font-semibold text-blue-500 text-center">Bajo</th>
                        <th class="px-4 py-3 font-semibold text-gray-500 text-center">Otro</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Total</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($rows as $i => $row)
                    @php
                        $type = $row['type'];
                        $scan = $row['item'];
                        $date = $row['date'];

                        $sourceIcons = [
                            'web' => ['icon' => '🌐', 'class' => 'bg-blue-100 text-blue-700'],
                            'cli' => ['icon' => '⌨️', 'class' => 'bg-gray-100 text-gray-700'],
                            'api' => ['icon' => '🔌', 'class' => 'bg-purple-100 text-purple-700'],
                        ];
                        $src = $sourceIcons[$scan->upload_source] ?? $sourceIcons['cli'];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors {{ $i === 0 ? 'bg-green-50/30' : '' }}">

                        {{-- Date --}}
                        <td class="px-5 py-3.5">
                            <div class="font-semibold text-gray-900">{{ $date?->format('d M Y') }}</div>
                            <div class="text-xs text-gray-400">{{ $date?->format('H:i') }}</div>
                            @if($i === 0)
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-medium">Último</span>
                            @endif
                        </td>

                        {{-- Type badge --}}
                        <td class="px-4 py-3.5">
                            @if($type === 'sca')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 text-xs font-bold rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                    SCA
                                </span>
                            @elseif($type === 'trivy')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-teal-100 text-teal-700 text-xs font-bold rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                    </svg>
                                    Trivy
                                </span>
                                @if($scan->artifact_name)
                                    <p class="text-xs text-gray-400 font-mono mt-0.5 truncate max-w-[120px]">{{ $scan->artifact_name }}</p>
                                @endif
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-purple-100 text-purple-700 text-xs font-bold rounded-full">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                                    </svg>
                                    SAST
                                </span>
                                @if($scan->semgrep_version)
                                    <p class="text-xs text-gray-400 font-mono mt-0.5">v{{ $scan->semgrep_version }}</p>
                                @endif
                            @endif
                        </td>

                        {{-- Uploaded at --}}
                        <td class="px-4 py-3.5 text-gray-600">
                            <div>{{ $scan->uploaded_at?->format('d M Y H:i') ?? $scan->created_at->format('d M Y H:i') }}</div>
                            <div class="text-xs text-gray-400">{{ ($scan->uploaded_at ?? $scan->created_at)->diffForHumans() }}</div>
                        </td>

                        {{-- Source --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium rounded-full {{ $src['class'] }}">
                                {{ $src['icon'] }} {{ $scan->source_label }}
                            </span>
                        </td>

                        {{-- Uploader --}}
                        <td class="px-4 py-3.5 text-gray-600">
                            @if($scan->uploader)
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-gray-200 rounded-full flex items-center justify-center text-xs font-bold text-gray-600">
                                        {{ strtoupper(substr($scan->uploader->name, 0, 1)) }}
                                    </div>
                                    <span class="text-sm">{{ $scan->uploader->name }}</span>
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">Sistema</span>
                            @endif
                        </td>

                        {{-- Severity counts (campos distintos según tipo) --}}
                        @php
                            if ($type === 'sast') {
                                // SAST: ERROR → Crit., WARNING → Medio, INFO → Bajo
                                $c = $scan->total_error   ?? 0;
                                $h = 0;
                                $m = $scan->total_warning ?? 0;
                                $l = $scan->total_info    ?? 0;
                                $o = 0;
                                $total = $scan->total_findings ?? ($c+$m+$l);
                            } elseif ($type === 'sca') {
                                $c = $scan->total_critical ?? 0;
                                $h = $scan->total_high     ?? 0;
                                $m = $scan->total_medium   ?? 0;
                                $l = $scan->total_low      ?? 0;
                                $o = $scan->total_info     ?? 0;
                                $total = $scan->total_vulnerable_dependencies ?? ($c+$h+$m+$l+$o);
                            } else { // trivy
                                $c = $scan->total_critical ?? 0;
                                $h = $scan->total_high     ?? 0;
                                $m = $scan->total_medium   ?? 0;
                                $l = $scan->total_low      ?? 0;
                                $o = $scan->total_unknown  ?? 0;
                                $total = $scan->total_vulnerabilities ?? ($c+$h+$m+$l+$o);
                            }
                        @endphp
                        <td class="px-4 py-3.5 text-center {{ $c > 0 ? 'text-red-600 font-bold' : 'text-gray-400' }}">{{ $c ?: '—' }}</td>
                        <td class="px-4 py-3.5 text-center {{ $h > 0 ? 'text-orange-500 font-bold' : 'text-gray-400' }}">{{ $h ?: '—' }}</td>
                        <td class="px-4 py-3.5 text-center {{ $m > 0 ? 'text-yellow-600 font-bold' : 'text-gray-400' }}">{{ $m ?: '—' }}</td>
                        <td class="px-4 py-3.5 text-center {{ $l > 0 ? 'text-blue-500 font-bold' : 'text-gray-400' }}">{{ $l ?: '—' }}</td>
                        <td class="px-4 py-3.5 text-center text-gray-500">{{ $o ?: '—' }}</td>
                        <td class="px-4 py-3.5 text-center font-semibold text-gray-700">{{ number_format($total) }}</td>

                        {{-- Actions --}}
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-2">
                                @if($type === 'sca')
                                    <a href="{{ route('projects.scans.show', [$project, $scan]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-900 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                        Ver detalles
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('projects.scans.destroy', [$project, $scan]) }}"
                                          onsubmit="return confirm('¿Eliminar este escaneo SCA?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @elseif($type === 'trivy')
                                    <a href="{{ route('projects.trivy.show', [$project, $scan]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-teal-700 hover:bg-teal-800 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                        Ver detalles
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('projects.trivy.destroy', [$project, $scan]) }}"
                                          onsubmit="return confirm('¿Eliminar este escaneo Trivy?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('projects.sast.show', [$project, $scan]) }}"
                                       class="inline-flex items-center gap-1 px-3 py-1.5 bg-purple-700 hover:bg-purple-800 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                        Ver detalles
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('projects.sast.destroy', [$project, $scan]) }}"
                                          onsubmit="return confirm('¿Eliminar este escaneo SAST?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endforeach

@endif
@endsection
