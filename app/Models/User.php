<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_super_admin
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        $initials = Str::initials($this->name, true);

        return Str::length($initials) > 1
            ? Str::substr($initials, 0, 1).Str::substr($initials, -1)
            : $initials;
    }

    /**
     * @return HasMany<VirtualQueue, $this>
     */
    public function virtualQueues(): HasMany
    {
        return $this->hasMany(VirtualQueue::class);
    }

    /**
     * @return HasMany<QueueSession, $this>
     */
    public function openedQueueSessions(): HasMany
    {
        return $this->hasMany(QueueSession::class, 'opened_by_user_id');
    }

    /**
     * @return HasMany<QueueSession, $this>
     */
    public function closedQueueSessions(): HasMany
    {
        return $this->hasMany(QueueSession::class, 'closed_by_user_id');
    }

    /**
     * @return HasMany<QueueEntryStatusChange, $this>
     */
    public function queueEntryStatusChanges(): HasMany
    {
        return $this->hasMany(QueueEntryStatusChange::class, 'changed_by_user_id');
    }

    /**
     * @return HasMany<RoleAssignment, $this>
     */
    public function roleAssignments(): HasMany
    {
        return $this->hasMany(RoleAssignment::class);
    }

    /**
     * A super admin and a VirtualQueue's creator always have full operator
     * access to it; QueueSupervisor is the delegated equivalent for anyone
     * else.
     */
    public function isSupervisorOf(VirtualQueue $virtualQueue): bool
    {
        return $this->is_super_admin
            || $virtualQueue->user_id === $this->id
            || $this->roleAssignments()
                ->where('role', Role::QueueSupervisor)
                ->where('virtual_queue_id', $virtualQueue->id)
                ->exists();
    }

    /**
     * Service point ids this user may call entries to / resolve entries at
     * within the given queue: every service point if they supervise the
     * queue, otherwise only the ones they are explicitly assigned to work.
     *
     * @return list<int>
     */
    public function operableServicePointIdsFor(VirtualQueue $virtualQueue): array
    {
        if ($this->isSupervisorOf($virtualQueue)) {
            return $virtualQueue->servicePoints()->pluck('id')->all();
        }

        return $this->roleAssignments()
            ->where('role', Role::ServicePointWorker)
            ->whereHas('servicePoint', fn ($query) => $query->where('virtual_queue_id', $virtualQueue->id))
            ->pluck('service_point_id')
            ->all();
    }

    public function canViewQueue(VirtualQueue $virtualQueue): bool
    {
        return $this->isSupervisorOf($virtualQueue) || $this->operableServicePointIdsFor($virtualQueue) !== [];
    }

    public function canOperateServicePoint(ServicePoint $servicePoint): bool
    {
        return in_array($servicePoint->id, $this->operableServicePointIdsFor($servicePoint->virtualQueue), true);
    }

    /**
     * Whether the user may call/complete/cancel/no-show this specific
     * entry: supervisors may act on any entry in their queue, a service
     * point worker only on entries already assigned to their own point(s).
     */
    public function canManageEntry(QueueEntry $entry): bool
    {
        $virtualQueue = $entry->session->virtualQueue;

        if ($this->isSupervisorOf($virtualQueue)) {
            return true;
        }

        return $entry->service_point_id !== null
            && in_array($entry->service_point_id, $this->operableServicePointIdsFor($virtualQueue), true);
    }
}
