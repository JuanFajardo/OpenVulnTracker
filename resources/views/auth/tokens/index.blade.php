@extends('layouts.app')

@section('title', 'Tokens de API')
@section('subtitle', 'Gestión de tokens de acceso para importación vía curl')

@section('header-actions')
    <button onclick="document.getElementById('modal-create').classList.remove('hidden')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg shadow transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Token
    </button>
@endsection

@section('content')

{{-- ────────────────────────────────────────────────────────────────────────
     Modal: Token recién creado (mostrar UNA sola vez)
──────────────────────────────────────────────────────────────────────── --}}
@if(session('new_token'))
<div id="modal-new-token" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
     x-data>
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg mx-4 p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-gray-900">Token creado</h2>
                <p class="text-sm text-gray-500">Cópialo ahora — no se mostrará de nuevo</p>
            </div>
        </div>

        <div class="bg-gray-900 rounded-lg p-4 mb-4">
            <code id="new-token-value" class="text-green-400 text-sm break-all font-mono select-all">{{ session('new_token') }}</code>
        </div>

        <p class="text-xs text-gray-500 mb-4">
            Usa este token en el header <code class="bg-gray-100 px-1 rounded">Authorization: Bearer &lt;token&gt;</code>
        </p>

        <div class="bg-gray-50 rounded-lg p-3 mb-5">
            <p class="text-xs font-semibold text-gray-600 mb-1">Ejemplo curl:</p>
            <code class="text-xs text-gray-700 break-all">
                curl -X POST -H "Authorization: Bearer {{ session('new_token') }}" \<br>
                &nbsp;&nbsp;-F "file=@report.json" -F "branch=main" \<br>
                &nbsp;&nbsp;{{ url('/api/import') }}
            </code>
        </div>

        <div class="flex gap-3">
            <button onclick="copyToken()"
                    class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gray-900 hover:bg-gray-700 text-white text-sm font-semibold rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span id="copy-btn-label">Copiar token</span>
            </button>
            <button onclick="document.getElementById('modal-new-token').classList.add('hidden')"
                    class="flex-1 px-4 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>
<script>
function copyToken() {
    const val = document.getElementById('new-token-value').textContent;
    navigator.clipboard.writeText(val).then(() => {
        document.getElementById('copy-btn-label').textContent = '¡Copiado!';
        setTimeout(() => { document.getElementById('copy-btn-label').textContent = 'Copiar token'; }, 2000);
    });
}
</script>
@endif

{{-- ────────────────────────────────────────────────────────────────────────
     Info: cómo usar los tokens
──────────────────────────────────────────────────────────────────────── --}}
<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex gap-3">
    <svg class="w-5 h-5 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>
    <div class="text-sm text-blue-800">
        <p class="font-semibold mb-1">Importación vía API (curl)</p>
        <code class="block bg-blue-100 rounded px-3 py-2 text-xs text-blue-900 leading-relaxed">
            curl -X POST \<br>
            &nbsp;&nbsp;-H "Authorization: Bearer &lt;tu_token&gt;" \<br>
            &nbsp;&nbsp;-F "file=@dependency-check-report.json" \<br>
            &nbsp;&nbsp;-F "branch=main" \<br>
            &nbsp;&nbsp;{{ url('/api/import') }}
        </code>
        <p class="mt-2 text-xs text-blue-700">
            El parámetro <code class="bg-blue-100 px-1 rounded">branch</code> es opcional (por defecto: <em>main</em>).
            Para archivos grandes usa <code class="bg-blue-100 px-1 rounded">--max-time 600</code> en curl.
        </p>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────────
     Leyenda de colores
──────────────────────────────────────────────────────────────────────── --}}
<div class="flex items-center gap-6 mb-4 text-xs text-gray-500">
    <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded bg-green-100 border border-green-300"></span> Activo
    </span>
    <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded bg-amber-100 border border-amber-300"></span> Vence en ≤7 días
    </span>
    <span class="flex items-center gap-1.5">
        <span class="inline-block w-3 h-3 rounded bg-gray-200 border border-gray-300"></span> Expirado
    </span>
</div>

