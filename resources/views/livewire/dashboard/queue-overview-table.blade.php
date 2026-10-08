<div wire:poll.10s.visible>
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 mb-0">
                    Přehled front
                </h2>

                @can('create', \App\Models\VirtualQueue::class)
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#createQueueModal">
                        Nová fronta
                    </button>
                @endcan
            </div>

            @if ($allQueues->isEmpty())
                <p class="text-body-secondary mb-0">
                    Zatím nemáte vytvořenou žádnou frontu.
                </p>
            @else
                <div class="table-responsive">
                    <table class="table queue-table align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">Fronta</th>
                                <th scope="col">Stav</th>
                                <th scope="col">Čeká</th>
                                <th scope="col">Obslužná místa</th>
                                <th scope="col" class="text-end">Akce</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($allQueues as $queue)
                                @php $session = $queue->activeSession; @endphp

                                <tr wire:key="queue-{{ $queue->id }}">
                                    <th scope="row">
                                        <a class="text-decoration-none" href="{{ Route::has('queue.show') ? route('queue.show', $queue) : '#' }}">
                                            {{ $queue->name }}
                                        </a>
                                    </th>

                                    <td>
                                        @if ($session)
                                            @switch($session->status)
                                                @case(\App\Enums\QueueSessionStatus::Open)
                                                    <span class="queue-status queue-status--open">
                                                        Otevřená
                                                    </span>
                                                @break

                                                @case(\App\Enums\QueueSessionStatus::Paused)
                                                    <span class="queue-status queue-status--paused">
                                                        Pozastavená
                                                    </span>
                                                @break

                                                @default
                                                    <span class="queue-status queue-status--closed">
                                                        Neaktivní
                                                    </span>
                                            @endswitch
                                        @else
                                            <span class="queue-status queue-status--closed">
                                                Zavřená
                                            </span>
                                        @endif
                                    </td>

                                    <td>
                                        {{ $session?->waiting_count ?? 0 }}
                                    </td>

                                    <td>
                                        {{ $queue->active_service_points_count ?? 0 }}
                                    </td>

                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                        @can('update', $queue)
                                            @if (! $session)
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    wire:click="openSession({{ $queue->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="openSession({{ $queue->id }})">
                                                    <span wire:loading.remove wire:target="openSession({{ $queue->id }})">
                                                        Otevřít
                                                    </span>
                                                    <span wire:loading wire:target="openSession({{ $queue->id }})">
                                                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                    </span>
                                                </button>
                                            @elseif ($session->status === \App\Enums\QueueSessionStatus::Open)
                                                <button type="button" class="btn btn-sm btn-outline-warning"
                                                    wire:click="pauseSession({{ $session->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="pauseSession({{ $session->id }}),closeSession({{ $session->id }})">
                                                    <span wire:loading.remove wire:target="pauseSession({{ $session->id }})">
                                                        Pozastavit
                                                    </span>
                                                    <span wire:loading wire:target="pauseSession({{ $session->id }})">
                                                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                    </span>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:loading.attr="disabled"
                                                    wire:target="closeSession({{ $session->id }}),pauseSession({{ $session->id }})"
                                                    x-on:click="Swal.fire({
                                                        title: 'Zavřít frontu?',
                                                        text: @js("Fronta „{$queue->name}“ bude trvale uzavřena a dokud ji znovu neotevřete, nepůjde do ní přibírat nové návštěvníky."),
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonText: 'Zavřít',
                                                        cancelButtonText: 'Zrušit',
                                                        confirmButtonColor: '#dc3545',
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.closeSession({{ $session->id }})
                                                        }
                                                    })">
                                                    <span wire:loading.remove wire:target="closeSession({{ $session->id }})">
                                                        Zavřít
                                                    </span>
                                                    <span wire:loading wire:target="closeSession({{ $session->id }})">
                                                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                    </span>
                                                </button>
                                            @elseif ($session->status === \App\Enums\QueueSessionStatus::Paused)
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                    wire:click="resumeSession({{ $session->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="resumeSession({{ $session->id }}),closeSession({{ $session->id }})">
                                                    <span wire:loading.remove wire:target="resumeSession({{ $session->id }})">
                                                        Obnovit
                                                    </span>
                                                    <span wire:loading wire:target="resumeSession({{ $session->id }})">
                                                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                    </span>
                                                </button>

                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:loading.attr="disabled"
                                                    wire:target="closeSession({{ $session->id }}),resumeSession({{ $session->id }})"
                                                    x-on:click="Swal.fire({
                                                        title: 'Zavřít frontu?',
                                                        text: @js("Fronta „{$queue->name}“ bude trvale uzavřena a dokud ji znovu neotevřete, nepůjde do ní přibírat nové návštěvníky."),
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonText: 'Zavřít',
                                                        cancelButtonText: 'Zrušit',
                                                        confirmButtonColor: '#dc3545',
                                                    }).then((result) => {
                                                        if (result.isConfirmed) {
                                                            $wire.closeSession({{ $session->id }})
                                                        }
                                                    })">
                                                    <span wire:loading.remove wire:target="closeSession({{ $session->id }})">
                                                        Zavřít
                                                    </span>
                                                    <span wire:loading wire:target="closeSession({{ $session->id }})">
                                                        <span class="spinner-border spinner-border-sm" aria-hidden="true"></span>
                                                    </span>
                                                </button>
                                            @endif
                                        @endcan
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    @can('create', \App\Models\VirtualQueue::class)
        <div class="modal fade" id="createQueueModal" tabindex="-1" aria-hidden="true" wire:ignore.self>
            <div class="modal-dialog">
                <div class="modal-content">
                    <form wire:submit.prevent="createQueue">
                        <div class="modal-header">
                            <h2 class="modal-title h5 mb-0">
                                Nová fronta
                            </h2>

                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zavřít"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="createQueueName" class="form-label">
                                    Název
                                </label>

                                <input type="text" class="form-control" id="createQueueName" wire:model="name" autofocus>

                                @error('name')
                                    <div class="text-danger small mt-1">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="createQueueDescription" class="form-label">
                                    Popis <span class="text-body-secondary">(nepovinné)</span>
                                </label>

                                <textarea class="form-control" id="createQueueDescription" rows="3" wire:model="description"></textarea>

                                @error('description')
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

                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="createQueue">
                                Vytvořit frontu
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcan
</div>
