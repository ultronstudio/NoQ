<nav class="navbar navbar-expand-lg bg-white border-bottom" data-bs-theme="light">
    <div class="container">
        <a href="{{ route('home') }}" class="navbar-brand p-0">
            @logo('auto', ['width' => 100])
        </a>

        <div class="ms-auto d-flex align-items-center gap-2">
            @auth
                <a
                    href="{{ route('dashboard') }}"
                    class="btn btn-outline-primary"
                >
                    Administrace
                </a>

                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf

                    <button
                        type="submit"
                        class="btn btn-outline-secondary"
                    >
                        Odhlásit se
                    </button>
                </form>
            @else
                <a
                    href="{{ route('login') }}"
                    class="btn btn-primary"
                >
                    Přihlásit se
                </a>
            @endauth
        </div>
    </div>
</nav>
