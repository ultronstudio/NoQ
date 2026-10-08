<div class="modal fade" id="queueSettingsModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog">
        <div class="modal-content">
            <form wire:submit.prevent="save">
                <div class="modal-header">
                    <h2 class="modal-title h5 mb-0">
                        Upravit frontu
                    </h2>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="queueSettingsName" class="form-label">
                            Název
                        </label>

                        <input type="text" class="form-control" id="queueSettingsName" wire:model="name">

                        @error('name')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="queueSettingsDescription" class="form-label">
                            Popis <span class="text-body-secondary">(nepovinné)</span>
                        </label>

                        <textarea class="form-control" id="queueSettingsDescription" rows="3" wire:model="description"></textarea>

                        @error('description')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-0">
                        <label for="queueSettingsMaxWaiting" class="form-label">
                            Maximální počet čekajících <span class="text-body-secondary">(nepovinné)</span>
                        </label>

                        <input type="number" min="1" class="form-control" id="queueSettingsMaxWaiting"
                            wire:model="maxWaiting" placeholder="Bez omezení">

                        <div class="form-text">
                            Nad tento počet čekajících se noví návštěvníci už do fronty nedostanou.
                        </div>

                        @error('maxWaiting')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        Zrušit
                    </button>

                    <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
                        Uložit změny
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
