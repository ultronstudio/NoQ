<?php

namespace Tests\Feature\Queue;

use App\Livewire\Queue\Settings;
use App\Models\RoleAssignment;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SettingsComponentTest extends TestCase
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

        Livewire::test(Settings::class, ['virtualQueue' => $virtualQueue])
            ->assertForbidden();
    }

    public function test_the_form_is_prefilled_with_current_values(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create([
            'name' => 'Stará fronta',
            'description' => 'Starý popis.',
            'max_waiting' => 15,
        ]);

        $this->actingAs($owner);

        Livewire::test(Settings::class, ['virtualQueue' => $virtualQueue])
            ->assertSet('name', 'Stará fronta')
            ->assertSet('description', 'Starý popis.')
            ->assertSet('maxWaiting', 15);
    }

    public function test_owner_can_update_name_description_and_max_waiting(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create([
            'name' => 'Stará fronta',
            'max_waiting' => null,
        ]);

        $this->actingAs($owner);

        Livewire::test(Settings::class, ['virtualQueue' => $virtualQueue])
            ->set('name', 'Nový název')
            ->set('description', 'Nový popis.')
            ->set('maxWaiting', 25)
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('queue.show', $virtualQueue));

        $virtualQueue->refresh();
        $this->assertSame('Nový název', $virtualQueue->name);
        $this->assertSame('Nový popis.', $virtualQueue->description);
        $this->assertSame(25, $virtualQueue->max_waiting);
    }

    public function test_delegated_supervisor_can_update_the_queue(): void
    {
        $supervisor = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();

        RoleAssignment::factory()->queueSupervisor()->create([
            'user_id' => $supervisor->id,
            'virtual_queue_id' => $virtualQueue->id,
        ]);

        $this->actingAs($supervisor);

        Livewire::test(Settings::class, ['virtualQueue' => $virtualQueue])
            ->set('name', 'Spravováno vedoucím')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Spravováno vedoucím', $virtualQueue->refresh()->name);
    }

    public function test_max_waiting_can_be_cleared_back_to_unlimited(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create(['max_waiting' => 10]);

        $this->actingAs($owner);

        Livewire::test(Settings::class, ['virtualQueue' => $virtualQueue])
            ->set('maxWaiting', '')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNull($virtualQueue->refresh()->max_waiting);
    }

    public function test_an_empty_name_fails_validation(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();

        $this->actingAs($owner);

        Livewire::test(Settings::class, ['virtualQueue' => $virtualQueue])
            ->set('name', '')
            ->call('save')
            ->assertHasErrors('name');
    }

    public function test_a_zero_max_waiting_fails_validation(): void
    {
        $owner = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->for($owner)->create();

        $this->actingAs($owner);

        Livewire::test(Settings::class, ['virtualQueue' => $virtualQueue])
            ->set('maxWaiting', 0)
            ->call('save')
            ->assertHasErrors('maxWaiting');
    }
}
