@php
    $severityColors = [
        'CRITICAL' => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'dot' => 'bg-red-500',    'badge' => 'bg-red-600 text-white'],
        'HIGH'     => ['bg' => 'bg-orange-100',  'text' => 'text-orange-700', 'dot' => 'bg-orange-500', 'badge' => 'bg-orange-500 text-white'],
        'MEDIUM'   => ['bg' => 'bg-yellow-100',  'text' => 'text-yellow-700', 'dot' => 'bg-yellow-500', 'badge' => 'bg-yellow-500 text-white'],
        'LOW'      => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',   'dot' => 'bg-blue-400',   'badge' => 'bg-blue-500 text-white'],
        'INFO'     => ['bg' => 'bg-gray-100',    'text' => 'text-gray-600',   'dot' => 'bg-gray-400',   'badge' => 'bg-gray-400 text-white'],
    ];

    $severityOrder = ['CRITICAL' => 4, 'HIGH' => 3, 'MEDIUM' => 2, 'LOW' => 1, 'INFO' => 0];

    // Sort by max severity descending
    $sortedDeps = $deps->sortByDesc(fn($d) => $severityOrder[$d->max_severity] ?? 0);

    $emptyMsg = $emptyMsg ?? 'No hay dependencias vulnerables en esta categoría.';
@endphp

@if($sortedDeps->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
        <div class="w-14 h-14 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="font-semibold text-gray-700">{{ $emptyMsg }}</p>
    </div>
@else
    {{-- Severity filter (Alpine.js client-side) --}}
    <div x-data="{ filter: 'ALL' }" class="space-y-3">

        {{-- Filter buttons --}}
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-xs text-gray-500 font-medium">Filtrar:</span>
            @foreach(['ALL' => ['ALL', 'bg-gray-900 text-white', 'bg-gray-100 text-gray-700'], 'CRITICAL' => ['Critical', 'bg-red-600 text-white', 'bg-red-50 text-red-700'], 'HIGH' => ['High', 'bg-orange-500 text-white', 'bg-orange-50 text-orange-700'], 'MEDIUM' => ['Medium', 'bg-yellow-500 text-white', 'bg-yellow-50 text-yellow-700'], 'LOW' => ['Low', 'bg-blue-500 text-white', 'bg-blue-50 text-blue-700']] as $sev => [$label, $active, $inactive])
            <button @click="filter = '{{ $sev }}'"
                    :class="filter === '{{ $sev }}' ? '{{ $active }}' : '{{ $inactive }}'"
                    class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors">
                {{ $label }}
                @if($sev !== 'ALL')
                    <span class="ml-1 opacity-75">({{ $deps->filter(fn($d) => $d->max_severity === $sev)->count() }})</span>
                @else
                    <span class="ml-1 opacity-75">({{ $deps->count() }})</span>
                @endif
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
                            <th class="px-4 py-3 font-semibold text-gray-600">CVEs / Advisories</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($sortedDeps as $dep)
                        @php
                            $maxSev = $dep->max_severity;
                            $colors = $severityColors[$maxSev] ?? $severityColors['INFO'];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors"
                            x-show="filter === 'ALL' || filter === '{{ $maxSev }}'"
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900">
                                        {{ $dep->package_name ?: \Illuminate\Support\Str::limit($dep->file_name, 50) }}
                                    </p>
                                    @if($dep->description)
                                        <p class="text-xs text-gray-400 mt-0.5 leading-snug">
                                            {{ \Illuminate\Support\Str::limit($dep->description, 90) }}
                                        </p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                @if($dep->package_version)
                                    <span class="font-mono text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded whitespace-nowrap">
                                        {{ $dep->package_version }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($dep->package_ecosystem)
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded font-medium uppercase tracking-wide">
                                        {{ $dep->package_ecosystem }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 {{ $colors['bg'] }} {{ $colors['text'] }} font-bold text-xs rounded-full">
                                    {{ $dep->vulnerabilities->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 {{ $colors['badge'] }} text-xs font-bold rounded-full">
                                    <span class="w-1.5 h-1.5 bg-white/50 rounded-full"></span>
                                    {{ $maxSev }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <div class="flex flex-wrap gap-1">
                                    @foreach($dep->vulnerabilities->sortByDesc(fn($v) => $severityOrder[strtoupper($v->severity ?? 'INFO')] ?? 0)->take(3) as $vuln)
                                        @php $vc = $severityColors[strtoupper($vuln->severity ?? 'INFO')] ?? $severityColors['INFO']; @endphp
                                        <span class="text-xs font-mono px-2 py-0.5 {{ $vc['bg'] }} {{ $vc['text'] }} rounded whitespace-nowrap"
                                              title="{{ $vuln->cvssv3_base_score ? 'CVSSv3: '.$vuln->cvssv3_base_score : '' }}">
                                            {{ \Illuminate\Support\Str::limit($vuln->name, 22) }}
                                        </span>
                                    @endforeach
                                    @if($dep->vulnerabilities->count() > 3)
                                        <span class="text-xs text-gray-400 self-center">
                                            +{{ $dep->vulnerabilities->count() - 3 }} más
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('dependencies.show', [$project, $dep]) }}"
                                   class="inline-flex items-center gap-1 px-3 py-1.5 bg-gray-900 hover:bg-gray-700 text-white text-xs font-medium rounded-lg transition-colors whitespace-nowrap">
                                    Detalles
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif
