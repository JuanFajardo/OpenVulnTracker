<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OpenVulnTracker') }} - @yield('title', 'Dashboard')</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased">

<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <div class="w-64 bg-gray-900 flex flex-col flex-shrink-0 fixed inset-y-0 left-0 z-50">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-700">
            <img src="{{ asset('logo.png') }}" alt="OpenVulnTracker" class="h-24 w-auto object-contain">
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
            <p class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wider">Main</p>

            <a href="{{ route('projects.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('projects.*', 'dashboard') ? 'bg-red-600 text-white shadow-sm' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                Projects
            </a>

            <a href="{{ route('import.create') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('import.*') ? 'bg-red-600 text-white shadow-sm' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                Import Report
            </a>

            @if(auth()->user()->role === 'admin')
            <p class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</p>
            <a href="{{ route('users.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('users.*') ? 'bg-red-600 text-white shadow-sm' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                User Management
            </a>
            <a href="{{ route('tokens.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
               {{ request()->routeIs('tokens.*') ? 'bg-red-600 text-white shadow-sm' : 'text-gray-400 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                </svg>
                Autenticación
            </a>
            @endif
        </nav>

        {{-- User footer --}}
        <div class="px-4 py-4 border-t border-gray-700">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center text-white text-sm font-bold shadow">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                    <p class="text-gray-400 text-xs capitalize">{{ auth()->user()->role }}</p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('profile.edit') }}"
                   class="flex-1 text-center px-2 py-1.5 text-xs text-gray-400 hover:text-white bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">
                    Profile
                </a>
                <form method="POST" action="{{ route('logout') }}" class="flex-1">
                    @csrf
                    <button type="submit"
                            class="w-full px-2 py-1.5 text-xs text-gray-400 hover:text-red-400 bg-gray-800 hover:bg-gray-700 rounded-lg transition-colors">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Main content (offset for fixed sidebar) --}}
    <div class="flex-1 flex flex-col ml-64 min-h-screen">
        {{-- Top bar --}}
        <header class="bg-white border-b border-gray-200 px-8 py-4 sticky top-0 z-40 shadow-sm">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div>
                        <h1 class="text-xl font-bold text-gray-900">@yield('title', 'Dashboard')</h1>
                        @hasSection('subtitle')
                            <p class="text-sm text-gray-500 mt-0.5">@yield('subtitle')</p>
                        @endif
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    @yield('header-actions')
                </div>
            </div>
        </header>

        {{-- Flash messages --}}
        @if(session()->hasAny(['success', 'error', 'warning', 'info']))
        <div class="px-8 pt-4 space-y-2">
            @if(session('success'))
            <div class="border rounded-lg px-4 py-3 text-sm bg-green-50 border-green-200 text-green-800 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                <span>{{ session('success') }}</span>
            </div>
            @endif
            @if(session('error'))
            <div class="border rounded-lg px-4 py-3 text-sm bg-red-50 border-red-200 text-red-800 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                <span>{{ session('error') }}</span>
            </div>
            @endif
            @if(session('warning'))
            <div class="border rounded-lg px-4 py-3 text-sm bg-yellow-50 border-yellow-200 text-yellow-800 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>{{ session('warning') }}</span>
            </div>
            @endif
            @if(session('info'))
            <div class="border rounded-lg px-4 py-3 text-sm bg-blue-50 border-blue-200 text-blue-800 flex items-start gap-2">
                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>{{ session('info') }}</span>
            </div>
            @endif
        </div>
        @endif

        {{-- Page content --}}
        <main class="flex-1 px-8 py-6">
            @yield('content')
        </main>

        <footer class="px-8 py-4 text-center text-xs text-gray-400 border-t border-gray-100">
            OpenVulnTracker &mdash; OWASP Dependency-Check Dashboard
        </footer>
    </div>
</div>

</body>
</html>
