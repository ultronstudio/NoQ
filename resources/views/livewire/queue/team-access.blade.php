<div class="card">
    <div class="card-header bg-transparent">
        <h2 class="h6 mb-0">
            Přístupy k frontě
        </h2>
    </div>

    <ul class="list-group list-group-flush">
        @forelse ($assignments as $assignment)
            <li class="list-group-item d-flex justify-content-between align-items-center gap-2 flex-wrap"
                wire:key="assignment-{{ $assignment->id }}">
                <div>
                    <div class="fw-semibold">
                        {{ $assignment->user->name }}
                    </div>
                    <div class="small text-body-secondary">
                        {{ $assignment->user->email }} · {{ $assignment->role->label() }}
                        @if ($assignment->servicePoint)
                            · {{ $assignment->servicePoint->name }}
                        @endif
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-outline-danger"
                    wire:click="revoke({{ $assignment->id }})"
                    wire:loading.attr="disabled"
                    wire:target="revoke({{ $assignment->id }})">
                    Odebrat
                </button>
            </li>
        @empty
            <li class="list-group-item text-body-secondary">
                Zatím nikomu nebyl přiřazen přístup.
            </li>
        @endforelse
    </ul>

    <div class="card-body">
        <form wire:submit.prevent="assign" class="row g-2 align-items-end">
            <div class="col-12 col-md">
                <label class="form-label small mb-1">
                    E-mail uživatele
                </label>

                <input type="email" class="form-control form-control-sm"
                    wire:model="email"
                    placeholder="osoba@example.com">

                @error('email')
                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            @if ($canAssignSupervisor)
                <div class="col-12 col-md-auto">
                    <label class="form-label small mb-1">
                        Role
                    </label>

                    <select class="form-select form-select-sm" wire:model.live="role">
                        <option value="{{ \App\Enums\Role::ServicePointWorker->value }}">
                            Pracovník přepážky
                        </option>
                        <option value="{{ \App\Enums\Role::QueueSupervisor->value }}">
                            Vedoucí operátor
                        </option>
                    </select>
                </div>
            @endif

            @if ($role === \App\Enums\Role::ServicePointWorker->value)
                <div class="col-12 col-md-auto">
                    <label class="form-label small mb-1">
                        Obslužné místo
                    </label>

                    <select class="form-select form-select-sm" wire:model="servicePointId">
                        <option value="">— vyberte —</option>

                        @foreach ($servicePoints as $servicePoint)
                            <option value="{{ $servicePoint->id }}">
                                {{ $servicePoint->name }}
                            </option>
                        @endforeach
                    </select>

                    @error('servicePointId')
                        <div class="text-danger small mt-1">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
            @endif

            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary"
                    wire:loading.attr="disabled"
                    wire:target="assign">
                    Přidat přístup
                </button>
            </div>
        </form>
    </div>
</div>
