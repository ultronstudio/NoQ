<?php

namespace App\Actions\Queue;

use App\Enums\QueueSessionStatus;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueSession;
use Illuminate\Support\Facades\DB;

/**
 * Resumes a paused QueueSession back to open. A closed session is
 * terminal and can never be resumed.
 */
final class ResumeQueueSession
{
    public function __invoke(QueueSession $session): QueueSession
    {
        return DB::transaction(function () use ($session) {
            $locked = QueueSession::query()->whereKey($session->getKey())->lockForUpdate()->first();

            if ($locked->status !== QueueSessionStatus::Paused) {
                throw QueueSessionStateException::cannotResume($locked);
            }

            $locked->update(['status' => QueueSessionStatus::Open]);

            return $locked;
        });
    }
}
