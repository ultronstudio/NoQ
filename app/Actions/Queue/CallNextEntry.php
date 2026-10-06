<?php

namespace App\Actions\Queue;

use App\Actions\Queue\Concerns\TransitionsQueueEntryStatus;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Exceptions\Queue\InvalidServicePointException;
use App\Exceptions\Queue\NoWaitingQueueEntriesException;
use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Calls the next waiting entry of a QueueSession (lowest ticket number).
 *
 * The session row is locked for update for the whole operation, so two
 * operators calling "next" concurrently on the same session are
 * serialized: the second transaction only sees the candidate list after
 * the first has already moved its pick to Called.
 */
final class CallNextEntry
{
    use TransitionsQueueEntryStatus;

    public function __invoke(QueueSession $session, ?User $operator = null, ?ServicePoint $servicePoint = null): QueueEntry
    {
        return DB::transaction(function () use ($session, $operator, $servicePoint) {
            $lockedSession = QueueSession::query()->whereKey($session->getKey())->lockForUpdate()->first();

            if ($servicePoint !== null) {
                $this->assertServicePointUsable($servicePoint, $lockedSession);
            }

            $next = QueueEntry::query()
                ->where('queue_session_id', $lockedSession->getKey())
                ->where('status', QueueEntryStatus::Waiting)
                ->orderBy('number')
                ->first();

            if ($next === null) {
                throw NoWaitingQueueEntriesException::forSession($lockedSession);
            }

            return $this->transition(
                entry: $next,
                to: QueueEntryStatus::Called,
                actor: StatusChangeActor::Operator,
                changedBy: $operator,
                servicePoint: $servicePoint,
            );
        });
    }

    private function assertServicePointUsable(ServicePoint $servicePoint, QueueSession $session): void
    {
        if ($servicePoint->virtual_queue_id !== $session->virtual_queue_id) {
            throw InvalidServicePointException::notInQueue($servicePoint, $session);
        }

        if (! $servicePoint->is_active) {
            throw InvalidServicePointException::inactive($servicePoint);
        }
    }
}
