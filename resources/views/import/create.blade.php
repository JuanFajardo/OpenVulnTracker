@extends('layouts.app')

@section('title', 'Importar Reporte')
@section('subtitle', 'Sube un reporte JSON al sistema')

@section('content')
<div class="max-w-2xl"
     x-data="{
         scanType: 'auto',
         fileName: '',
         fileSize: '',
         parsedProject: '',
         parsedBranch: '',
         dragging: false,
         state: 'idle',
         progress: 0,
         errorMsg: '',

         types: [
             {
                 key:   'auto',
                 label: 'Auto-detectar',
                 desc:  'El sistema identifica el formato automáticamente',
                 icon:  'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
                 ring:  'ring-gray-400',
                 bg:    'bg-gray-50',
                 text:  'text-gray-700',
                 badge: 'bg-gray-100 text-gray-600',
             },
             {
                 key:   'sca',
                 label: 'SCA',
                 desc:  'OWASP Dependency-Check — análisis de dependencias',
                 icon:  'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                 ring:  'ring-red-400',
                 bg:    'bg-red-50',
                 text:  'text-red-700',
                 badge: 'bg-red-100 text-red-700',
             },
             {
                 key:   'trivy',
                 label: 'Images',
                 desc:  'Trivy — escaneo de imagen de contenedor o sistema de archivos',
                 icon:  'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
                 ring:  'ring-teal-400',
                 bg:    'bg-teal-50',
                 text:  'text-teal-700',
                 badge: 'bg-teal-100 text-teal-700',
             },
             {
                 key:   'sast',
                 label: 'SAST',
                 desc:  'Semgrep — análisis estático de código fuente',
                 icon:  'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                 ring:  'ring-purple-400',
                 bg:    'bg-purple-50',
                 text:  'text-purple-700',
                 badge: 'bg-purple-100 text-purple-700',
             },
         ],

         get currentType() {
             return this.types.find(t => t.key === this.scanType);
         },

         get dropHint() {
             if (this.scanType === 'sca')   return 'dependency-check-report.json &bull; Máx. 200 MB';
             if (this.scanType === 'trivy') return 'proyecto_rama.json &bull; Máx. 200 MB';
             if (this.scanType === 'sast')  return 'proyecto_rama.json &bull; Máx. 200 MB';
             return 'dependency-check-report.json &nbsp;/&nbsp; proyecto_rama.json &bull; Máx. 200 MB';
         },

         formatSize(bytes) {
             if (bytes >= 1024 * 1024) return (bytes / 1024 / 1024).toFixed(1) + ' MB';
             return (bytes / 1024).toFixed(1) + ' KB';
         },

         parseFileName(name) {
             const base = name.replace(/\.json$/i, '');
             const idx  = base.lastIndexOf('_');
             if (idx > 0) {
                 this.parsedProject = base.substring(0, idx);
                 this.parsedBranch  = base.substring(idx + 1);
             } else {
                 this.parsedProject = base;
                 this.parsedBranch  = '';
             }
         },

         onFileChange(e) {
             const f = e.target.files[0];
             if (f) {
                 this.fileName = f.name;
                 this.fileSize = this.formatSize(f.size);
                 this.parseFileName(f.name);
             }
         },

         onDrop(e) {
             this.dragging = false;
             const f = e.dataTransfer.files[0];
             if (f) {
                 this.fileName = f.name;
                 this.fileSize = this.formatSize(f.size);
                 this.parseFileName(f.name);
                 this.$refs.fileInput.files = e.dataTransfer.files;
             }
         },

         reset() {
             this.state         = 'idle';
             this.progress      = 0;
             this.errorMsg      = '';
             this.fileName      = '';
             this.fileSize      = '';
             this.parsedProject = '';
             this.parsedBranch  = '';
             this.$refs.fileInput.value = '';
         },

         submit(e) {
             e.preventDefault();
             if (!this.$refs.fileInput.files.length) {
                 this.errorMsg = 'Selecciona un archivo primero.';
                 this.state    = 'error';
                 return;
             }
             this.state    = 'uploading';
             this.progress = 0;
             this.errorMsg = '';

             const formData = new FormData(e.target);
             const xhr      = new XMLHttpRequest();

             xhr.upload.onprogress = (ev) => {
                 if (ev.lengthComputable) {
                     this.progress = Math.round(ev.loaded / ev.total * 100);
                 }
             };

             xhr.upload.onload = () => {
                 this.progress = 100;
                 this.state    = 'processing';
             };

             xhr.onload = () => {
                 try {
                     const r = JSON.parse(xhr.responseText);
                     if (r.success) {
                         this.state = 'success';
                         setTimeout(() => { window.location.href = r.redirect; }, 2200);
                     } else {
                         this.state    = 'error';
                         this.errorMsg = r.error || 'Error desconocido.';
                     }
                 } catch {
                     this.state    = 'error';
                     this.errorMsg = 'Respuesta inesperada del servidor (código ' + xhr.status + ').';
                 }
             };

             xhr.onerror = () => {
                 this.state    = 'error';
                 this.errorMsg = 'Error de conexión. Verifica que el servidor esté activo.';
             };

             xhr.open('POST', e.target.action);
             xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
             xhr.setRequestHeader('Accept', 'application/json');
             xhr.send(formData);
         }
     }"
     @submit="submit($event)">

    {{-- ── ESTADO: SUBIENDO ─────────────────────────────────────────────── --}}
    <div x-show="state === 'uploading'" x-cloak
         class="bg-white rounded-xl border border-gray-200 shadow-sm p-8">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
            </div>
            <div>
                <p class="font-bold text-gray-900">Subiendo archivo...</p>
                <p class="text-sm text-gray-500" x-text="fileName"></p>
            </div>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-3 mb-2 overflow-hidden">
            <div class="h-3 bg-blue-500 rounded-full transition-all duration-300"
                 :style="'width: ' + progress + '%'"></div>
        </div>
        <div class="flex justify-between text-xs text-gray-500">
            <span>Transfiriendo al servidor</span>
            <span x-text="progress + '%'" class="font-semibold text-blue-600"></span>
        </div>
    </div>

    {{-- ── ESTADO: PROCESANDO ───────────────────────────────────────────── --}}
    <div x-show="state === 'processing'" x-cloak
         class="bg-white rounded-xl border border-gray-200 shadow-sm p-8">
        <div class="flex flex-col items-center text-center py-4">
            <div class="w-16 h-16 bg-amber-50 rounded-full flex items-center justify-center mb-5">
                <svg class="w-8 h-8 text-amber-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <p class="text-lg font-bold text-gray-900 mb-1">Analizando vulnerabilidades</p>
            <p class="text-sm text-gray-500">El servidor está procesando el reporte. Para reportes grandes esto puede tomar 1-2 minutos. No cierres esta página.</p>
            <div class="mt-5 flex items-center gap-1.5">
                <span class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 0ms"></span>
                <span class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 150ms"></span>
                <span class="w-2 h-2 bg-amber-400 rounded-full animate-bounce" style="animation-delay: 300ms"></span>
            </div>
        </div>
    </div>

    {{-- ── ESTADO: ÉXITO ────────────────────────────────────────────────── --}}
    <div x-show="state === 'success'" x-cloak
         class="bg-white rounded-xl border border-green-200 shadow-sm p-8">
        <div class="flex flex-col items-center text-center py-4">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-5">
                <svg class="w-9 h-9 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <p class="text-lg font-bold text-gray-900 mb-1">¡Reporte importado exitosamente!</p>
            <p class="text-sm text-gray-500">Redirigiendo al dashboard...</p>
        </div>
    </div>

    {{-- ── ESTADO: ERROR ────────────────────────────────────────────────── --}}
    <div x-show="state === 'error'" x-cloak
         class="bg-white rounded-xl border border-red-200 shadow-sm p-8">
        <div class="flex flex-col items-center text-center py-2">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-5">
                <svg class="w-9 h-9 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <p class="text-lg font-bold text-gray-900 mb-2">Error en la importación</p>
            <p class="text-sm text-red-700 bg-red-50 border border-red-200 rounded-lg px-4 py-3 max-w-md"
               x-text="errorMsg"></p>
            <button @click="reset()"
                    class="mt-5 px-5 py-2.5 bg-gray-800 hover:bg-gray-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Reintentar
            </button>
        </div>
    </div>

    {{-- ── ESTADO: IDLE (formulario) ─────────────────────────────────────── --}}
    <div x-show="state === 'idle'">
        <form method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data">
            @csrf
            {{-- Tipo de escaneo se envía al servidor --}}
            <input type="hidden" name="scan_type" :value="scanType">

            {{-- ── Selector de tipo ──────────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-3">Tipo de reporte</p>

                <div class="grid grid-cols-4 gap-3">
                    <template x-for="t in types" :key="t.key">
                        <button type="button"
                                @click="scanType = t.key"
                                :class="scanType === t.key
                                    ? 'ring-2 ' + t.ring + ' ' + t.bg
                                    : 'ring-1 ring-gray-200 bg-white hover:bg-gray-50'"
                                class="rounded-xl p-4 text-left transition-all flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                      :class="scanType === t.key ? t.badge : 'bg-gray-100 text-gray-500'"
                                      x-text="t.label"></span>
                                <svg x-show="scanType === t.key"
                                     class="w-4 h-4 flex-shrink-0" :class="t.text"
                                     fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                          clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <svg class="w-6 h-6" :class="scanType === t.key ? t.text : 'text-gray-400'"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      :d="t.icon"/>
                            </svg>
                            <p class="text-xs text-gray-500 leading-snug" x-text="t.desc"></p>
                        </button>
                    </template>
                </div>
            </div>

            {{-- ── Dropzone ───────────────────────────────────────────────── --}}
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-3">
                    Archivo JSON
                    <span class="font-normal text-gray-400 ml-1"
                          x-text="scanType !== 'auto' ? '(' + currentType.label + ')' : ''"></span>
                </label>

                <div @dragover.prevent="dragging = true"
                     @dragleave.prevent="dragging = false"
                     @drop.prevent="onDrop($event)"
                     :class="dragging
                         ? 'border-blue-400 bg-blue-50'
                         : (fileName ? 'border-green-400 bg-green-50' : 'border-gray-300 bg-gray-50 hover:bg-gray-100')"
                     class="relative border-2 border-dashed rounded-xl p-10 text-center transition-colors cursor-pointer"
                     @click="$refs.fileInput.click()">

                    <input type="file" name="report" accept=".json" x-ref="fileInput"
                           @change="onFileChange($event)"
                           class="hidden">

                    {{-- Sin archivo --}}
                    <div x-show="!fileName">
                        <div class="w-14 h-14 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-7 h-7 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                        </div>
                        <p class="font-semibold text-gray-700">Arrastra tu archivo aquí</p>
                        <p class="text-sm text-gray-500 mt-1">o haz clic para buscar</p>
                        <p class="text-xs text-gray-400 mt-3" x-html="dropHint"></p>
                    </div>

                    {{-- Archivo seleccionado --}}
                    <div x-show="fileName" class="flex items-center justify-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <div class="text-left">
                            <p class="font-semibold text-gray-800 text-sm" x-text="fileName"></p>
                            <p class="text-xs text-gray-500" x-text="fileSize"></p>
                            <p class="text-xs text-gray-400 mt-0.5">Haz clic para cambiar</p>
                        </div>
                    </div>
                </div>

                @error('report')
                <p class="text-red-500 text-sm mt-2">{{ $message }}</p>
                @enderror

                {{-- Preview proyecto/rama extraídos del nombre del archivo --}}
                <div x-show="fileName" x-cloak
                     class="mt-4 bg-gray-50 border border-gray-200 rounded-lg px-4 py-3 flex items-start gap-3">
                    <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-gray-700 space-y-1">
                        <p class="font-semibold text-gray-800">Datos detectados del archivo</p>
                        <p>
                            <span class="text-gray-500">Proyecto:</span>
                            <code class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded text-gray-900"
                                  x-text="parsedProject || '(sin nombre)'"></code>
                        </p>
                        <p x-show="parsedBranch">
                            <span class="text-gray-500">Rama:</span>
                            <code class="font-mono bg-white border border-gray-200 px-1.5 py-0.5 rounded text-gray-900"
                                  x-text="parsedBranch"></code>
                        </p>
                        <p x-show="!parsedBranch" class="text-amber-600 text-xs">
                            Sin rama detectada — renombra el archivo como <code class="font-mono">proyecto_rama.json</code>
                        </p>
                    </div>
                </div>

                {{-- Info contextual --}}
                <div x-show="scanType === 'auto' || scanType === 'sca'"
                     class="bg-blue-50 border border-blue-100 rounded-lg p-4 mt-4">
                    <p class="text-sm font-semibold text-blue-800 mb-1">SCA — Dependency-Check</p>
                    <ul class="text-sm text-blue-700 space-y-1 list-disc list-inside">
                        <li>Solo se almacenan dependencias con vulnerabilidades</li>
                        <li>El nombre del proyecto se lee del propio JSON</li>
                        <li>Reportes grandes (~30 MB) pueden tardar 1–2 minutos</li>
                    </ul>
                </div>

                <div x-show="scanType === 'auto' || scanType === 'trivy'"
                     class="bg-teal-50 border border-teal-100 rounded-lg p-4 mt-4">
                    <p class="text-sm font-semibold text-teal-800 mb-1">Trivy — Contenedor / Imagen</p>
                    <ul class="text-sm text-teal-700 space-y-1 list-disc list-inside">
                        <li>El nombre del archivo define proyecto y rama: <code class="font-mono">proyecto_rama.json</code></li>
                        <li>Soporta escaneos de imágenes Docker y sistemas de archivos</li>
                    </ul>
                </div>

                <div x-show="scanType === 'auto' || scanType === 'sast'"
                     class="bg-purple-50 border border-purple-100 rounded-lg p-4 mt-4">
                    <p class="text-sm font-semibold text-purple-800 mb-1">SAST — Semgrep</p>
                    <ul class="text-sm text-purple-700 space-y-1 list-disc list-inside">
                        <li>El nombre del archivo define proyecto y rama: <code class="font-mono">proyecto_rama.json</code></li>
                        <li>Genera el reporte con: <code class="font-mono">semgrep --json -o proyecto_rama.json .</code></li>
                        <li>Severidades: ERROR (alta) · WARNING (media) · INFO (informativa)</li>
                    </ul>
                </div>

                {{-- Botón submit --}}
                <button type="submit"
                        :disabled="!fileName"
                        :class="scanType === 'trivy' ? 'bg-teal-600 hover:bg-teal-700'
                              : scanType === 'sca'   ? 'bg-red-600 hover:bg-red-700'
                              : scanType === 'sast'  ? 'bg-purple-600 hover:bg-purple-700'
                              : 'bg-gray-800 hover:bg-gray-700'"
                        class="mt-5 w-full px-4 py-3 disabled:opacity-40 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    <span x-text="fileName ? 'Importar ' + currentType.label : 'Selecciona un archivo'"></span>
                </button>
            </div>

        </form>

        {{-- Importación vía API --}}
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-5">
            <p class="text-sm font-semibold text-gray-700 mb-2">Importar vía API (curl)</p>
            <code class="block text-xs bg-gray-900 text-green-400 rounded-lg p-3 font-mono leading-relaxed whitespace-pre">curl -X POST \
  -H "Authorization: Bearer &lt;token&gt;" \
  -F "file=@reporte.json" \
  -F "scan_type=auto" \
  {{ url('/api/import') }}</code>
            <p class="text-xs text-gray-500 mt-2">
                <code class="font-mono">scan_type</code>: <code class="font-mono">auto</code> (detecta formato),
                <code class="font-mono">sca</code>, <code class="font-mono">trivy</code> o <code class="font-mono">sast</code> &mdash;
                Genera un token en
                <a href="{{ route('tokens.index') }}" class="text-red-600 hover:underline">Autenticación</a>.
            </p>
        </div>
    </div>

</div>
@endsection
