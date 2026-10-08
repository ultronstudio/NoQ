<!doctype html>
<html lang="{{ config('metadata.language') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @include('partials.metadata')

    @vite([
        'resources/scss/app.scss',
        'resources/js/app.js',
    ])

    @stack('styles')
    @livewireStyles
</head>

<body>
    <div
        x-data="{ online: navigator.onLine }"
        x-on:online.window="online = true"
        x-on:offline.window="online = false"
        x-show="!online"
        x-cloak
        x-transition
        class="alert alert-warning text-center mb-0 rounded-0 py-2 small"
        role="status"
        aria-live="polite"
    >
        Jste offline — změny se neuloží, dokud se připojení neobnoví.
    </div>

    @include('components.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
    @stack('scripts')
</body>
</html>
