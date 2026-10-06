<?php

namespace App\Actions\Queue;

use App\Enums\QueueSessionStatus;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueSession;
use Illuminate\Support\Facades\DB;

/**
 * Pauses an open QueueSession. A paused session still counts as "active"
 * for the one-active-session-per-queue invariant, but stops accepting
 * new entries until resumed.
 */
final class PauseQueueSession
{
    public function __invoke(QueueSession $session): QueueSession
    {
        return DB::transaction(function () use ($session) {
            $locked = QueueSession::query()->whereKey($session->getKey())->lockForUpdate()->first();

            if ($locked->status !== QueueSessionStatus::Open) {
                throw QueueSessionStateException::cannotPause($locked);
            }

            $locked->update(['status' => QueueSessionStatus::Paused]);

            return $locked;
        });
    }
}