{{-- ────────────────────────────────────────────────────────────────────────
     Tabla de tokens
──────────────────────────────────────────────────────────────────────── --}}
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Nombre / Descripción</th>
                <th class="px-5 py-3 text-left">Token</th>
                <th class="px-5 py-3 text-left">Estado</th>
                <th class="px-5 py-3 text-left">Vence</th>
                <th class="px-5 py-3 text-left">Último uso</th>
                <th class="px-5 py-3 text-left">Creado por</th>
                <th class="px-5 py-3 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($tokens as $token)
            @php $badge = $token->statusBadge(); @endphp
            <tr class="{{ $token->rowClass() }} hover:bg-gray-50 transition-colors">

                {{-- Nombre + descripción --}}
                <td class="px-5 py-3.5">
                    <p class="font-semibold text-gray-900">{{ $token->name }}</p>
                    @if($token->description)
                        <p class="text-xs text-gray-500 mt-0.5">{{ $token->description }}</p>
                    @endif
                </td>

                {{-- Token (enmascarado) --}}
                <td class="px-5 py-3.5 font-mono text-xs text-gray-500">
                    vt_••••••••••••••••••••••••••••••••
                </td>

                {{-- Estado --}}
                <td class="px-5 py-3.5">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badge['class'] }}">
                        {{ $badge['label'] }}
                    </span>
                </td>

                {{-- Fecha de vencimiento --}}
                <td class="px-5 py-3.5 text-gray-600">
                    <span class="{{ $token->isExpiringSoon() ? 'text-amber-700 font-semibold' : ($token->isExpired() ? 'text-gray-400 line-through' : '') }}">
                        {{ $token->expires_at->format('d/m/Y') }}
                    </span>
                    <p class="text-xs text-gray-400">{{ $token->duration_days }} días</p>
                </td>

                {{-- Último uso --}}
                <td class="px-5 py-3.5 text-gray-500">
                    {{ $token->last_used_at ? $token->last_used_at->format('d/m/Y H:i') : '—' }}
                </td>

                {{-- Creado por --}}
                <td class="px-5 py-3.5 text-gray-500">
                    {{ $token->creator->name ?? '—' }}
                    <p class="text-xs text-gray-400">{{ $token->created_at->format('d/m/Y') }}</p>
                </td>

                {{-- Acciones --}}
                <td class="px-5 py-3.5 text-right">
                    <div class="flex items-center justify-end gap-2">
                        {{-- Botón Editar --}}
                        <button
                            data-update-url="{{ route('tokens.update', $token) }}"
                            data-name="{{ $token->name }}"
                            data-description="{{ $token->description ?? '' }}"
                            data-duration="{{ $token->duration_days }}"
                            onclick="openEditModal(this)"
                            class="px-3 py-1.5 text-xs font-medium text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Editar
                        </button>

                        {{-- Botón Revocar --}}
                        <form action="{{ route('tokens.destroy', $token) }}" method="POST"
                              onsubmit="return confirm('¿Revocar el token «{{ addslashes($token->name) }}»? Esta acción no se puede deshacer.')">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3 py-1.5 text-xs font-medium text-red-600 border border-red-200 rounded-lg hover:bg-red-50 transition-colors">
                                Revocar
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="px-5 py-12 text-center text-gray-400">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    <p class="font-medium">No hay tokens creados</p>
                    <p class="text-sm mt-1">Crea un token para permitir importaciones vía API</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- ────────────────────────────────────────────────────────────────────────
     Modal: Crear token
──────────────────────────────────────────────────────────────────────── --}}
<div id="modal-create" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Nuevo Token de API</h2>
            <button onclick="document.getElementById('modal-create').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form action="{{ route('tokens.store') }}" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" name="name" required maxlength="100"
                       placeholder="Ej: CI/CD Pipeline Prod"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea name="description" maxlength="500" rows="2"
                          placeholder="Para qué se usará este token..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Duración (días) <span class="text-red-500">*</span></label>
                <input type="number" name="duration_days" value="30" min="1" max="3650" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                <p class="text-xs text-gray-400 mt-1">Por defecto: 30 días. Máximo: 3650 días (10 años).</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    Generar Token
                </button>
                <button type="button"
                        onclick="document.getElementById('modal-create').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ────────────────────────────────────────────────────────────────────────
     Modal: Editar token
──────────────────────────────────────────────────────────────────────── --}}
<div id="modal-edit" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 hidden">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900">Editar Token</h2>
            <button onclick="document.getElementById('modal-edit').classList.add('hidden')"
                    class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="edit-form" action="" method="POST" class="px-6 py-5 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre <span class="text-red-500">*</span></label>
                <input type="text" id="edit-name" name="name" required maxlength="100"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                <textarea id="edit-description" name="description" maxlength="500" rows="2"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none resize-none"></textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nueva duración (días desde ahora) <span class="text-red-500">*</span></label>
                <input type="number" id="edit-duration" name="duration_days" min="1" max="3650" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none">
                <p class="text-xs text-amber-600 mt-1">La fecha de vencimiento se calculará desde ahora.</p>
            </div>
            <div class="flex gap-3 pt-2">
                <button type="submit"
                        class="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                    Guardar Cambios
                </button>
                <button type="button"
                        onclick="document.getElementById('modal-edit').classList.add('hidden')"
                        class="flex-1 px-4 py-2.5 border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-lg transition-colors">
                    Cancelar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(btn) {
    document.getElementById('edit-form').action = btn.dataset.updateUrl;
    document.getElementById('edit-name').value = btn.dataset.name;
    document.getElementById('edit-description').value = btn.dataset.description;
    document.getElementById('edit-duration').value = btn.dataset.duration;
    document.getElementById('modal-edit').classList.remove('hidden');
}
</script>

@endsection
