{{-- Compatibility wrapper for Breeze <x-app-layout> component --}}
@extends('layouts.app')

@section('content')
    {{ $slot }}
@endsection
