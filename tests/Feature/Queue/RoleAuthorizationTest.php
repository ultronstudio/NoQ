<?php

namespace Tests\Feature\Queue;

use App\Enums\Role;
use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Models\RoleAssignment;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_full_access_without_any_assignment(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();
        $entry = QueueEntry::factory()->for($session, 'session')->create();

        $this->assertTrue($superAdmin->can('view', $virtualQueue));
        $this->assertTrue($superAdmin->can('update', $virtualQueue));
        $this->assertTrue($superAdmin->can('delete', $virtualQueue));
        $this->assertTrue($superAdmin->can('manage', $session));
        $this->assertTrue($superAdmin->can('manage', $servicePoint));
        $this->assertTrue($superAdmin->can('operate', $servicePoint));
        $this->assertTrue($superAdmin->can('manage', $entry));
    }

    public function test_delegated_queue_supervisor_has_full_access_to_their_queue_only(): void
    {
        $supervisor = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();
        $otherQueue = VirtualQueue::factory()->create();

        RoleAssignment::factory()->queueSupervisor()->create([
            'user_id' => $supervisor->id,
            'virtual_queue_id' => $virtualQueue->id,
        ]);

        $session = QueueSession::factory()->for($virtualQueue)->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();
        $entry = QueueEntry::factory()->for($session, 'session')->create();

        $this->assertTrue($supervisor->can('view', $virtualQueue));
        $this->assertTrue($supervisor->can('update', $virtualQueue));
        $this->assertTrue($supervisor->can('manage', $session));
        $this->assertTrue($supervisor->can('manage', $servicePoint));
        $this->assertTrue($supervisor->can('operate', $servicePoint));
        $this->assertTrue($supervisor->can('manage', $entry));

        $this->assertFalse($supervisor->can('view', $otherQueue));
        $this->assertFalse($supervisor->can('delete', $virtualQueue), 'delete stays owner/super-admin only');
    }

    public function test_service_point_worker_is_restricted_to_their_own_point(): void
    {
        $worker = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();
        $ownPoint = ServicePoint::factory()->for($virtualQueue)->create();
        $otherPoint = ServicePoint::factory()->for($virtualQueue)->create();

        RoleAssignment::factory()->servicePointWorker()->create([
            'user_id' => $worker->id,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $ownPoint->id,
        ]);

        $session = QueueSession::factory()->for($virtualQueue)->create();
        $ownEntry = QueueEntry::factory()->for($session, 'session')->create(['service_point_id' => $ownPoint->id]);
        $otherEntry = QueueEntry::factory()->for($session, 'session')->create(['service_point_id' => $otherPoint->id]);
        $unassignedEntry = QueueEntry::factory()->for($session, 'session')->create(['service_point_id' => null]);

        // Can view the queue (read access) but not manage its lifecycle.
        $this->assertTrue($worker->can('view', $virtualQueue));
        $this->assertFalse($worker->can('update', $virtualQueue));
        $this->assertFalse($worker->can('manage', $session));

        // Can operate only their own point, never configure it.
        $this->assertTrue($worker->can('operate', $ownPoint));
        $this->assertFalse($worker->can('operate', $otherPoint));
        $this->assertFalse($worker->can('manage', $ownPoint));

        // Can resolve entries already at their own point, nothing else.
        $this->assertTrue($worker->can('manage', $ownEntry));
        $this->assertFalse($worker->can('manage', $otherEntry));
        $this->assertFalse($worker->can('manage', $unassignedEntry));
    }

    public function test_supervisor_may_assign_a_service_point_worker_but_not_another_supervisor(): void
    {
        $supervisor = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($supervisor)->create();

        $this->assertTrue($supervisor->can('create', [RoleAssignment::class, $virtualQueue, Role::ServicePointWorker]));
        $this->assertFalse($supervisor->can('create', [RoleAssignment::class, $virtualQueue, Role::QueueSupervisor]));
    }

    public function test_super_admin_may_assign_either_role(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $virtualQueue = VirtualQueue::factory()->create();

        $this->assertTrue($superAdmin->can('create', [RoleAssignment::class, $virtualQueue, Role::ServicePointWorker]));
        $this->assertTrue($superAdmin->can('create', [RoleAssignment::class, $virtualQueue, Role::QueueSupervisor]));
    }

    public function test_stranger_may_not_assign_any_role(): void
    {
        $stranger = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();

        $this->assertFalse($stranger->can('create', [RoleAssignment::class, $virtualQueue, Role::ServicePointWorker]));
        $this->assertFalse($stranger->can('create', [RoleAssignment::class, $virtualQueue, Role::QueueSupervisor]));
    }
}
