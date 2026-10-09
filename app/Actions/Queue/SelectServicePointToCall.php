<?php

namespace App\Actions\Queue;

use App\Enums\QueueEntryStatus;
use App\Models\QueueSession;
use App\Models\ServicePoint;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Picks which free, active service point should receive the next called
 * entry when the caller does not choose one explicitly (an operator
 * covering several counters, or a worker whose own point is implicit).
 *
 * "Free" means no entry is currently Called at that point. Among the free
 * candidates, the one idle longest wins: a point that has never resolved
 * an entry is considered maximally idle and sorts first; otherwise ties
 * break on the most recent moment it stopped serving someone (transitioned
 * to Completed, Cancelled, or NoShow).
 */
final class SelectServicePointToCall
{
    /**
     * @param  Collection<int, ServicePoint>  $candidates
     */
    public function __invoke(QueueSession $session, Collection $candidates): ?ServicePoint
    {
        $busyServicePointIds = $session->entries()
            ->where('status', QueueEntryStatus::Called)
            ->pluck('service_point_id')
            ->all();

        $free = $candidates
            ->where('is_active', true)
            ->reject(fn (ServicePoint $servicePoint) => in_array($servicePoint->id, $busyServicePointIds, true));

        if ($free->isEmpty()) {
            return null;
        }

        return $free
            ->sortBy(function (ServicePoint $servicePoint) {
                $lastResolvedAt = $this->lastResolvedAt($servicePoint);

                return $lastResolvedAt === null ? -1 : $lastResolvedAt->timestamp;
            })
            ->first();
    }

    private function lastResolvedAt(ServicePoint $servicePoint): ?CarbonInterface
    {
        return $servicePoint->statusChanges()
            ->whereIn('to_status', [
                QueueEntryStatus::Completed,
                QueueEntryStatus::Cancelled,
                QueueEntryStatus::NoShow,
            ])
            ->latest('created_at')
            ->value('created_at');
    }
}
