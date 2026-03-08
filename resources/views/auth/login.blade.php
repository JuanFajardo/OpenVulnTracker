<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenVulnTracker — Iniciar Sesión</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet"/>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-950 font-sans antialiased">

<div class="min-h-screen flex">

    {{-- Left branding panel --}}
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-gray-900 via-gray-900 to-red-950 flex-col justify-between p-12 relative overflow-hidden">
        {{-- Background decoration --}}
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-20 left-10 w-72 h-72 bg-red-500 rounded-full blur-3xl"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-red-800 rounded-full blur-3xl"></div>
        </div>

        <div class="relative">
            <div class="flex items-center gap-3">
                <img src="{{ asset('logo.png') }}" alt="OpenVulnTracker" class="h-32 w-auto object-contain">
            </div>
        </div>

        <div class="relative">
            <h1 class="text-4xl font-black text-white leading-tight mb-4">
                Gestión de<br>
                <span class="text-red-400">Vulnerabilidades</span><br>
                de Dependencias
            </h1>
            <p class="text-gray-400 leading-relaxed mb-8">
                Importa, analiza y visualiza los reportes de OWASP Dependency-Check.
                Identifica rápidamente librerías vulnerables en tus proyectos.
            </p>

        </div>

        <p class="text-gray-600 text-xs relative">
            &#x1F12F; {{ date('Y') }} OpenVulnTracker 
        </p>
    </div>

    {{-- Right login panel --}}
    <div class="flex-1 flex flex-col justify-center px-6 py-12 lg:px-12 bg-white">
        <div class="mx-auto w-full max-w-sm">

            {{-- Mobile logo --}}
            <div class="lg:hidden flex items-center gap-3 mb-8">
                <img src="{{ asset('logo.png') }}" alt="OpenVulnTracker" class="h-14 w-auto object-contain">
            </div>

            <h2 class="text-2xl font-bold text-gray-900 mb-1">Iniciar Sesión</h2>
            <p class="text-gray-500 text-sm mb-8">Accede a tu panel de vulnerabilidades</p>

            {{-- Session status --}}
            @if(session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-4">
                {{ session('status') }}
            </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Correo electrónico
                    </label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}"
                           required autofocus autocomplete="username"
                           class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none transition-shadow
                                  {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }}"
                           placeholder="admin@vulntracker.local">
                    @error('email')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="block text-sm font-medium text-gray-700">Contraseña</label>
                        @if(Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-red-600 hover:underline">
                            ¿Olvidaste tu contraseña?
                        </a>
                        @endif
                    </div>
                    <input id="password" type="password" name="password"
                           required autocomplete="current-password"
                           class="w-full px-4 py-3 border rounded-xl text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent outline-none transition-shadow
                                  {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-200 bg-gray-50 focus:bg-white' }}"
                           placeholder="••••••••">
                    @error('password')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-2">
                    <input id="remember_me" type="checkbox" name="remember"
                           class="w-4 h-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                    <label for="remember_me" class="text-sm text-gray-600">Recordarme</label>
                </div>

                <button type="submit"
                        class="w-full py-3 px-4 bg-red-600 hover:bg-red-700 active:bg-red-800 text-white font-semibold rounded-xl transition-colors shadow-sm shadow-red-200 text-sm">
                    Iniciar Sesión
                </button>
            </form>

            <p class="text-center text-xs text-gray-400 mt-8">
                Acceso restringido. Solo usuarios autorizados.
            </p>
        </div>
    </div>

</div>

</body>
</html>
