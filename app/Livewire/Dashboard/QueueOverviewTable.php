<?php

namespace App\Livewire\Dashboard;

use App\Actions\Queue\CloseQueueSession;
use App\Actions\Queue\OpenQueueSession;
use App\Actions\Queue\PauseQueueSession;
use App\Actions\Queue\ResumeQueueSession;
use App\Enums\QueueEntryStatus;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueSession;
use App\Models\VirtualQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Livewire\Component;

/**
 * Admin dashboard table of the current user's queues, with inline
 * session lifecycle actions (open/pause/resume/close) and a modal to
 * create a new queue. Polls on an interval so waiting counts and
 * statuses stay live when changed elsewhere (e.g. by an operator
 * working the queue).
 */
class QueueOverviewTable extends Component
{
    public string $name = '';

    public string $description = '';

    public function createQueue(): RedirectResponse
    {
        $this->authorize('create', VirtualQueue::class);

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Zadejte název fronty.',
            'name.max' => 'Název smí mít nejvýše 255 znaků.',
            'description.max' => 'Popis smí mít nejvýše 1000 znaků.',
        ]);

        $queue = VirtualQueue::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'description' => $data['description'] ?: null,
        ]);

        return redirect()->route('queue.show', $queue);
    }

    public function openSession(int $queueId): void
    {
        $queue = VirtualQueue::query()->whereKey($queueId)->firstOrFail();

        $this->authorize('update', $queue);

        try {
            (new OpenQueueSession)($queue, auth()->user());
        } catch (QueueSessionStateException) {
            $this->notifyError("Frontu „{$queue->name}“ se nepodařilo otevřít.");

            return;
        }

        $this->notifySuccess("Fronta „{$queue->name}“ byla otevřena.");
    }

    public function pauseSession(int $sessionId): void
    {
        $session = QueueSession::query()->whereKey($sessionId)->with('virtualQueue')->firstOrFail();

        $this->authorize('manage', $session);

        try {
            (new PauseQueueSession)($session);
        } catch (QueueSessionStateException) {
            $this->notifyError("Frontu „{$session->virtualQueue->name}“ se nepodařilo pozastavit.");

            return;
        }

        $this->notifySuccess("Fronta „{$session->virtualQueue->name}“ byla pozastavena.");
    }

    public function resumeSession(int $sessionId): void
    {
        $session = QueueSession::query()->whereKey($sessionId)->with('virtualQueue')->firstOrFail();

        $this->authorize('manage', $session);

        try {
            (new ResumeQueueSession)($session);
        } catch (QueueSessionStateException) {
            $this->notifyError("Frontu „{$session->virtualQueue->name}“ se nepodařilo obnovit.");

            return;
        }

        $this->notifySuccess("Fronta „{$session->virtualQueue->name}“ byla obnovena.");
    }

    public function closeSession(int $sessionId): void
    {
        $session = QueueSession::query()->whereKey($sessionId)->with('virtualQueue')->firstOrFail();

        $this->authorize('manage', $session);

        try {
            (new CloseQueueSession)($session, auth()->user());
        } catch (QueueSessionStateException) {
            $this->notifyError("Frontu „{$session->virtualQueue->name}“ se nepodařilo zavřít.");

            return;
        }

        $this->notifySuccess("Fronta „{$session->virtualQueue->name}“ byla zavřena.");
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
        $user = auth()->user();

        $queuesQuery = VirtualQueue::query()
            ->select([
                'id',
                'user_id',
                'public_id',
                'name',
                'archived_at',
            ])
            ->whereNull('archived_at');

        if (! $user->is_super_admin) {
            $queuesQuery->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereHas('roleAssignments', fn ($q) => $q->where('user_id', $user->id));
            });
        }

        $allQueues = $queuesQuery
            ->withCount([
                'servicePoints as active_service_points_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->with([
                'activeSession' => fn ($query) => $query->withCount([
                    'entries as waiting_count' => fn ($query) => $query->where('status', QueueEntryStatus::Waiting),
                ]),
            ])
            ->orderBy('name')
            ->get();

        return view('livewire.dashboard.queue-overview-table', [
            'allQueues' => $allQueues,
        ]);
    }
}
