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

<body class="public-page">
    <header class="public-header">
        <div class="container">
            <div class="public-header__inner">
                <a href="{{ route('home') }}" class="public-logo">
                    <img
                        src="https://placehold.co/180x56/FFFFFF/00B7EF?text=LOGO"
                        alt="NoQ"
                        width="180"
                        height="56"
                    >
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
