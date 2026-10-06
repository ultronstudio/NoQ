<?php

namespace App\Models;

use Database\Factories\ServicePointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A single service desk/counter within a VirtualQueue (e.g. "Přepážka 1").
 * Inactive service points are kept for historical records rather than deleted.
 *
 * @property int $id
 * @property int $virtual_queue_id
 * @property string $name
 * @property int $sort_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['virtual_queue_id', 'name', 'sort_order', 'is_active'])]
class ServicePoint extends Model
{
    /** @use HasFactory<ServicePointFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
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
     * @return HasMany<QueueEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(QueueEntry::class);
    }

    /**
     * @return HasMany<QueueEntryStatusChange, $this>
     */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(QueueEntryStatusChange::class);
    }
}
