<?php

namespace App\Actions\Queue;

use App\Enums\QueueSessionStatus;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueSession;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Support\Facades\DB;

/**
 * Starts a new QueueSession for a VirtualQueue.
 *
 * Locks the VirtualQueue row so that two concurrent calls for the same
 * queue cannot both pass the "no active session yet" check: the second
 * transaction waits for the first to commit, then sees the session it
 * just created and fails the invariant below.
 */
final class OpenQueueSession
{
    public function __invoke(VirtualQueue $virtualQueue, User $openedBy): QueueSession
    {
        return DB::transaction(function () use ($virtualQueue, $openedBy) {
            $locked = VirtualQueue::query()->whereKey($virtualQueue->getKey())->lockForUpdate()->first();

            $hasActiveSession = $locked->sessions()
                ->whereIn('status', [QueueSessionStatus::Open, QueueSessionStatus::Paused])
                ->exists();

            if ($hasActiveSession) {
                throw QueueSessionStateException::alreadyActive($locked);
            }

            return $locked->sessions()->create([
                'opened_by_user_id' => $openedBy->getKey(),
                'status' => QueueSessionStatus::Open,
                'next_number' => 1,
                'opened_at' => now(),
            ]);
        });
    }
}
