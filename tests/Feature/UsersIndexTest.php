<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_a_plain_user_may_not_view_the_users_index(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertForbidden();
    }

    public function test_a_super_admin_can_view_the_users_index(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $other = User::factory()->create(['name' => 'Jana Nováková']);

        $response = $this->actingAs($superAdmin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Jana Nováková');
    }

    public function test_the_users_index_lists_role_assignments_with_their_queue_and_role(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $worker = User::factory()->create(['name' => 'Petr Dvořák']);
        $virtualQueue = VirtualQueue::factory()->create(['name' => 'Pobočka Centrum']);

        RoleAssignment::factory()->queueSupervisor()->create([
            'user_id' => $worker->id,
            'virtual_queue_id' => $virtualQueue->id,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('users.index'));

        $response->assertOk();
        $response->assertSee('Petr Dvořák');
        $response->assertSee('Pobočka Centrum');
        $response->assertSee(Role::QueueSupervisor->label());
    }

    public function test_the_users_index_link_is_only_shown_to_super_admins(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $plainUser = User::factory()->create();

        $this->actingAs($superAdmin)
            ->get(route('dashboard'))
            ->assertSee('Uživatelé');

        $this->actingAs($plainUser)
            ->get(route('dashboard'))
            ->assertDontSee('Uživatelé');
    }
}
