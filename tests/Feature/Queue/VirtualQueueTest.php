<?php

namespace Tests\Feature\Queue;

use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class VirtualQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_id_is_assigned_automatically_on_creation(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();

        $this->assertNotEmpty($virtualQueue->public_id);
    }

    public function test_public_id_is_a_valid_ulid(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();

        $this->assertTrue(Str::isUlid($virtualQueue->public_id));
    }

    public function test_public_id_is_unique_across_virtual_queues(): void
    {
        $first = VirtualQueue::factory()->create();
        $second = VirtualQueue::factory()->create();

        $this->assertNotSame($first->public_id, $second->public_id);
    }

    public function test_internal_id_is_not_used_as_the_public_identifier(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();

        $this->assertNotSame((string) $virtualQueue->id, $virtualQueue->public_id);
    }

    public function test_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($user)->create();

        $this->assertTrue($virtualQueue->user->is($user));
        $this->assertTrue($user->virtualQueues->first()->is($virtualQueue));
    }

    public function test_has_many_service_points(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();

        $this->assertTrue($virtualQueue->servicePoints->first()->is($servicePoint));
    }
}
