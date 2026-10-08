<?php

namespace App\Actions\Queue;

use App\Enums\QueueEntryStatus;
use App\Enums\QueueSessionStatus;
use App\Enums\StatusChangeActor;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueEntryStatusChange;
use App\Models\QueueSession;
use App\Support\Queue\TrackingToken;
use Illuminate\Support\Facades\DB;

/**
 * Lets an anonymous visitor join an open QueueSession.
 *
 * The ticket number comes from QueueSession::next_number rather than
 * MAX(number) + 1: the session row is locked for update inside the
 * transaction, so two concurrent joins are serialized and can never
 * receive the same number. The same lock makes the capacity check
 * against VirtualQueue::max_waiting (when set) race-free too.
 */
final class JoinQueue
{
    public function __invoke(QueueSession $session): JoinQueueResult
    {
        return DB::transaction(function () use ($session) {
            $locked = QueueSession::query()->whereKey($session->getKey())->lockForUpdate()->first();

            if ($locked->status !== QueueSessionStatus::Open) {
                throw QueueSessionStateException::notOpen($locked);
            }

            $maxWaiting = $locked->virtualQueue->max_waiting;

            if ($maxWaiting !== null) {
                $waitingCount = $locked->entries()->where('status', QueueEntryStatus::Waiting)->count();

                if ($waitingCount >= $maxWaiting) {
                    throw QueueSessionStateException::full($locked, $maxWaiting);
                }
            }

            $number = $locked->next_number;
            $locked->update(['next_number' => $number + 1]);

            $token = TrackingToken::generate();

            $entry = $locked->entries()->create([
                'tracking_token_hash' => $token->hash,
                'number' => $number,
                'status' => QueueEntryStatus::Waiting,
            ]);

            QueueEntryStatusChange::create([
                'queue_entry_id' => $entry->getKey(),
                'from_status' => null,
                'to_status' => QueueEntryStatus::Waiting,
                'actor' => StatusChangeActor::Visitor,
            ]);

            return new JoinQueueResult($entry, $token->raw);
        });
    }
}
