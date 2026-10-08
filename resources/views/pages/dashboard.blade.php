@extends('layouts.app')

@section('title', 'Administrace')

@section('content')
    <div class="container py-4">
        <div class="mb-4">
            <h1 class="h2 mb-1">
                Administrace
            </h1>

            <p class="text-body-secondary mb-0">
                Přihlášen jako {{ auth()->user()->name }}
            </p>
        </div>

        <livewire:dashboard.queue-overview-table />
    </div>
@endsection
