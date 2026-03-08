@extends('layouts.app')

@section('title', 'Create User')

@section('header-actions')
    <a href="{{ route('users.index') }}"
       class="inline-flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-700 text-sm rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </a>
@endsection

@section('content')
<div class="max-w-lg">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <form method="POST" action="{{ route('users.store') }}" class="space-y-5">
            @csrf
            @include('users._form', ['user' => null])
            <div class="pt-2">
                <button type="submit"
                        class="w-full px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
