<?php

namespace App\Livewire\Queue;

use App\Enums\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Lets a queue supervisor (or super admin) see and manage who has
 * delegated access to a VirtualQueue. ServicePointWorker assignments are
 * open to any supervisor; QueueSupervisor assignments are super-admin
 * only (see App\Policies\RoleAssignmentPolicy).
 */
class TeamAccess extends Component
{
    public int $virtualQueueId;

    public string $email = '';

    public string $role = '';

    public ?int $servicePointId = null;

    public function mount(VirtualQueue $virtualQueue): void
    {
        $this->authorize('update', $virtualQueue);

        $this->virtualQueueId = $virtualQueue->id;
        $this->role = Role::ServicePointWorker->value;
    }

    public function assign(): void
    {
        $virtualQueue = $this->virtualQueue();

        $data = $this->validate([
            'email' => ['required', 'email'],
            'role' => ['required', Rule::enum(Role::class)],
            'servicePointId' => ['nullable', 'integer'],
        ], [
            'email.required' => 'Zadejte e-mail uživatele.',
            'email.email' => 'Zadejte platný e-mail.',
            'role.required' => 'Vyberte roli.',
        ]);

        $role = Role::from($data['role']);

        $this->authorize('create', [RoleAssignment::class, $virtualQueue, $role]);

        $user = User::query()->where('email', $data['email'])->first();

        if ($user === null) {
            $this->addError('email', 'Uživatel s tímto e-mailem neexistuje.');

            return;
        }

        $servicePoint = null;

        if ($role === Role::ServicePointWorker) {
            $servicePoint = $this->servicePointId === null
                ? null
                : $virtualQueue->servicePoints()->whereKey($this->servicePointId)->first();

            if ($servicePoint === null) {
                $this->addError('servicePointId', 'Vyberte obslužné místo patřící této frontě.');

                return;
            }
        }

        $alreadyAssigned = RoleAssignment::query()
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->where('virtual_queue_id', $virtualQueue->id)
            ->where('service_point_id', $servicePoint?->id)
            ->exists();

        if ($alreadyAssigned) {
            $this->addError('email', 'Tento uživatel už tuto roli má.');

            return;
        }

        RoleAssignment::create([
            'user_id' => $user->id,
            'role' => $role,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $servicePoint?->id,
        ]);

        $this->reset(['email', 'servicePointId']);

        $this->dispatch('toast', variant: 'success', message: "Role byla přiřazena uživateli {$user->name}.");
    }

    public function revoke(int $roleAssignmentId): void
    {
        $assignment = RoleAssignment::query()->whereKey($roleAssignmentId)->firstOrFail();

        $this->authorize('delete', $assignment);

        $assignment->delete();

        $this->dispatch('toast', variant: 'success', message: 'Přístup byl odebrán.');
    }

    private function virtualQueue(): VirtualQueue
    {
        return VirtualQueue::query()->findOrFail($this->virtualQueueId);
    }

    public function render(): View
    {
        $virtualQueue = $this->virtualQueue();

        $assignments = RoleAssignment::query()
            ->where('virtual_queue_id', $virtualQueue->id)
            ->with(['user', 'servicePoint'])
            ->orderBy('role')
            ->get();

        return view('livewire.queue.team-access', [
            'assignments' => $assignments,
            'servicePoints' => $virtualQueue->servicePoints()->orderBy('sort_order')->orderBy('name')->get(),
            'canAssignSupervisor' => auth()->user()->is_super_admin,
        ]);
    }
}
