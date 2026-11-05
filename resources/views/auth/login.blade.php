@extends('layouts.auth')

@section('title', 'Přihlášení')

@section('auth-title')
    <livewire:auth.time-welcome />
@endsection

@section('auth-info')
    {{--
        Tady není nic k vidění.
    --}}
@endsection

@section('content')
    <livewire:auth.login />
@endsection
