<?php

namespace App\Actions\Queue\Concerns;

use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Exceptions\Queue\InvalidQueueEntryTransitionException;
use App\Models\QueueEntry;
use App\Models\QueueEntryStatusChange;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Centralizes every QueueEntry status change behind one locked,
 * validated, history-recording path, so no action can mutate
 * QueueEntry::status without also writing a QueueEntryStatusChange.
 */
trait TransitionsQueueEntryStatus
{
    protected function transition(
        QueueEntry $entry,
        QueueEntryStatus $to,
        StatusChangeActor $actor,
        ?User $changedBy = null,
        ?ServicePoint $servicePoint = null,
    ): QueueEntry {
        return DB::transaction(function () use ($entry, $to, $actor, $changedBy, $servicePoint) {
            /** @var QueueEntry|null $locked */
            $locked = QueueEntry::query()->whereKey($entry->getKey())->lockForUpdate()->first();

            if ($locked === null) {
                throw (new ModelNotFoundException)->setModel(QueueEntry::class, [$entry->getKey()]);
            }

            $from = $locked->status;

            if (! $from->canTransitionTo($to)) {
                throw InvalidQueueEntryTransitionException::fromTo($from, $to);
            }

            $servicePointId = $servicePoint?->getKey() ?? $locked->service_point_id;

            $locked->forceFill([
                'status' => $to,
                'service_point_id' => $servicePointId,
            ])->save();

            QueueEntryStatusChange::create([
                'queue_entry_id' => $locked->getKey(),
                'from_status' => $from,
                'to_status' => $to,
                'actor' => $actor,
                'changed_by_user_id' => $changedBy?->getKey(),
                'service_point_id' => $servicePointId,
            ]);

            return $locked;
        });
    }
}
