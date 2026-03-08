{{--
  Partial: _trivy_deps_table
  Variables: $vulns (Collection<TrivyVulnerability>)
  Groups by package, shows in SCA-style table with expandable vuln details.
--}}
@php
    $sevOrder = ['CRITICAL' => 5, 'HIGH' => 4, 'MEDIUM' => 3, 'LOW' => 2, 'UNKNOWN' => 1];

    $sevColors = [
        'CRITICAL' => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'badge' => 'bg-red-600 text-white',    'border' => 'border-red-400'],
        'HIGH'     => ['bg' => 'bg-orange-100',  'text' => 'text-orange-700', 'badge' => 'bg-orange-500 text-white', 'border' => 'border-orange-400'],
        'MEDIUM'   => ['bg' => 'bg-yellow-100',  'text' => 'text-yellow-700', 'badge' => 'bg-yellow-500 text-white', 'border' => 'border-yellow-400'],
        'LOW'      => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',   'badge' => 'bg-blue-500 text-white',   'border' => 'border-blue-400'],
        'UNKNOWN'  => ['bg' => 'bg-gray-100',    'text' => 'text-gray-600',   'badge' => 'bg-gray-400 text-white',   'border' => 'border-gray-300'],
    ];

    // Group by package identity
    $packages = $vulns
        ->groupBy(fn($v) => $v->pkg_name . '|||' . ($v->pkg_version ?? '') . '|||' . ($v->target_type ?? '') . '|||' . ($v->target ?? ''))
        ->map(function($pkgVulns) use ($sevOrder, $sevColors) {
            $maxSev = $pkgVulns
                ->sortByDesc(fn($v) => $sevOrder[strtoupper($v->severity ?? 'UNKNOWN')] ?? 0)
                ->first()?->severity ?? 'UNKNOWN';
            $maxSev = strtoupper($maxSev);
            return [
                'pkg_name'      => $pkgVulns->first()->pkg_name,
                'pkg_version'   => $pkgVulns->first()->pkg_version,
                'fixed_version' => $pkgVulns->first()->fixed_version,
                'target_type'   => $pkgVulns->first()->target_type,
                'target'        => $pkgVulns->first()->target,
                'maxSev'        => $maxSev,
                'colors'        => $sevColors[$maxSev] ?? $sevColors['UNKNOWN'],
                'vulns'         => $pkgVulns->sortByDesc(fn($v) => $sevOrder[strtoupper($v->severity ?? 'UNKNOWN')] ?? 0)->values(),
            ];
        })
        ->sortByDesc(fn($p) => $sevOrder[$p['maxSev']] ?? 0)
        ->values();
@endphp

