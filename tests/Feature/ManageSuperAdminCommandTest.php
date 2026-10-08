<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageSuperAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_grants_super_admin_by_email(): void
    {
        $user = User::factory()->create();

        $this->artisan('noq:super-admin', ['email' => $user->email])
            ->assertSuccessful();

        $this->assertTrue($user->refresh()->is_super_admin);
    }

    public function test_it_revokes_super_admin_with_the_revoke_flag(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        $this->artisan('noq:super-admin', ['email' => $user->email, '--revoke' => true])
            ->assertSuccessful();

        $this->assertFalse($user->refresh()->is_super_admin);
    }

    public function test_it_fails_for_an_unknown_email(): void
    {
        $this->artisan('noq:super-admin', ['email' => 'nobody@example.com'])
            ->assertFailed();
    }
}
