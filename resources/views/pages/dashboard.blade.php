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

        <div class="card">
            <div class="card-body">
                <h2 class="h5">
                    NoQ
                </h2>

                <p class="mb-0">
                    Zde bude přehled front, akcí a aktuálního provozu.
                </p>
            </div>
        </div>
    </div>
@endsection
