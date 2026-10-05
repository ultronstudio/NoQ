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

<body class="public-page">
    <header class="public-header" data-bs-theme="light">
        <div class="container">
            <div class="public-header__inner">
                <a href="{{ route('home') }}" class="public-logo">
                    @logo
                </a>

                @hasSection('header-content')
                    <div class="public-header__content">
                        @yield('header-content')
                    </div>
                @endif
            </div>
        </div>
    </header>

    <main class="public-main">
        @yield('content')
    </main>

    @include('partials.footer')

    @livewireScripts
    @stack('scripts')
</body>
</html>
