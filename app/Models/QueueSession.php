<?php

namespace App\Models;

use App\Enums\QueueSessionStatus;
use Database\Factories\QueueSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * One concrete run of a VirtualQueue (e.g. "6. 10. 2026, otevřeno 08:00").
 * A new QueueSession is created every time the queue is opened again.
 *
 * @property int $id
 * @property int $virtual_queue_id
 * @property int $opened_by_user_id
 * @property int|null $closed_by_user_id
 * @property QueueSessionStatus $status
 * @property int $next_number
 * @property Carbon $opened_at
 * @property Carbon|null $closed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['virtual_queue_id', 'opened_by_user_id', 'closed_by_user_id', 'status', 'next_number', 'opened_at', 'closed_at'])]
class QueueSession extends Model
{
    /** @use HasFactory<QueueSessionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QueueSessionStatus::class,
            'next_number' => 'integer',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<VirtualQueue, $this>
     */
    public function virtualQueue(): BelongsTo
    {
        return $this->belongsTo(VirtualQueue::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by_user_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    /**
     * @return HasMany<QueueEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }
}
