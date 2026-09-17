<div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="mb-4">
                    <h1 class="h4 mb-1">Potvrzení hesla</h1>

                    <p class="text-body-secondary mb-0">
                        Pro pokračování potvrďte své heslo.
                    </p>
                </div>

                <form wire:submit="confirm">
                    <div class="mb-4">
                        <label for="password" class="form-label">
                            Heslo
                        </label>

                        <input
                            wire:model="password"
                            type="password"
                            id="password"
                            class="form-control @error('password') is-invalid @enderror"
                            autocomplete="current-password"
                            autofocus
                        >

                        @error('password')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                        wire:loading.attr="disabled"
                        wire:target="confirm"
                    >
                        <span wire:loading.remove wire:target="confirm">
                            Potvrdit
                        </span>

                        <span wire:loading wire:target="confirm">
                            Ověřování…
                        </span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
