<?php

namespace Tests\Feature\Queue;

use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueShowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();

        $response = $this->get(route('queue.show', $virtualQueue));
        $response->assertRedirect(route('login'));
    }

    public function test_owner_can_view_their_queue(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();

        $response = $this->actingAs($owner)->get(route('queue.show', $virtualQueue));

        $response->assertOk();
        $response->assertSee($virtualQueue->name);
    }

    public function test_another_user_cannot_view_someone_elses_queue(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();

        $response = $this->actingAs($stranger)->get(route('queue.show', $virtualQueue));

        $response->assertForbidden();
    }
}
