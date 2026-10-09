<?php

namespace App\Livewire\Queue;

use App\Actions\Queue\CallNextEntry;
use App\Actions\Queue\CancelEntry;
use App\Actions\Queue\CloseQueueSession;
use App\Actions\Queue\CompleteEntry;
use App\Actions\Queue\MarkEntryNoShow;
use App\Actions\Queue\OpenQueueSession;
use App\Actions\Queue\PauseQueueSession;
use App\Actions\Queue\ResumeQueueSession;
use App\Actions\Queue\SelectServicePointToCall;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Exceptions\Queue\QueueDomainException;
use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Models\ServicePoint;
use App\Models\VirtualQueue;
use Illuminate\Contracts\View\View;
use Livewire\Component;

/**
 * Operator console for a single VirtualQueue: session lifecycle, calling
 * and resolving entries, service point management, and live counters.
 * Polls on an interval so the view stays current when multiple operators
 * work the same queue concurrently.
 */
class Show extends Component
{
    public int $virtualQueueId;

    public string $newServicePointName = '';

    public function mount(VirtualQueue $virtualQueue): void
    {
        $this->authorize('view', $virtualQueue);

        $this->virtualQueueId = $virtualQueue->id;
    }

    public function openSession(): void
    {
        $queue = $this->virtualQueue();

        $this->authorize('update', $queue);

        try {
            (new OpenQueueSession)($queue, auth()->user());
        } catch (QueueDomainException) {
            $this->notifyError('Frontu se nepodařilo otevřít.');

            return;
        }

        $this->notifySuccess('Fronta byla otevřena.');
    }

    public function pauseSession(): void
    {
        $session = $this->activeSessionOrFail();

        $this->authorize('manage', $session);

        try {
            (new PauseQueueSession)($session);
        } catch (QueueDomainException) {
            $this->notifyError('Frontu se nepodařilo pozastavit.');

            return;
        }

        $this->notifySuccess('Fronta byla pozastavena.');
    }

    public function resumeSession(): void
    {
        $session = $this->activeSessionOrFail();

        $this->authorize('manage', $session);

        try {
            (new ResumeQueueSession)($session);
        } catch (QueueDomainException) {
            $this->notifyError('Frontu se nepodařilo obnovit.');

            return;
        }

        $this->notifySuccess('Fronta byla obnovena.');
    }

    public function closeSession(): void
    {
        $session = $this->activeSessionOrFail();

        $this->authorize('manage', $session);

        try {
            (new CloseQueueSession)($session, auth()->user());
        } catch (QueueDomainException) {
            $this->notifyError('Frontu se nepodařilo zavřít.');

            return;
        }

        $this->notifySuccess('Fronta byla zavřena.');
    }

    /**
     * Calls the next waiting entry without the caller picking a specific
     * counter: the eligible service points are whatever they are allowed
     * to operate (every active point for a supervisor, just their own
     * assigned point(s) for a service point worker), and the longest-idle
     * free one among those wins (App\Actions\Queue\SelectServicePointToCall).
     */
    public function callNext(): void
    {
        $virtualQueue = $this->virtualQueue();
        $session = $this->activeSessionOrFail();

        $this->authorize('view', $virtualQueue);

        $operableIds = auth()->user()->operableServicePointIdsFor($virtualQueue);

        if ($operableIds === []) {
            $this->notifyError('Nemáte přiřazenou žádnou obslužnou přepážku.');

            return;
        }

        $candidates = ServicePoint::query()->whereIn('id', $operableIds)->get();
        $servicePoint = (new SelectServicePointToCall)($session, $candidates);

        if ($servicePoint === null) {
            $this->notifyError('Žádná z vašich přepážek není volná nebo aktivní.');

            return;
        }

        $this->authorize('operate', $servicePoint);

        try {
            $entry = (new CallNextEntry)($session, auth()->user(), $servicePoint);
        } catch (QueueDomainException) {
            $this->notifyError('Nikdo nečeká.');

            return;
        }

        $this->notifySuccess("Zavoláno pořadové číslo {$entry->number} na „{$servicePoint->name}“.");
    }

    public function completeEntry(int $entryId): void
    {
        $entry = QueueEntry::query()->whereKey($entryId)->firstOrFail();

        $this->authorize('manage', $entry);

        try {
            (new CompleteEntry)($entry, auth()->user());
        } catch (QueueDomainException) {
            $this->notifyError('Lístek se nepodařilo dokončit.');

            return;
        }

        $this->notifySuccess("Lístek č. {$entry->number} byl dokončen.");
    }

