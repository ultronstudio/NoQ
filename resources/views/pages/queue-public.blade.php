@extends('layouts.app')

@section('title', $virtualQueue->name.' – vstup do fronty')

@section('content')
    <div class="container py-4">
        <livewire:public.queue-ticket :virtual-queue="$virtualQueue" />
    </div>
@endsection
