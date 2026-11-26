<nav class="navbar navbar-expand-lg bg-white border-bottom" data-bs-theme="light">
    <div class="container">
        <a href="{{ route('home') }}" class="navbar-brand p-0">
            @logo('auto', ['width' => 100])
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown"
            aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNavDropdown">
            <ul class="navbar-nav">
                @auth
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'text-primary' : '' }}">
                            Administrace
                        </a>
                    </li>
                    @can('viewAny', \App\Models\User::class)
                        <li class="nav-item">
                            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'text-primary' : '' }}">
                                Uživatelé
                            </a>
                        </li>
                    @endcan
                @endauth
            </ul>

            <div class="ms-auto d-flex align-items-center gap-2">
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf

                        <button type="submit" class="btn btn-outline-secondary">
                            Odhlásit se
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">
                        Přihlásit se
                    </a>
                @endauth
            </div>
        </div>
    </div>
</nav>
