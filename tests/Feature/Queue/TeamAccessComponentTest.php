<?php

namespace Tests\Feature\Queue;

use App\Enums\Role;
use App\Livewire\Queue\TeamAccess;
use App\Models\RoleAssignment;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TeamAccessComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_worker_cannot_mount_the_component(): void
    {
        $worker = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();

        RoleAssignment::factory()->servicePointWorker()->create([
            'user_id' => $worker->id,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $servicePoint->id,
        ]);

        $this->actingAs($worker);

        Livewire::test(TeamAccess::class, ['virtualQueue' => $virtualQueue])
            ->assertForbidden();
    }

    public function test_supervisor_can_assign_a_service_point_worker_to_an_existing_user(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();
        $newWorker = User::factory()->create();

        $this->actingAs($owner);

        Livewire::test(TeamAccess::class, ['virtualQueue' => $virtualQueue])
            ->set('email', $newWorker->email)
            ->set('servicePointId', $servicePoint->id)
            ->call('assign')
            ->assertHasNoErrors()
            ->assertDispatched('toast', variant: 'success');

        $this->assertDatabaseHas('role_assignments', [
            'user_id' => $newWorker->id,
            'role' => Role::ServicePointWorker->value,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $servicePoint->id,
        ]);
    }

    public function test_supervisor_cannot_assign_the_supervisor_role(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $candidate = User::factory()->create();

        $this->actingAs($owner);

        Livewire::test(TeamAccess::class, ['virtualQueue' => $virtualQueue])
            ->set('email', $candidate->email)
            ->set('role', Role::QueueSupervisor->value)
            ->call('assign')
            ->assertForbidden();

        $this->assertDatabaseMissing('role_assignments', [
            'user_id' => $candidate->id,
            'role' => Role::QueueSupervisor->value,
        ]);
    }

    public function test_super_admin_can_assign_the_supervisor_role(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $virtualQueue = VirtualQueue::factory()->create();
        $candidate = User::factory()->create();

        $this->actingAs($superAdmin);

        Livewire::test(TeamAccess::class, ['virtualQueue' => $virtualQueue])
            ->set('email', $candidate->email)
            ->set('role', Role::QueueSupervisor->value)
            ->call('assign')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('role_assignments', [
            'user_id' => $candidate->id,
            'role' => Role::QueueSupervisor->value,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => null,
        ]);
    }

    public function test_assigning_an_unknown_email_fails_with_a_friendly_error(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();

        $this->actingAs($owner);

        Livewire::test(TeamAccess::class, ['virtualQueue' => $virtualQueue])
            ->set('email', 'nobody@example.com')
            ->set('servicePointId', $servicePoint->id)
            ->call('assign')
            ->assertHasErrors('email');
    }

    public function test_supervisor_can_revoke_an_assignment(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();
        $worker = User::factory()->create();

        $assignment = RoleAssignment::factory()->servicePointWorker()->create([
            'user_id' => $worker->id,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $servicePoint->id,
        ]);

        $this->actingAs($owner);

        Livewire::test(TeamAccess::class, ['virtualQueue' => $virtualQueue])
            ->call('revoke', $assignment->id)
            ->assertDispatched('toast', variant: 'success');

        $this->assertDatabaseMissing('role_assignments', ['id' => $assignment->id]);
    }
}
