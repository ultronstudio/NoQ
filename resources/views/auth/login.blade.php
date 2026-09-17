@extends('layouts.auth')

@section('title', 'Přihlášení')

@section('auth-title', 'Přihlášení do NoQ')

@section(
    'auth-description',
    'Přihlášení je určeno správcům a pracovníkům systému.'
)

@section('auth-info')
    {{--
        Tady není nic k vidění.
    --}}
@endsection

@section('content')
    <livewire:auth.login />
@endsection
