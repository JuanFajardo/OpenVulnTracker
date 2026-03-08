{{--
  Partial: _trivy_table
  Variables: $vulns (Collection of TrivyVulnerability)
--}}

@if($vulns->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="text-gray-500 text-sm">No se encontraron vulnerabilidades.</p>
    </div>
@else

<div x-data="{ open: null }">
@foreach($vulns as $v)
<div class="bg-white rounded-xl border-l-4 {{ $v->severityBorder() }} border-t border-r border-b border-gray-200 shadow-sm mb-3 overflow-hidden">

    {{-- Row header (clickable) --}}
    <button type="button"
            class="w-full text-left px-5 py-3.5 flex items-center gap-4 hover:bg-gray-50 transition-colors"
            @click="open = (open === {{ $loop->index }}) ? null : {{ $loop->index }}">

        {{-- Severity badge --}}
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $v->severityClass() }} flex-shrink-0 w-20 justify-center">
            {{ $v->severity }}
        </span>

        {{-- CVE / GHSA ID --}}
        <span class="font-mono text-sm font-bold text-gray-800 w-48 flex-shrink-0">{{ $v->vuln_id }}</span>

        {{-- Package --}}
        <div class="flex-1 min-w-0">
            <span class="font-semibold text-gray-700">{{ $v->pkg_name }}</span>
            @if($v->pkg_version)
            <span class="text-xs text-gray-400 font-mono ml-1">{{ $v->pkg_version }}</span>
            @endif
            @if($v->fixed_version)
            <span class="text-xs text-green-600 ml-2">→ fix: {{ $v->fixed_version }}</span>
            @endif
        </div>

        {{-- CVSS score --}}
        @if($v->mainScore() !== null)
        <span class="text-sm font-bold text-gray-600 flex-shrink-0 w-12 text-right">
            {{ number_format($v->mainScore(), 1) }}
        </span>
        @endif

        {{-- Target / type --}}
        @if($v->target_type)
        <span class="text-xs text-gray-400 bg-gray-100 px-2 py-0.5 rounded font-mono flex-shrink-0">
            {{ $v->target_type }}
        </span>
        @endif

        {{-- Chevron --}}
        <svg class="w-4 h-4 text-gray-400 flex-shrink-0 transition-transform"
             :class="open === {{ $loop->index }} ? 'rotate-90' : ''"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </button>

    {{-- Expanded detail --}}
    <div x-show="open === {{ $loop->index }}" x-cloak x-transition
         class="border-t border-gray-100 px-5 py-4 bg-gray-50">

        <div class="grid grid-cols-2 gap-6">

            {{-- Left column --}}
            <div class="space-y-3">

                {{-- Title --}}
                @if($v->title)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Título</p>
                    <p class="text-sm text-gray-700">{{ $v->title }}</p>
                </div>
                @endif

                {{-- Description --}}
                @if($v->description)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Descripción</p>
                    <p class="text-sm text-gray-600 leading-relaxed line-clamp-5">{{ $v->description }}</p>
                </div>
                @endif

                {{-- CWEs --}}
                @if(!empty($v->cwes))
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">CWE</p>
                    <div class="flex flex-wrap gap-1">
                        @foreach($v->cwes as $cwe)
                        <span class="text-xs font-mono bg-orange-100 text-orange-700 px-2 py-0.5 rounded">{{ $cwe }}</span>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Primary URL --}}
                @if($v->primary_url)
                <div>
                    <a href="{{ $v->primary_url }}" target="_blank" rel="noopener"
                       class="text-xs text-teal-600 hover:underline flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        {{ $v->primary_url }}
                    </a>
                </div>
                @endif
            </div>

            {{-- Right column --}}
            <div class="space-y-3">

                {{-- Package details --}}
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Paquete</p>
                    <div class="text-sm text-gray-700 space-y-0.5">
                        <div><span class="text-gray-400">Nombre:</span> <span class="font-mono">{{ $v->pkg_name }}</span></div>
                        @if($v->pkg_version)
                        <div><span class="text-gray-400">Instalado:</span> <span class="font-mono">{{ $v->pkg_version }}</span></div>
                        @endif
                        @if($v->fixed_version)
                        <div><span class="text-gray-400">Fix en:</span> <span class="font-mono text-green-700">{{ $v->fixed_version }}</span></div>
                        @endif
                        @if($v->status)
                        <div><span class="text-gray-400">Estado:</span> {{ $v->status }}</div>
                        @endif
                        @if($v->pkg_path)
                        <div><span class="text-gray-400">Ruta:</span> <span class="font-mono text-xs">{{ $v->pkg_path }}</span></div>
                        @endif
                    </div>
                </div>

                {{-- Target --}}
                @if($v->target)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Target</p>
                    <p class="text-sm font-mono text-gray-600">{{ $v->target }}</p>
                    @if($v->target_class || $v->target_type)
                    <p class="text-xs text-gray-400 mt-0.5">
                        {{ $v->target_class }}{{ $v->target_class && $v->target_type ? ' / ' : '' }}{{ $v->target_type }}
                    </p>
                    @endif
                </div>
                @endif

                {{-- CVSS --}}
                @if($v->cvss_v3_score !== null || $v->cvss_v2_score !== null)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">CVSS</p>
                    <div class="text-sm space-y-0.5">
                        @if($v->cvss_source)
                        <div class="text-xs text-gray-400">Fuente: {{ strtoupper($v->cvss_source) }}</div>
                        @endif
                        @if($v->cvss_v3_score !== null)
                        <div>
                            <span class="text-gray-500">v3:</span>
                            <span class="font-bold text-gray-800">{{ number_format($v->cvss_v3_score, 1) }}</span>
                            @if($v->cvss_v3_vector)
                            <span class="font-mono text-xs text-gray-400 ml-1">{{ $v->cvss_v3_vector }}</span>
                            @endif
                        </div>
                        @endif
                        @if($v->cvss_v2_score !== null)
                        <div>
                            <span class="text-gray-500">v2:</span>
                            <span class="font-bold text-gray-700">{{ number_format($v->cvss_v2_score, 1) }}</span>
                            @if($v->cvss_v2_vector)
                            <span class="font-mono text-xs text-gray-400 ml-1">{{ $v->cvss_v2_vector }}</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Dates --}}
                @if($v->published_date || $v->last_modified_date)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">Fechas</p>
                    <div class="text-xs text-gray-500 space-y-0.5">
                        @if($v->published_date)
                        <div>Publicado: {{ $v->published_date->format('d M Y') }}</div>
                        @endif
                        @if($v->last_modified_date)
                        <div>Modificado: {{ $v->last_modified_date->format('d M Y') }}</div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- References --}}
                @if(!empty($v->references) && count($v->references) > 0)
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-1">
                        Referencias ({{ count($v->references) }})
                    </p>
                    <ul class="space-y-0.5 max-h-24 overflow-y-auto">
                        @foreach(array_slice($v->references, 0, 8) as $ref)
                        <li>
                            <a href="{{ $ref }}" target="_blank" rel="noopener"
                               class="text-xs text-teal-600 hover:underline truncate block max-w-xs">
                                {{ $ref }}
                            </a>
                        </li>
                        @endforeach
                        @if(count($v->references) > 8)
                        <li class="text-xs text-gray-400">… y {{ count($v->references) - 8 }} más</li>
                        @endif
                    </ul>
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endforeach
</div>

<p class="text-xs text-gray-400 mt-3 text-right">{{ $vulns->count() }} vulnerabilidad(es) mostrada(s)</p>
@endif
