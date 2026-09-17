<!doctype html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Přihlášení') | NoQ</title>

    @vite([
        'resources/scss/app.scss',
        'resources/js/app.js',
    ])

    @livewireStyles
    @stack('styles')
</head>

<body class="auth-page">
    <main class="auth-layout">
        <aside class="auth-side">
            <div class="auth-side__inner">
                <a href="{{ route('home') }}" class="auth-logo">
                    <img
                        src="https://placehold.co/180x56/FFFFFF/00B7EF?text=LOGO"
                        alt="NoQ"
                        width="180"
                        height="56"
                    >
                </a>

                <div class="auth-side__content">
                    <h1>
                        @yield('auth-title', 'Administrace NoQ')
                    </h1>

                    <p>
                        @yield(
                            'auth-description',
                            'Přihlášení je určeno správcům a pracovníkům systému.'
                        )
                    </p>

                    @hasSection('auth-info')
                        <div class="auth-side__info">
                            @yield('auth-info')
                        </div>
                    @endif
                </div>

                <div class="auth-side__footer">
                    <a href="{{ route('home') }}">
                        Zpět na hlavní stránku
                    </a>

                    <span>
                        &copy; {{ date('Y') }} <a href="https://petrvurm.cz?utm_source=noq" target="_blank" rel="noopener">Petr Vurm</a>
                    </span>
                </div>
            </div>
        </aside>

        <section class="auth-main">
            <div class="auth-main__inner">
                @yield('content')
            </div>
        </section>
    </main>

    @livewireScripts
    @stack('scripts')
</body>
</html>
