<div class="auth-form">
    <form wire:submit="login" novalidate>
        <div class="mb-4">
            <label for="email" class="form-label">
                E-mail
            </label>

            <input
                wire:model.blur="email"
                type="email"
                id="email"
                class="form-control form-control-lg @error('email') is-invalid @enderror"
                autocomplete="email"
                autofocus
            >

            @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">
                Heslo
            </label>

            <input
                wire:model="password"
                type="password"
                id="password"
                class="form-control form-control-lg @error('password') is-invalid @enderror"
                autocomplete="current-password"
            >

            @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="form-check mb-4">
            <input
                wire:model="remember"
                type="checkbox"
                id="remember"
                class="form-check-input"
            >

            <label for="remember" class="form-check-label">
                Zapamatovat přihlášení
            </label>
        </div>

        <button
            type="submit"
            class="btn btn-primary btn-lg w-100"
            wire:loading.attr="disabled"
            wire:target="login"
        >
            <span wire:loading.remove wire:target="login">
                Přihlásit se
            </span>

            <span wire:loading wire:target="login">
                Přihlašování…
            </span>
        </button>
    </form>

    <p class="auth-form__access">
        Přístupové údaje poskytuje správce systému.
    </p>
</div>
