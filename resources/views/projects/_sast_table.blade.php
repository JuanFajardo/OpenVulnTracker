{{--
  Partial: _sast_table
  Variables: $findings (Collection<SastFinding>)
  Agrupa por archivo, muestra hallazgos Semgrep con panel expandible por regla.
--}}
@php
    $sevOrder = ['ERROR' => 3, 'WARNING' => 2, 'INFO' => 1];

    $sevColors = [
        'ERROR'   => ['bg' => 'bg-red-100',    'text' => 'text-red-700',    'badge' => 'bg-red-600 text-white',    'border' => 'border-red-400'],
        'WARNING' => ['bg' => 'bg-yellow-100',  'text' => 'text-yellow-700', 'badge' => 'bg-yellow-500 text-white', 'border' => 'border-yellow-400'],
        'INFO'    => ['bg' => 'bg-blue-100',    'text' => 'text-blue-700',   'badge' => 'bg-blue-500 text-white',   'border' => 'border-blue-400'],
    ];

    // Agrupar por archivo
    $files = $findings
        ->groupBy('file_path')
        ->map(function ($fileFindings) use ($sevOrder, $sevColors) {
            $maxSev = $fileFindings
                ->sortByDesc(fn($f) => $sevOrder[strtoupper($f->severity ?? 'INFO')] ?? 0)
                ->first()?->severity ?? 'INFO';
            $maxSev = strtoupper($maxSev);
            return [
                'file_path' => $fileFindings->first()->file_path,
                'maxSev'    => $maxSev,
                'colors'    => $sevColors[$maxSev] ?? $sevColors['INFO'],
                'findings'  => $fileFindings->sortByDesc(fn($f) => $sevOrder[strtoupper($f->severity ?? 'INFO')] ?? 0)->values(),
            ];
        })
        ->sortByDesc(fn($f) => $sevOrder[$f['maxSev']] ?? 0)
        ->values();
@endphp

@if($files->isEmpty())
    <div class="bg-white rounded-xl border border-gray-200 p-12 text-center shadow-sm">
        <div class="w-14 h-14 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-3">
            <svg class="w-7 h-7 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <p class="font-semibold text-gray-700">No se encontraron hallazgos SAST.</p>
    </div>
@else

