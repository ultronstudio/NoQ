<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\QueueOverviewTable;
use App\Models\RoleAssignment;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_super_admin_sees_every_queue(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $someoneElsesQueue = VirtualQueue::factory()->create(['name' => 'Cizí fronta']);

        $this->actingAs($superAdmin);

        Livewire::test(QueueOverviewTable::class)
            ->assertSee('Cizí fronta');
    }

    public function test_a_plain_user_does_not_see_queues_they_have_no_relation_to(): void
    {
        $user = User::factory()->create();
        VirtualQueue::factory()->create(['name' => 'Cizí fronta']);

        $this->actingAs($user);

        Livewire::test(QueueOverviewTable::class)
            ->assertDontSee('Cizí fronta');
    }

    public function test_a_delegated_supervisor_sees_a_queue_they_do_not_own(): void
    {
        $supervisor = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create(['name' => 'Spravovaná fronta']);

        RoleAssignment::factory()->queueSupervisor()->create([
            'user_id' => $supervisor->id,
            'virtual_queue_id' => $virtualQueue->id,
        ]);

        $this->actingAs($supervisor);

        Livewire::test(QueueOverviewTable::class)
            ->assertSee('Spravovaná fronta');
    }

    public function test_a_service_point_worker_sees_the_queue_their_point_belongs_to(): void
    {
        $worker = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create(['name' => 'Fronta s přepážkou']);
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();

        RoleAssignment::factory()->servicePointWorker()->create([
            'user_id' => $worker->id,
            'virtual_queue_id' => $virtualQueue->id,
            'service_point_id' => $servicePoint->id,
        ]);

        $this->actingAs($worker);

        Livewire::test(QueueOverviewTable::class)
            ->assertSee('Fronta s přepážkou')
            ->assertDontSee('Otevřít');
    }

    public function test_a_user_can_create_a_new_queue_and_is_redirected_to_it(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(QueueOverviewTable::class)
            ->set('name', 'Nová fronta')
            ->set('description', 'Popis nové fronty.')
            ->call('createQueue')
            ->assertHasNoErrors();

        $created = VirtualQueue::query()->where('name', 'Nová fronta')->sole();

        $this->assertSame($user->id, $created->user_id);
        $this->assertSame('Popis nové fronty.', $created->description);
    }

    public function test_creating_a_queue_without_a_name_fails_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Livewire::test(QueueOverviewTable::class)
            ->set('name', '')
            ->call('createQueue')
            ->assertHasErrors('name');
    }
}