    public function cancelEntry(int $entryId): void
    {
        $entry = QueueEntry::query()->whereKey($entryId)->firstOrFail();

        $this->authorize('manage', $entry);

        try {
            (new CancelEntry)($entry, StatusChangeActor::Operator, auth()->user());
        } catch (QueueDomainException) {
            $this->notifyError('Lístek se nepodařilo zrušit.');

            return;
        }

        $this->notifySuccess("Lístek č. {$entry->number} byl zrušen.");
    }

    public function noShowEntry(int $entryId): void
    {
        $entry = QueueEntry::query()->whereKey($entryId)->firstOrFail();

        $this->authorize('manage', $entry);

        try {
            (new MarkEntryNoShow)($entry, auth()->user());
        } catch (QueueDomainException) {
            $this->notifyError('Lístek se nepodařilo označit jako nedostavený.');

            return;
        }

        $this->notifySuccess("Lístek č. {$entry->number} byl označen jako nedostavený.");
    }

    public function addServicePoint(): void
    {
        $queue = $this->virtualQueue();

        $this->authorize('update', $queue);

        $data = $this->validate([
            'newServicePointName' => ['required', 'string', 'max:255'],
        ], [
            'newServicePointName.required' => 'Zadejte název obslužného místa.',
            'newServicePointName.max' => 'Název smí mít nejvýše 255 znaků.',
        ]);

        $queue->servicePoints()->create([
            'name' => $data['newServicePointName'],
            'sort_order' => $queue->servicePoints()->count(),
            'is_active' => true,
        ]);

        $this->reset('newServicePointName');

        $this->notifySuccess('Obslužné místo bylo přidáno.');
    }

    public function toggleServicePoint(int $servicePointId): void
    {
        $servicePoint = ServicePoint::query()->whereKey($servicePointId)->firstOrFail();

        $this->authorize('manage', $servicePoint);

        $servicePoint->update(['is_active' => ! $servicePoint->is_active]);

        $this->notifySuccess($servicePoint->is_active
            ? "Obslužné místo „{$servicePoint->name}“ bylo aktivováno."
            : "Obslužné místo „{$servicePoint->name}“ bylo deaktivováno.");
    }

    private function virtualQueue(): VirtualQueue
    {
        return VirtualQueue::query()->findOrFail($this->virtualQueueId);
    }

    private function activeSessionOrFail(): QueueSession
    {
        $session = $this->virtualQueue()->activeSession;

        abort_if($session === null, 404);

        return $session;
    }

    private function notifySuccess(string $message): void
    {
        $this->dispatch('toast', variant: 'success', message: $message);
    }

    private function notifyError(string $message): void
    {
        $this->dispatch('toast', variant: 'error', message: $message);
    }

    public function render(): View
    {
        $virtualQueue = $this->virtualQueue();
        $session = $virtualQueue->activeSession;

        $servicePoints = $virtualQueue->servicePoints()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $calledEntries = collect();
        $waitingEntries = collect();
        $stats = [
            'waiting' => 0,
            'called' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'no_show' => 0,
            'issued' => 0,
        ];

        if ($session !== null) {
            $calledEntries = $session->entries()
                ->where('status', QueueEntryStatus::Called)
                ->with('servicePoint')
                ->orderBy('number')
                ->get();

            $waitingEntries = $session->entries()
                ->where('status', QueueEntryStatus::Waiting)
                ->orderBy('number')
                ->limit(50)
                ->get();

            $stats = [
                'waiting' => $session->entries()->where('status', QueueEntryStatus::Waiting)->count(),
                'called' => $calledEntries->count(),
                'completed' => $session->entries()->where('status', QueueEntryStatus::Completed)->count(),
                'cancelled' => $session->entries()->where('status', QueueEntryStatus::Cancelled)->count(),
                'no_show' => $session->entries()->where('status', QueueEntryStatus::NoShow)->count(),
                'issued' => $session->next_number - 1,
            ];
        }

        return view('livewire.queue.show', [
            'virtualQueue' => $virtualQueue,
            'session' => $session,
            'servicePoints' => $servicePoints,
            'calledEntries' => $calledEntries,
            'calledByServicePoint' => $calledEntries->keyBy('service_point_id'),
            'waitingEntries' => $waitingEntries,
            'stats' => $stats,
            'canCallNext' => auth()->user()->operableServicePointIdsFor($virtualQueue) !== [],
        ]);
    }
}
