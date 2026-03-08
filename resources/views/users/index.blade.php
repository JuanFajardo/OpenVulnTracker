@extends('layouts.app')

@section('title', 'User Management')
@section('subtitle', 'Manage access to OpenVulnTracker')

@section('header-actions')
    <a href="{{ route('users.create') }}"
       class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        New User
    </a>
@endsection

@section('content')
<div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-left border-b border-gray-100">
                    <th class="px-6 py-3 font-semibold text-gray-600">User</th>
                    <th class="px-4 py-3 font-semibold text-gray-600">Email</th>
                    <th class="px-4 py-3 font-semibold text-gray-600">Role</th>
                    <th class="px-4 py-3 font-semibold text-gray-600">Joined</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($users as $user)
                <tr class="hover:bg-gray-50 transition-colors {{ $user->id === auth()->id() ? 'bg-blue-50/50' : '' }}">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-gradient-to-br from-gray-600 to-gray-900 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                                @if($user->id === auth()->id())
                                    <span class="text-xs text-blue-500">(you)</span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-gray-600">{{ $user->email }}</td>
                    <td class="px-4 py-4">
                        @php
                            $roleColors = [
                                'admin'   => 'bg-red-100 text-red-700',
                                'analyst' => 'bg-blue-100 text-blue-700',
                                'viewer'  => 'bg-gray-100 text-gray-600',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-1 text-xs font-semibold rounded-full {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-gray-500 text-sm">{{ $user->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2 justify-end">
                            <a href="{{ route('users.edit', $user) }}"
                               class="px-3 py-1.5 text-xs border border-gray-200 text-gray-700 hover:bg-gray-100 rounded-lg transition-colors">
                                Edit
                            </a>
                            @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  onsubmit="return confirm('Delete {{ $user->name }}?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="px-3 py-1.5 text-xs bg-red-50 border border-red-100 text-red-600 hover:bg-red-100 rounded-lg transition-colors">
                                    Delete
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
    @if($users->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