<div x-data="{ filter: 'ALL', open: null }" class="space-y-3">

    {{-- Filter tabs --}}
    <div class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-gray-500 font-medium">Filtrar:</span>

        @php
            $filterDefs = [
                'ALL'     => ['ALL',     'bg-gray-900 text-white',   'bg-gray-100 text-gray-700'],
                'ERROR'   => ['Error',   'bg-red-600 text-white',    'bg-red-50 text-red-700'],
                'WARNING' => ['Warning', 'bg-yellow-500 text-white', 'bg-yellow-50 text-yellow-700'],
                'INFO'    => ['Info',    'bg-blue-500 text-white',   'bg-blue-50 text-blue-700'],
            ];
        @endphp

        @foreach($filterDefs as $sev => [$label, $active, $inactive])
        <button @click="filter = '{{ $sev }}'"
                :class="filter === '{{ $sev }}' ? '{{ $active }}' : '{{ $inactive }}'"
                class="px-3 py-1.5 text-xs font-semibold rounded-full transition-colors">
            {{ $label }}
            <span class="ml-1 opacity-75">
                ({{ $sev === 'ALL' ? $files->count() : $files->where('maxSev', $sev)->count() }})
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
                        <th class="px-6 py-3 font-semibold text-gray-600">Archivo</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 text-center">Hallazgos</th>
                        <th class="px-4 py-3 font-semibold text-gray-600">Severidad Máx.</th>
                        <th class="px-4 py-3 font-semibold text-gray-600 min-w-[260px]">Reglas detectadas</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($files as $idx => $file)
                    @php $colors = $file['colors']; @endphp

                    {{-- File row --}}
                    <tr class="hover:bg-gray-50 transition-colors align-top border-t border-gray-50"
                        x-show="filter === 'ALL' || filter === '{{ $file['maxSev'] }}'"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">

                        {{-- File path --}}
                        <td class="px-6 py-3.5">
                            <p class="font-mono text-xs text-gray-800 break-all">{{ $file['file_path'] }}</p>
                        </td>

                        {{-- Finding count --}}
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center justify-center w-7 h-7 {{ $colors['bg'] }} {{ $colors['text'] }} font-bold text-xs rounded-full">
                                {{ $file['findings']->count() }}
                            </span>
                        </td>

                        {{-- Max severity badge --}}
                        <td class="px-4 py-3.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 {{ $colors['badge'] }} text-xs font-bold rounded-full">
                                <span class="w-1.5 h-1.5 bg-white/50 rounded-full"></span>
                                {{ $file['maxSev'] }}
                            </span>
                        </td>

                        {{-- Rule IDs (inline badges) --}}
                        <td class="px-4 py-3.5">
                            <div class="flex flex-wrap gap-1">
                                @foreach($file['findings']->unique('check_id') as $f)
                                @php $fc = $sevColors[strtoupper($f->severity ?? 'INFO')] ?? $sevColors['INFO']; @endphp
                                <span class="text-xs font-mono {{ $fc['bg'] }} {{ $fc['text'] }} px-2 py-0.5 rounded whitespace-nowrap max-w-[220px] truncate"
                                      title="{{ $f->check_id }}">
                                    {{ last(explode('.', $f->check_id)) }}
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

                    {{-- Expanded detail rows — one sub-block per finding --}}
                    <tr x-show="open === {{ $idx }}" x-cloak
                        x-transition:enter="transition ease-out duration-150"
                        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                        class="border-t border-gray-100">
                        <td colspan="5" class="px-0 py-0 bg-gray-50">
                            <div class="divide-y divide-gray-100">
                                @foreach($file['findings'] as $f)
                                @php $fc = $sevColors[strtoupper($f->severity ?? 'INFO')] ?? $sevColors['INFO']; @endphp
                                <div class="px-6 py-4 border-l-4 {{ $fc['border'] }}">
                                    {{-- Header: severity + rule ID + location --}}
                                    <div class="flex items-start gap-3 mb-3 flex-wrap">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $fc['badge'] }} flex-shrink-0">
                                            {{ strtoupper($f->severity ?? 'INFO') }}
                                        </span>
                                        @if($f->confidence)
                                        <span class="text-xs {{ $fc['bg'] }} {{ $fc['text'] }} px-2 py-0.5 rounded font-medium">
                                            Confianza: {{ $f->confidence }}
                                        </span>
                                        @endif
                                        <span class="font-mono text-xs text-gray-500 flex-shrink-0">
                                            Línea {{ $f->line_start }}{{ $f->col_start ? ':' . $f->col_start : '' }}
                                        </span>
                                        <span class="text-xs text-gray-400 font-mono break-all">{{ $f->check_id }}</span>
                                    </div>

                                    <div class="grid grid-cols-2 gap-6 text-sm">
                                        <div class="space-y-2">
                                            {{-- Message --}}
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Descripción</p>
                                                <p class="text-gray-700 text-sm leading-relaxed">{{ $f->message }}</p>
                                            </div>

                                            {{-- Code snippet --}}
                                            @if($f->code_snippet)
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Código</p>
                                                <pre class="text-xs font-mono bg-gray-900 text-green-300 px-3 py-2 rounded overflow-x-auto whitespace-pre-wrap">{{ $f->code_snippet }}</pre>
                                            </div>
                                            @endif

                                            {{-- Fix --}}
                                            @if($f->fix)
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Corrección sugerida</p>
                                                <p class="text-gray-600 text-xs leading-relaxed bg-green-50 border border-green-200 rounded px-2 py-1.5">{{ $f->fix }}</p>
                                            </div>
                                            @endif
                                        </div>

                                        <div class="space-y-2">
                                            {{-- Category + CWEs --}}
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Categoría</p>
                                                <div class="flex flex-wrap gap-1">
                                                    @if($f->category)
                                                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded font-medium uppercase tracking-wide">
                                                        {{ $f->category }}
                                                    </span>
                                                    @endif
                                                    @if(!empty($f->cwe))
                                                        @foreach($f->cwe as $cwe)
                                                        <span class="text-xs font-mono bg-orange-100 text-orange-700 px-2 py-0.5 rounded">{{ $cwe }}</span>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>

                                            {{-- OWASP --}}
                                            @if(!empty($f->owasp))
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">OWASP</p>
                                                <div class="flex flex-wrap gap-1">
                                                    @foreach($f->owasp as $owasp)
                                                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-mono">{{ $owasp }}</span>
                                                    @endforeach
                                                </div>
                                            </div>
                                            @endif

                                            {{-- References --}}
                                            @if(!empty($f->references) && count($f->references) > 0)
                                            <div>
                                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">Referencias</p>
                                                <ul class="space-y-0.5 max-h-24 overflow-y-auto">
                                                    @foreach(array_slice($f->references, 0, 5) as $ref)
                                                    <li>
                                                        <a href="{{ $ref }}" target="_blank" rel="noopener"
                                                           class="text-xs text-purple-600 hover:underline truncate block max-w-xs">{{ $ref }}</a>
                                                    </li>
                                                    @endforeach
                                                    @if(count($f->references) > 5)
                                                    <li class="text-xs text-gray-400">… y {{ count($f->references) - 5 }} más</li>
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
    <p class="text-xs text-gray-400 text-right">{{ $files->count() }} archivo(s) · {{ $findings->count() }} hallazgo(s)</p>
</div>
@endif