@if($packages->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
        <div class="w-14 h-14 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="font-semibold text-gray-700">No se encontraron vulnerabilidades.</p>
    </div>
@else

<div x-data="{ filter: 'ALL', open: null }" class="space-y-3">

    {{-- Filter tabs --}}
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-gray-500 font-medium">Filtrar:</span>

        @php
            $filterDefs = [
                'ALL'      => ['ALL',      'bg-gray-900 text-white',   'bg-gray-100 text-gray-700'],
                'CRITICAL' => ['Critical', 'bg-red-600 text-white',    'bg-red-50 text-red-700'],
                'HIGH'     => ['High',     'bg-orange-500 text-white', 'bg-orange-50 text-orange-700'],
                'MEDIUM'   => ['Medium',   'bg-yellow-500 text-white', 'bg-yellow-50 text-yellow-700'],
                'LOW'      => ['Low',      'bg-blue-500 text-white',   'bg-blue-50 text-blue-700'],
            ];
        @endphp

        @foreach($filterDefs as $sev => [$label, $active, $inactive])
        <button @click="filter = '{{ $sev }}'"
                :class="filter === '{{ $sev }}' ? '{{ $active }}' : '{{ $inactive }}'"
                class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors">
            {{ $label }}
            <span class="ml-1 opacity-75">
                ({{ $sev === 'ALL' ? $packages->count() : $packages->where('maxSev', $sev)->count() }})
            </span>
        </button>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left border-b border-gray-100">
                        <th class="px-6 py-3 font-semibold text-gray-600">Librería</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Versión</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Ecosistema</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Vulns</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Severidad Máx.</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 min-w-[220px]">CVEs</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($packages as $idx => $pkg)
                    @php $colors = $pkg['colors']; @endphp

                    {{-- Package row --}}
                    <tr class="hover:bg-gray-50 transition-colors align-top border-t border-gray-50"
                        x-show="filter === 'ALL' || filter === '{{ $pkg['maxSev'] }}'"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                        {{-- Package name --}}
                        <td class="px-6 py-3.5">
                            <p class="font-semibold text-gray-900">{{ $pkg['pkg_name'] }}</p>
                            @if($pkg['target'])
                                <p class="text-xs text-gray-400 font-mono mt-0.5 truncate max-w-[200px]"
                                   title="{{ $pkg['target'] }}">{{ $pkg['target'] }}</p>
                            @endif
                        </td>

                        {{-- Version --}}
                        <td class="px-4 py-3.5">
                            @if($pkg['pkg_version'])
                                <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded whitespace-nowrap">
                                    {{ $pkg['pkg_version'] }}
                                </span>
                            @endif
                            @if($pkg['fixed_version'])
                                <p class="text-xs text-green-600 mt-0.5">→ {{ $pkg['fixed_version'] }}</p>
                            @endif
                        </td>

                        {{-- Ecosystem --}}
                        <td class="px-4 py-3.5">
                            @if($pkg['target_type'])
                                <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded font-medium uppercase tracking-wide">
                                    {{ $pkg['target_type'] }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>

                        {{-- Vuln count --}}
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 {{ $colors['bg'] }} {{ $colors['text'] }} font-bold text-xs rounded-full">
                                {{ $pkg['vulns']->count() }}
                            </span>
                        </td>

                        {{-- Max severity badge --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 {{ $colors['badge'] }} text-xs font-bold rounded-full">
                                <span class="w-1.5 h-1.5 bg-white/50 rounded-full"></span>
                                {{ $pkg['maxSev'] }}
                            </span>
                        </td>

                        {{-- CVE list (inline badges) --}}
                        <td class="px-4 py-3.5">
                            <div class="flex flex-wrap gap-1">
                                @foreach($pkg['vulns'] as $v)
                                @php $vc = $sevColors[strtoupper($v->severity ?? 'UNKNOWN')] ?? $sevColors['UNKNOWN']; @endphp
                                <span class="text-xs font-mono {{ $vc['bg'] }} {{ $vc['text'] }} px-2 py-0.5 rounded whitespace-nowrap">
                                    {{ $v->vuln_id }}
                                </span>
                                @endforeach
                            </div>
                        </td>

                        {{-- Detalles button --}}
                        <td class="px-4 py-3.5">
                            <button type="button"
                                    @click="open = (open === {{ $idx }}) ? null : {{ $idx }}"
                                    :class="open === {{ $idx }} ? 'bg-gray-700 text-white' : 'bg-gray-900 hover:bg-gray-700 text-white'"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                Detalles
                                <svg class="w-3 h-3 transition-transform" :class="open === {{ $idx }} ? 'rotate-90' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </td>
                    </tr>

                    {{-- Expanded detail rows — one sub-block per CVE --}}
                    <tr x-show="open === {{ $idx }}" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        class="border-t border-gray-100">
                        <td colspan="7" class="px-0 py-0 bg-gray-50">
                            <div class="divide-y divide-gray-100">
                                @foreach($pkg['vulns'] as $v)
                                @php $vc = $sevColors[strtoupper($v->severity ?? 'UNKNOWN')] ?? $sevColors['UNKNOWN']; @endphp
                                <div class="px-6 py-4 border-l-4 {{ $vc['border'] }}">
                                    <div class="flex items-start gap-3 mb-3">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $vc['badge'] }} flex-shrink-0">
                                            {{ strtoupper($v->severity ?? 'UNKNOWN') }}
                                        </span>
                                        <span class="font-mono text-sm font-bold text-gray-800">{{ $v->vuln_id }}</span>
                                        @if($v->cvss_v3_score !== null || $v->cvss_v2_score !== null)
                                            <span class="text-xs text-gray-500 bg-white border border-gray-200 px-2 py-0.5 rounded ml-auto flex-shrink-0">
                                                CVSS {{ $v->cvss_v3_score !== null ? number_format($v->cvss_v3_score,1).' (v3)' : number_format($v->cvss_v2_score,1).' (v2)' }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-2 gap-6 text-sm">
                                        <div class="space-y-2">
                                            @if($v->title)
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Título</p>
                                                <p class="text-gray-700">{{ $v->title }}</p>
                                            </div>
                                            @endif

                                            @if($v->description)
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Descripción</p>
                                                <p class="text-gray-600 text-xs leading-relaxed line-clamp-4">{{ $v->description }}</p>
                                            </div>
                                            @endif

                                            @if(!empty($v->cwes))
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($v->cwes as $cwe)
                                                <span class="text-xs font-mono bg-orange-100 text-orange-700 px-2 py-0.5 rounded">{{ $cwe }}</span>
                                                @endforeach
                                            </div>
                                            @endif

                                            @if($v->primary_url)
                                            <a href="{{ $v->primary_url }}" target="_blank" rel="noopener"
                                               class="text-xs text-teal-600 hover:underline flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                                </svg>
                                                {{ $v->primary_url }}
                                            </a>
                                            @endif
                                        </div>

                                        <div class="space-y-2">
                                            {{-- Package detail --}}
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Paquete</p>
                                                <div class="text-xs text-gray-600 space-y-0.5">
                                                    <div><span class="text-gray-400">Instalado:</span> <span class="font-mono">{{ $v->pkg_version ?? '—' }}</span></div>
                                                    @if($v->fixed_version)
                                                    <div><span class="text-gray-400">Fix en:</span> <span class="font-mono text-green-700">{{ $v->fixed_version }}</span></div>
                                                    @endif
                                                    @if($v->status)
                                                    <div><span class="text-gray-400">Estado:</span> {{ $v->status }}</div>
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- CVSS detail --}}
                                            @if($v->cvss_v3_score !== null || $v->cvss_v2_score !== null)
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">CVSS
                                                    @if($v->cvss_source)<span class="font-normal normal-case text-gray-400"> · {{ strtoupper($v->cvss_source) }}</span>@endif
                                                </p>
                                                <div class="text-xs space-y-0.5">
                                                    @if($v->cvss_v3_score !== null)
                                                    <div>
                                                        <span class="text-gray-400">v3:</span>
                                                        <span class="font-bold text-gray-800">{{ number_format($v->cvss_v3_score, 1) }}</span>
                                                        @if($v->cvss_v3_vector)
                                                        <span class="font-mono text-gray-400 ml-1 break-all">{{ $v->cvss_v3_vector }}</span>
                                                        @endif
                                                    </div>
                                                    @endif
                                                    @if($v->cvss_v2_score !== null)
                                                    <div>
                                                        <span class="text-gray-400">v2:</span>
                                                        <span class="font-bold text-gray-700">{{ number_format($v->cvss_v2_score, 1) }}</span>
                                                        @if($v->cvss_v2_vector)
                                                        <span class="font-mono text-gray-400 ml-1 break-all">{{ $v->cvss_v2_vector }}</span>
                                                        @endif
                                                    </div>
                                                    @endif
                                                </div>
                                            </div>
                                            @endif

                                            {{-- Dates --}}
                                            @if($v->published_date || $v->last_modified_date)
                                            <div class="text-xs text-gray-400 space-y-0.5">
                                                @if($v->published_date)<div>Publicado: {{ $v->published_date->format('d M Y') }}</div>@endif
                                                @if($v->last_modified_date)<div>Modificado: {{ $v->last_modified_date->format('d M Y') }}</div>@endif
                                            </div>
                                            @endif

                                            {{-- References --}}
                                            @if(!empty($v->references) && count($v->references) > 0)
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Referencias</p>
                                                <ul class="space-y-0.5 max-h-20 overflow-y-auto">
                                                    @foreach(array_slice($v->references, 0, 6) as $ref)
                                                    <li><a href="{{ $ref }}" target="_blank" rel="noopener"
                                                           class="text-xs text-teal-600 hover:underline truncate block max-w-xs">{{ $ref }}</a></li>
                                                    @endforeach
                                                    @if(count($v->references) > 6)
                                                    <li class="text-xs text-gray-400">… y {{ count($v->references) - 6 }} más</li>
                                                    @endif
                                                </ul>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <p class="text-xs text-gray-400 text-right">{{ $packages->count() }} paquete(s) · {{ $vulns->count() }} vulnerabilidad(es)</p>
</div>
@endif
