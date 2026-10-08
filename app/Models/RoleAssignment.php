<?php

namespace App\Models;

use App\Enums\Role;
use Database\Factories\RoleAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Grants a User a scoped role beyond plain VirtualQueue ownership:
 * QueueSupervisor (full operator access to one VirtualQueue) or
 * ServicePointWorker (access limited to one ServicePoint within a
 * VirtualQueue). A user may hold any number of these.
 *
 * @property int $id
 * @property int $user_id
 * @property Role $role
 * @property int $virtual_queue_id
 * @property int|null $service_point_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['user_id', 'role', 'virtual_queue_id', 'service_point_id'])]
class RoleAssignment extends Model
{
    /** @use HasFactory<RoleAssignmentFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => Role::class,
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
     * @return BelongsTo<VirtualQueue, $this>
     */
    public function virtualQueue(): BelongsTo
    {
        return $this->belongsTo(VirtualQueue::class);
    }

    /**
     * @return BelongsTo<ServicePoint, $this>
     */
    public function servicePoint(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class);
    }
}
