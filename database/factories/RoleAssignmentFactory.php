<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleAssignment>
 */
class RoleAssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role' => Role::QueueSupervisor,
            'virtual_queue_id' => VirtualQueue::factory(),
            'service_point_id' => null,
        ];
    }

    public function queueSupervisor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::QueueSupervisor,
            'service_point_id' => null,
        ]);
    }

    public function servicePointWorker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::ServicePointWorker,
        ]);
    }
}
