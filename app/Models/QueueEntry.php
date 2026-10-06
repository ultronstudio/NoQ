<?php

namespace App\Models;

use App\Enums\QueueEntryStatus;
use Database\Factories\QueueEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * An anonymous visitor's ticket within a QueueSession. Visitors are never
 * represented by the User model; they are identified only by a hashed
 * tracking token (see App\Support\Queue\TrackingToken).
 *
 * @property int $id
 * @property int $queue_session_id
 * @property int|null $service_point_id
 * @property string $tracking_token_hash
 * @property int $number
 * @property QueueEntryStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['queue_session_id', 'service_point_id', 'tracking_token_hash', 'number', 'status'])]
#[Hidden(['tracking_token_hash'])]
class QueueEntry extends Model
{
    /** @use HasFactory<QueueEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'status' => QueueEntryStatus::class,
        ];
    }

    /**
     * @return BelongsTo<QueueSession, $this>
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(QueueSession::class, 'queue_session_id');
    }

    /**
     * @return BelongsTo<ServicePoint, $this>
     */
    public function servicePoint(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class);
    }

    /**
     * @return HasMany<QueueEntryStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(QueueEntryStatusChange::class);
    }

    /**
     * 1-based position among currently waiting entries of the same session,
     * derived from the (queue_session_id, status, number) index rather than
     * stored, so it is always consistent with the status history.
     */
    public function position(): int
    {
        return self::query()
            ->where('queue_session_id', $this->queue_session_id)
            ->where('status', QueueEntryStatus::Waiting)
            ->where('number', '<', $this->number)
            ->count() + 1;
    }
}
