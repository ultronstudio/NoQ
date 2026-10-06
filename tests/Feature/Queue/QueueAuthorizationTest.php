<?php

namespace Tests\Feature\Queue;

use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_and_manage_their_virtual_queue(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();

        $this->assertTrue($owner->can('view', $virtualQueue));
        $this->assertTrue($owner->can('update', $virtualQueue));
        $this->assertTrue($owner->can('delete', $virtualQueue));
    }

    public function test_another_user_cannot_view_or_manage_someone_elses_virtual_queue(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();

        $this->assertFalse($stranger->can('view', $virtualQueue));
        $this->assertFalse($stranger->can('update', $virtualQueue));
        $this->assertFalse($stranger->can('delete', $virtualQueue));
    }

    public function test_any_authenticated_user_can_create_a_virtual_queue(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('create', VirtualQueue::class));
    }

    public function test_session_authorization_is_derived_from_the_virtual_queue_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();

        $this->assertTrue($owner->can('manage', $session));
        $this->assertFalse($stranger->can('manage', $session));
    }

    public function test_service_point_authorization_is_derived_from_the_virtual_queue_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();

        $this->assertTrue($owner->can('manage', $servicePoint));
        $this->assertFalse($stranger->can('manage', $servicePoint));
    }

    public function test_queue_entry_authorization_is_derived_from_the_virtual_queue_owner(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $entry = QueueEntry::factory()->for($session, 'session')->create();

        $this->assertTrue($owner->can('manage', $entry));
        $this->assertFalse($stranger->can('manage', $entry));
    }
}
