<?php

namespace Tests\Feature\Queue;

use App\Livewire\Queue\Show;
use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Models\RoleAssignment;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShowComponentRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stranger_cannot_mount_the_component(): void
    {
        $stranger = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();

        $this->actingAs($stranger);

        Livewire::test(Show::class, ['virtualQueue' => $virtualQueue])
            ->assertForbidden();
    }

    public function test_worker_does_not_see_session_or_service_point_management_controls(): void
    {
        $worker = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();

        RoleAssignment::factory()->servicePointWorker()->create([
            'user_id' => $worker->id,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $servicePoint->id,
        ]);

        QueueSession::factory()->for($virtualQueue)->create();

        $this->actingAs($worker);

        Livewire::test(Show::class, ['virtualQueue' => $virtualQueue])
            ->assertOk()
            ->assertDontSee('Pozastavit')
            ->assertDontSee('Přidat')
            ->assertSee('Zavolat dalšího');
    }

    public function test_worker_calling_next_is_routed_to_their_own_service_point_even_if_others_are_free(): void
    {
        $worker = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();
        $ownPoint = ServicePoint::factory()->for($virtualQueue)->create();
        ServicePoint::factory()->for($virtualQueue)->create(); // a free point the worker does not operate

        RoleAssignment::factory()->servicePointWorker()->create([
            'user_id' => $worker->id,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $ownPoint->id,
        ]);

        $session = QueueSession::factory()->for($virtualQueue)->create();
        QueueEntry::factory()->for($session, 'session')->create();

        $this->actingAs($worker);

        Livewire::test(Show::class, ['virtualQueue' => $virtualQueue])
            ->call('callNext')
            ->assertDispatched('toast', variant: 'success');

        $this->assertSame($ownPoint->id, $session->entries()->first()->service_point_id);
    }

    public function test_supervisor_sees_management_controls(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();

        $this->actingAs($owner);

        Livewire::test(Show::class, ['virtualQueue' => $virtualQueue])
            ->assertSee('Otevřít frontu')
            ->assertSee('Přidat');
    }
}
