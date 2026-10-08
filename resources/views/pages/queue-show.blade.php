@extends('layouts.app')

@section('title', $virtualQueue->name)

@section('content')
    <div class="container py-4">
        <div class="mb-4 d-flex justify-content-between align-items-start gap-3 flex-wrap">
            <div>
                <a href="{{ route('dashboard') }}" class="link-secondary text-decoration-none small">
                    &larr; Administrace
                </a>

                <h1 class="h2 mb-1 mt-2">
                    {{ $virtualQueue->name }}
                </h1>

                @if ($virtualQueue->description)
                    <p class="text-body-secondary mb-0">
                        {{ $virtualQueue->description }}
                    </p>
                @endif
            </div>

            @can('update', $virtualQueue)
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#queueSettingsModal">
                    Upravit frontu
                </button>
            @endcan
        </div>

        <livewire:queue.show :virtual-queue="$virtualQueue" />

        @can('update', $virtualQueue)
            <livewire:queue.settings :virtual-queue="$virtualQueue" />
            <div class="card mt-4" x-data="{ copied: false }">
                <div class="card-body">
                    <h2 class="h6 mb-3">
                        Veřejný vstup do fronty
                    </h2>

                    <div class="d-flex flex-wrap align-items-center gap-4">
                        <canvas x-ref="qr"
                            x-init="QRCode.toCanvas($refs.qr, @js(route('queue.public', $virtualQueue)), { width: 160, margin: 1 })"></canvas>

                        <div class="flex-grow-1" style="min-width: 240px;">
                            <p class="text-body-secondary small mb-2">
                                Návštěvníci naskenováním QR kódu nebo otevřením odkazu vstoupí do fronty bez nutnosti účtu.
                            </p>

                            <div class="input-group input-group-sm" style="max-width: 420px;">
                                <input type="text" class="form-control" readonly
                                    value="{{ route('queue.public', $virtualQueue) }}"
                                    x-ref="link">

                                <button type="button" class="btn btn-outline-secondary"
                                    x-on:click="navigator.clipboard.writeText($refs.link.value); copied = true; setTimeout(() => copied = false, 1500)">
                                    <span x-show="!copied">Kopírovat</span>
                                    <span x-show="copied">Zkopírováno</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <livewire:queue.team-access :virtual-queue="$virtualQueue" />
            </div>
        @endcan
    </div>
@endsection
