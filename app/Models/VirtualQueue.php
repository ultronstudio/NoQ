<?php

namespace App\Models;

use App\Enums\QueueSessionStatus;
use Database\Factories\VirtualQueueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * A long-lived definition of a queue (e.g. "Studijní oddělení").
 * Actual operation happens in its QueueSession records.
 *
 * @property int $id
 * @property int $user_id
 * @property string $public_id
 * @property string $name
 * @property string|null $description
 * @property int|null $max_waiting
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'name', 'description', 'max_waiting'])]
class VirtualQueue extends Model
{
    /** @use HasFactory<VirtualQueueFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (self $virtualQueue): void {
            $virtualQueue->public_id ??= (string) Str::ulid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_waiting' => 'integer',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<QueueSession, $this>
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(QueueSession::class);
    }

    /**
     * @return HasMany<ServicePoint, $this>
     */
    public function servicePoints(): HasMany
    {
        return $this->hasMany(ServicePoint::class);
    }

    /**
     * The single session currently open or paused for this queue, if any.
     *
     * @return HasOne<QueueSession, $this>
     */
    public function activeSession(): HasOne
    {
        return $this->hasOne(QueueSession::class)
            ->whereIn('status', [QueueSessionStatus::Open, QueueSessionStatus::Paused]);
    }

    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }
}
