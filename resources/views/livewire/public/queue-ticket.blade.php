<div wire:poll.4s.visible class="d-flex flex-column align-items-center text-center gap-4 py-4">
    <div>
        <h1 class="h3 mb-1">
            {{ $virtualQueue->name }}
        </h1>

        @if ($virtualQueue->description)
            <p class="text-body-secondary mb-0">
                {{ $virtualQueue->description }}
            </p>
        @endif
    </div>

    @if (! $entry)
        <div class="card w-100" style="max-width: 420px;">
            <div class="card-body">
                @if ($session && $session->status === \App\Enums\QueueSessionStatus::Open)
                    <p class="mb-3">
                        Vstupem se zařadíte do pořadí. Nic nemusíte instalovat ani se registrovat.
                    </p>

                    <button type="button" class="btn btn-primary btn-lg w-100"
                        wire:click="join"
                        wire:loading.attr="disabled"
                        wire:target="join">
                        Vstoupit do fronty
                    </button>
                @elseif ($session && $session->status === \App\Enums\QueueSessionStatus::Paused)
                    <span class="queue-status queue-status--paused mb-2">
                        Pozastaveno
                    </span>

                    <p class="text-body-secondary mb-0">
                        Fronta je dočasně pozastavená, zkuste to prosím za chvíli.
                    </p>
                @else
                    <span class="queue-status queue-status--closed mb-2">
                        Zavřeno
                    </span>

                    <p class="text-body-secondary mb-0">
                        Fronta momentálně nepřijímá návštěvníky.
                    </p>
                @endif

                @error('join')
                    <div class="text-danger small mt-2">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    @else
        <div class="card w-100" style="max-width: 420px;">
            <div class="card-body">
                @switch($entry->status)
                    @case(\App\Enums\QueueEntryStatus::Waiting)
                        <div class="text-body-secondary small mb-1">
                            Vaše pořadové číslo
                        </div>

                        <div class="display-4 fw-bold mb-2">
                            #{{ $entry->number }}
                        </div>

                        <div class="mb-3">
                            Pozice ve frontě: <strong>{{ $position }}</strong>
                        </div>

                        <button type="button" class="btn btn-outline-danger"
                            wire:loading.attr="disabled"
                            wire:target="cancel"
                            x-on:click="Swal.fire({
                                title: 'Zrušit lístek?',
                                text: 'Ztratíte své místo ve frontě.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Zrušit lístek',
                                cancelButtonText: 'Zpět',
                                confirmButtonColor: '#dc3545',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $wire.cancel()
                                }
                            })">
                            Zrušit lístek
                        </button>
                    @break

                    @case(\App\Enums\QueueEntryStatus::Called)
                        <div class="text-body-secondary small mb-1">
                            Jste na řadě
                        </div>

                        <div class="display-4 fw-bold mb-2">
                            #{{ $entry->number }}
                        </div>

                        <div class="h5 mb-3">
                            Jděte prosím k: {{ $entry->servicePoint?->name ?? 'přepážce' }}
                        </div>

                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            wire:click="cancel"
                            wire:loading.attr="disabled"
                            wire:target="cancel">
                            Zrušit
                        </button>
                    @break

                    @case(\App\Enums\QueueEntryStatus::Completed)
                        <span class="queue-status queue-status--open mb-2">
                            Obslouženo
                        </span>

                        <p class="mb-3">
                            Děkujeme, váš lístek č. {{ $entry->number }} byl vyřízen.
                        </p>

                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            wire:click="cancel"
                            wire:loading.attr="disabled"
                            wire:target="cancel">
                            Zpět na vstup
                        </button>
                    @break

                    @default
                        <span class="queue-status queue-status--closed mb-2">
                            {{ $entry->status === \App\Enums\QueueEntryStatus::Cancelled ? 'Zrušeno' : 'Nedostavili jste se' }}
                        </span>

                        <p class="mb-3">
                            Lístek č. {{ $entry->number }}.
                        </p>

                        <button type="button" class="btn btn-outline-secondary btn-sm"
                            wire:click="cancel"
                            wire:loading.attr="disabled"
                            wire:target="cancel">
                            Zpět na vstup
                        </button>
                @endswitch
            </div>
        </div>
    @endif
</div>
