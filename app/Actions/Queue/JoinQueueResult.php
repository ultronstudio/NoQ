<?php

namespace App\Actions\Queue;

use App\Models\QueueEntry;

/**
 * The result of joining a queue: the created entry plus the one-time raw
 * tracking token. The raw token is never persisted; the caller is
 * responsible for handing it to the visitor (e.g. via URL or cookie).
 */
final readonly class JoinQueueResult
{
    public function __construct(
        public QueueEntry $entry,
        public string $trackingToken,
    ) {}
}
