<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>
        @hasSection('title')
            @yield('title') | NoQ
        @else
            NoQ
        @endif
    </title>

    @vite([
        'resources/scss/app.scss',
        'resources/js/app.js',
    ])

    @stack('styles')
    @livewireStyles
</head>

<body>
    @include('components.navbar')

    <main>
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
    @stack('scripts')
</body>
</html>
