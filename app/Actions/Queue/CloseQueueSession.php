<?php

namespace App\Actions\Queue;

use App\Enums\QueueSessionStatus;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Closes a QueueSession for good. Closed sessions are terminal and
 * cannot be reopened, paused, or resumed.
 */
final class CloseQueueSession
{
    public function __invoke(QueueSession $session, User $closedBy): QueueSession
    {
        return DB::transaction(function () use ($session, $closedBy) {
            $locked = QueueSession::query()->whereKey($session->getKey())->lockForUpdate()->first();

            if ($locked->status === QueueSessionStatus::Closed) {
                throw QueueSessionStateException::alreadyClosed($locked);
            }

            $locked->update([
                'status' => QueueSessionStatus::Closed,
                'closed_at' => now(),
                'closed_by_user_id' => $closedBy->getKey(),
            ]);

            return $locked;
        });
    }
}
