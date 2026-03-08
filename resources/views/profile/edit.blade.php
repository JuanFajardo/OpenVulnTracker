@extends('layouts.app')

@section('title', 'Profile')
@section('subtitle', 'Manage your account information and security settings')

@section('content')
<div class="max-w-2xl space-y-6">

    {{-- Update profile info --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Profile Information</h3>
        @include('profile.partials.update-profile-information-form')
    </div>

    {{-- Update password --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Update Password</h3>
        @include('profile.partials.update-password-form')
    </div>

    {{-- Delete account --}}
    <div class="bg-white rounded-xl border border-red-100 shadow-sm p-6">
        <h3 class="font-semibold text-red-700 mb-4">Delete Account</h3>
        @include('profile.partials.delete-user-form')
    </div>

</div>
@endsection
