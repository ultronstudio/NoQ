<?php

namespace App\Models;

use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use Database\Factories\QueueEntryStatusChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * An immutable historical record of a single QueueEntry status transition.
 * Rows are never updated or deleted by the application.
 *
 * @property int $id
 * @property int $queue_entry_id
 * @property QueueEntryStatus|null $from_status
 * @property QueueEntryStatus $to_status
 * @property StatusChangeActor $actor
 * @property int|null $changed_by_user_id
 * @property int|null $service_point_id
 * @property Carbon|null $created_at
 */
#[Fillable(['queue_entry_id', 'from_status', 'to_status', 'actor', 'changed_by_user_id', 'service_point_id'])]
class QueueEntryStatusChange extends Model
{
    /** @use HasFactory<QueueEntryStatusChangeFactory> */
    use HasFactory;

    /**
     * This history record has no updated_at column: it is immutable.
     */
    public const ?string UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'from_status' => QueueEntryStatus::class,
            'to_status' => QueueEntryStatus::class,
            'actor' => StatusChangeActor::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<QueueEntry, $this>
     */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(QueueEntry::class, 'queue_entry_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * @return BelongsTo<ServicePoint, $this>
     */
    public function servicePoint(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class);
    }
}
