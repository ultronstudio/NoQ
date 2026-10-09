<?php

namespace App\Livewire\Public;

use App\Actions\Queue\CancelEntry;
use App\Actions\Queue\JoinQueue;
use App\Enums\QueueEntryStatus;
use App\Enums\QueueSessionStatus;
use App\Exceptions\Queue\QueueDomainException;
use App\Models\QueueEntry;
use App\Models\VirtualQueue;
use App\Support\Queue\TrackingToken;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

/**
 * Public, unauthenticated entry point for a VirtualQueue reached via its
 * QR code / public link (public_id). Lets an anonymous visitor join the
 * queue and then watch their own ticket live — no account needed.
 *
 * Identity is a single raw tracking token stored only in an httpOnly
 * cookie scoped to this queue (see App\Support\Queue\TrackingToken); the
 * server never persists the raw value, only its hash, matching the
 * guarantees documented there.
 */
class QueueTicket extends Component
{
    public int $virtualQueueId;

    public ?string $token = null;

    public function mount(VirtualQueue $virtualQueue): void
    {
        $this->virtualQueueId = $virtualQueue->id;

        $cookie = request()->cookie($this->cookieName());
        $this->token = is_string($cookie) ? $cookie : null;
    }

    public function join(): void
    {
        $virtualQueue = $this->virtualQueue();
        $session = $virtualQueue->activeSession;

        if ($session === null || $session->status !== QueueSessionStatus::Open) {
            $this->addError('join', 'Fronta momentálně nepřijímá nové návštěvníky.');

            return;
        }

        try {
            $result = (new JoinQueue)($session);
        } catch (QueueDomainException) {
            $this->addError('join', 'Fronta je aktuálně plná, zkuste to prosím později.');

            return;
        }

        $this->token = $result->trackingToken;

        Cookie::queue(Cookie::make(
            name: $this->cookieName(),
            value: $this->token,
            minutes: 60 * 12,
            httpOnly: true,
        ));
    }

    public function cancel(): void
    {
        $entry = $this->resolveEntry();

        if ($entry !== null) {
            try {
                (new CancelEntry)($entry);
            } catch (QueueDomainException) {
                // Already resolved by an operator in the meantime; nothing to undo.
            }
        }

        $this->forgetTicket();
    }

    private function resolveEntry(): ?QueueEntry
    {
        if ($this->token === null) {
            return null;
        }

        return QueueEntry::query()
            ->where('tracking_token_hash', TrackingToken::hash($this->token))
            ->with('servicePoint')
            ->first();
    }

    private function forgetTicket(): void
    {
        $this->token = null;
        Cookie::queue(Cookie::forget($this->cookieName()));
    }

    private function cookieName(): string
    {
        return 'noq_ticket_'.$this->virtualQueueId;
    }

    private function virtualQueue(): VirtualQueue
    {
        return VirtualQueue::query()->findOrFail($this->virtualQueueId);
    }

    public function render(): View
    {
        $virtualQueue = $this->virtualQueue();
        $session = $virtualQueue->activeSession;
        $entry = $this->resolveEntry();

        if ($entry !== null && $entry->queue_session_id !== $session?->id) {
            // The cookie points at a past (no longer active) session.
            $entry = null;
            $this->forgetTicket();
        }

        return view('livewire.public.queue-ticket', [
            'virtualQueue' => $virtualQueue,
            'session' => $session,
            'entry' => $entry,
            'position' => $entry?->status === QueueEntryStatus::Waiting ? $entry->position() : null,
        ]);
    }
}
