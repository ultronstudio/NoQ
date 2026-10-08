@php
    $statTiles = [
        [
            'label' => 'Čeká',
            'value' => $virtualQueue->max_waiting
                ? "{$stats['waiting']} / {$virtualQueue->max_waiting}"
                : $stats['waiting'],
        ],
        ['label' => 'V obsluze', 'value' => $stats['called']],
        ['label' => 'Vydáno lístků', 'value' => $stats['issued']],
        ['label' => 'Dokončeno', 'value' => $stats['completed']],
        ['label' => 'Zrušeno', 'value' => $stats['cancelled']],
        ['label' => 'Nedostavilo se', 'value' => $stats['no_show']],
    ];
@endphp

<div wire:poll.4s.visible>
    {{-- Session status + lifecycle controls --}}
    <div class="card mb-4">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="d-flex align-items-center gap-3 flex-wrap">
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

                    <span class="text-body-secondary small">
                        Otevřeno {{ $session->opened_at->format('j. n. Y H:i') }}, {{ $session->openedBy->name }}
                    </span>
                @else
                    <span class="queue-status queue-status--closed">
                        Zavřená
                    </span>
                @endif
            </div>

            <div class="d-flex gap-2">
                @if ($session && $canCallNext)
                    <button type="button" class="btn btn-primary"
                        wire:click="callNext"
                        wire:loading.attr="disabled"
                        wire:target="callNext"
                        @disabled($stats['waiting'] === 0)>
                        Zavolat dalšího
                    </button>
                @endif

                @can('update', $virtualQueue)
                    @if (! $session)
                        <button type="button" class="btn btn-success"
                            wire:click="openSession"
                            wire:loading.attr="disabled"
                            wire:target="openSession">
                            Otevřít frontu
                        </button>
                    @elseif ($session->status === \App\Enums\QueueSessionStatus::Open)
                        <button type="button" class="btn btn-outline-warning"
                            wire:click="pauseSession"
                            wire:loading.attr="disabled"
                            wire:target="pauseSession,closeSession">
                            Pozastavit
                        </button>

                        <button type="button" class="btn btn-outline-danger"
                            wire:loading.attr="disabled"
                            wire:target="closeSession,pauseSession"
                            x-on:click="Swal.fire({
                                title: 'Zavřít frontu?',
                                text: 'Fronta bude trvale uzavřena a dokud ji znovu neotevřete, nepůjde do ní přibírat nové návštěvníky.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Zavřít',
                                cancelButtonText: 'Zrušit',
                                confirmButtonColor: '#dc3545',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $wire.closeSession()
                                }
                            })">
                            Zavřít
                        </button>
                    @elseif ($session->status === \App\Enums\QueueSessionStatus::Paused)
                        <button type="button" class="btn btn-success"
                            wire:click="resumeSession"
                            wire:loading.attr="disabled"
                            wire:target="resumeSession,closeSession">
                            Obnovit
                        </button>

                        <button type="button" class="btn btn-outline-danger"
                            wire:loading.attr="disabled"
                            wire:target="closeSession,resumeSession"
                            x-on:click="Swal.fire({
                                title: 'Zavřít frontu?',
                                text: 'Fronta bude trvale uzavřena a dokud ji znovu neotevřete, nepůjde do ní přibírat nové návštěvníky.',
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonText: 'Zavřít',
                                cancelButtonText: 'Zrušit',
                                confirmButtonColor: '#dc3545',
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    $wire.closeSession()
                                }
                            })">
                            Zavřít
                        </button>
                    @endif
                @endcan
            </div>
        </div>
    </div>

    {{-- Live stats --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 mb-4">
        @foreach ($statTiles as $tile)
            <div class="col">
                <div class="card text-center h-100">
                    <div class="card-body py-3">
                        <div class="h3 mb-0">
                            {{ $tile['value'] }}
                        </div>
                        <div class="small text-body-secondary">
                            {{ $tile['label'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4">
        {{-- Service points --}}
        <div class="col-lg-5">
            <div class="card mb-4 mb-lg-0">
                <div class="card-header bg-transparent">
                    <h2 class="h6 mb-0">
                        Obslužná místa
                    </h2>
                </div>

                <ul class="list-group list-group-flush">
                    @forelse ($servicePoints as $servicePoint)
                        @php $busyEntry = $calledByServicePoint->get($servicePoint->id); @endphp

                        <li class="list-group-item d-flex justify-content-between align-items-center gap-2 flex-wrap"
                            wire:key="service-point-{{ $servicePoint->id }}">
                            <div>
                                <div class="fw-semibold">
                                    {{ $servicePoint->name }}
                                </div>
                                <div class="small text-body-secondary">
                                    @if (! $servicePoint->is_active)
                                        Neaktivní
                                    @elseif ($busyEntry)
                                        Obsluhuje lístek č. {{ $busyEntry->number }}
                                    @else
                                        Volno
                                    @endif
                                </div>
                            </div>

                            @can('manage', $servicePoint)
                                <div class="form-check form-switch mb-0" title="Aktivní obslužné místo">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        wire:click="toggleServicePoint({{ $servicePoint->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="toggleServicePoint({{ $servicePoint->id }})"
                                        @checked($servicePoint->is_active)>
                                </div>
                            @endcan
                        </li>
                    @empty
                        <li class="list-group-item text-body-secondary">
                            Zatím žádná obslužná místa.
                        </li>
                    @endforelse
                </ul>

                @can('update', $virtualQueue)
                    <div class="card-body">
                        <form wire:submit.prevent="addServicePoint" class="d-flex gap-2">
                            <input type="text" class="form-control form-control-sm"
                                placeholder="Název nového místa (např. Přepážka 3)"
                                wire:model="newServicePointName">

                            <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap"
                                wire:loading.attr="disabled"
                                wire:target="addServicePoint">
                                Přidat
                            </button>
                        </form>

                        @error('newServicePointName')
                            <div class="text-danger small mt-1">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
                @endcan
            </div>
        </div>

        <div class="col-lg-7">
            {{-- Currently being served --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 mb-3">
                        Právě se obsluhuje
                    </h2>

                    @if ($calledEntries->isEmpty())
                        <p class="text-body-secondary mb-0">
                            Nikdo není momentálně obsluhován.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table queue-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Číslo</th>
                                        <th scope="col">Obslužné místo</th>
                                        <th scope="col" class="text-end">Akce</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($calledEntries as $entry)
                                        <tr wire:key="called-{{ $entry->id }}">
                                            <th scope="row">
                                                #{{ $entry->number }}
                                            </th>

                                            <td>
                                                {{ $entry->servicePoint?->name ?? '—' }}
                                            </td>

                                            <td class="text-end">
                                                @can('manage', $entry)
                                                    <div class="d-inline-flex gap-2">
                                                        <button type="button" class="btn btn-sm btn-success"
                                                            wire:click="completeEntry({{ $entry->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="completeEntry({{ $entry->id }}),cancelEntry({{ $entry->id }}),noShowEntry({{ $entry->id }})">
                                                            Dokončit
                                                        </button>

                                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                            wire:click="noShowEntry({{ $entry->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="completeEntry({{ $entry->id }}),cancelEntry({{ $entry->id }}),noShowEntry({{ $entry->id }})">
                                                            Nedostavil se
                                                        </button>

                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            wire:click="cancelEntry({{ $entry->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="completeEntry({{ $entry->id }}),cancelEntry({{ $entry->id }}),noShowEntry({{ $entry->id }})">
                                                            Zrušit
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="text-body-secondary">—</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Waiting list --}}
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h2 class="h6 mb-0">
                            Čekající fronta
                        </h2>

                        <span class="badge text-bg-secondary">
                            {{ $stats['waiting'] }}
                        </span>
                    </div>

                    @if ($waitingEntries->isEmpty())
                        <p class="text-body-secondary mb-0">
                            Nikdo nečeká.
                        </p>
                    @else
                        <div class="table-responsive">
                            <table class="table queue-table align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th scope="col">Pozice</th>
                                        <th scope="col">Číslo</th>
                                        <th scope="col">Čeká od</th>
                                        <th scope="col" class="text-end">Akce</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($waitingEntries as $entry)
                                        <tr wire:key="waiting-{{ $entry->id }}">
                                            <td>
                                                {{ $loop->iteration }}
                                            </td>

                                            <th scope="row">
                                                #{{ $entry->number }}
                                            </th>

                                            <td>
                                                {{ $entry->created_at->diffForHumans() }}
                                            </td>

                                            <td class="text-end">
                                                @can('manage', $entry)
                                                    <div class="d-inline-flex gap-2">
                                                        <button type="button" class="btn btn-sm btn-outline-warning"
                                                            wire:click="noShowEntry({{ $entry->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="noShowEntry({{ $entry->id }}),cancelEntry({{ $entry->id }})">
                                                            Nedostavil se
                                                        </button>

                                                        <button type="button" class="btn btn-sm btn-outline-danger"
                                                            wire:click="cancelEntry({{ $entry->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="noShowEntry({{ $entry->id }}),cancelEntry({{ $entry->id }})">
                                                            Zrušit
                                                        </button>
                                                    </div>
                                                @else
                                                    <span class="text-body-secondary">—</span>
                                                @endcan
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if ($stats['waiting'] > $waitingEntries->count())
                            <p class="text-body-secondary small mt-2 mb-0">
                                Zobrazeno prvních {{ $waitingEntries->count() }} z {{ $stats['waiting'] }} čekajících.
                            </p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
