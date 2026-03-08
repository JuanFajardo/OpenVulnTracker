@props(['vulnerability', 'returnUrl' => null])

@php
    $state   = $vulnerability->state;
    $current = $state?->state ?? 'sin_revision';
    $labels  = \App\Models\VulnerabilityState::labels();
    $colors  = \App\Models\VulnerabilityState::colors();
@endphp

<div x-data="{
        open: false,
        state: '{{ $current }}',
        comment: '',
        get valid() { return this.comment.trim().length >= 5 }
     }"
     class="inline-block">

    {{-- Trigger badge --}}
    <button @click="open = true"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-semibold rounded-full border cursor-pointer
                   hover:opacity-80 transition-opacity {{ $colors[$current] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
        @if($current === 'sin_revision')
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @elseif($current === 'mitigado')
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @else
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        @endif
        {{ $labels[$current] ?? 'Sin Revisión' }}
    </button>

    {{-- Audit info --}}
    @if($state)
    <div class="text-xs text-gray-400 mt-0.5">
        {{ $state->user->name ?? '—' }} · {{ $state->updated_at->format('d M Y H:i') }}
    </div>
    @endif

    {{-- Modal backdrop --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
         @keydown.escape.window="open = false">

        <div @click.outside="open = false"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-2xl shadow-2xl w-full max-w-lg">

            <div class="px-6 py-5 border-b border-gray-100 flex items-start justify-between gap-3">
                <div>
                    <h3 class="font-bold text-gray-900 text-base">Actualizar estado de vulnerabilidad</h3>
                    <p class="text-sm text-gray-500 font-mono mt-0.5">{{ $vulnerability->name }}</p>
                </div>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600 p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST"
                  action="{{ route('vulnerabilities.state', $vulnerability) }}"
                  class="px-6 py-5 space-y-5">
                @csrf
                @if($returnUrl)
                    <input type="hidden" name="_return" value="{{ $returnUrl }}">
                @endif

                {{-- Estado --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Estado</label>
                    <div class="grid grid-cols-3 gap-3">
                        @foreach($labels as $val => $label)
                        @php $c = $colors[$val]; @endphp
                        <label class="relative cursor-pointer">
                            <input type="radio" name="state" value="{{ $val }}"
                                   x-model="state" class="sr-only">
                            <div :class="state === '{{ $val }}' ? 'ring-2 ring-offset-1 ring-gray-900 {{ $c }}' : 'border border-gray-200 text-gray-600 hover:border-gray-300'"
                                 class="px-3 py-2.5 rounded-xl text-sm font-semibold text-center transition-all">
                                @if($val === 'sin_revision') ⏳
                                @elseif($val === 'mitigado') ✅
                                @else ℹ️
                                @endif
                                <div>{{ $label }}</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Comentario obligatorio --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">
                        Comentario <span class="text-red-500">*</span>
                        <span class="text-gray-400 font-normal text-xs ml-1">(mínimo 5 caracteres)</span>
                    </label>
                    <textarea name="comment" x-model="comment" rows="3"
                              placeholder="Explica el motivo del cambio de estado, medidas tomadas, o por qué es un falso positivo..."
                              class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm focus:ring-2 focus:ring-gray-900 focus:outline-none resize-none"
                              :class="!valid && comment.length > 0 ? 'border-red-300' : ''"></textarea>
                    <div x-show="!valid && comment.length > 0" class="text-red-500 text-xs mt-1">
                        El comentario debe tener al menos 5 caracteres.
                    </div>
                </div>

                {{-- Historial del estado actual --}}
                @if($state)
                <div class="bg-gray-50 rounded-xl border border-gray-100 px-4 py-3 text-sm">
                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide mb-1">Estado anterior</p>
                    <p class="text-gray-700">
                        <span class="font-semibold">{{ $labels[$state->state] ?? $state->state }}</span>
                        · por <span class="font-semibold">{{ $state->user->name ?? '—' }}</span>
                        · {{ $state->updated_at->format('d M Y H:i') }}
                    </p>
                    @if($state->comment)
                    <p class="text-gray-500 mt-1 text-xs italic">"{{ \Illuminate\Support\Str::limit($state->comment, 100) }}"</p>
                    @endif
                </div>
                @endif

                <div class="flex gap-3 pt-1">
                    <button type="submit" :disabled="!valid"
                            class="flex-1 py-2.5 bg-gray-900 hover:bg-gray-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-xl transition-colors">
                        Guardar estado
                    </button>
                    <button type="button" @click="open = false"
                            class="px-5 py-2.5 border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm font-medium rounded-xl transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
