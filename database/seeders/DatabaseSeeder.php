<?php

namespace Database\Seeders;

use App\Actions\Queue\CallNextEntry;
use App\Actions\Queue\CompleteEntry;
use App\Actions\Queue\JoinQueue;
use App\Actions\Queue\OpenQueueSession;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $this->seedDemoQueue();
    }

    /**
     * A small, deterministic virtual queue with a realistic mix of entry
     * statuses, built through the real domain actions so its status
     * history is always consistent with the current state.
     */
    private function seedDemoQueue(): void
    {
        $operator = User::factory()->create([
            'name' => 'Demo operátor',
            'email' => 'demo@noq.local',
        ]);

        $virtualQueue = VirtualQueue::factory()->for($operator)->create([
            'name' => 'Studijní oddělení',
            'description' => 'Podání žádostí a konzultace ke studiu.',
        ]);

        $counterOne = ServicePoint::factory()->for($virtualQueue)->create([
            'name' => 'Přepážka 1',
            'sort_order' => 1,
        ]);

        $counterTwo = ServicePoint::factory()->for($virtualQueue)->create([
            'name' => 'Přepážka 2',
            'sort_order' => 2,
        ]);

        $session = (new OpenQueueSession)($virtualQueue, $operator);

        $joinQueue = new JoinQueue;
        $callNextEntry = new CallNextEntry;
        $completeEntry = new CompleteEntry;

        for ($i = 0; $i < 6; $i++) {
            $joinQueue($session);
        }

        // #1 and #2: called at counter 1 and completed.
        $completeEntry($callNextEntry($session, $operator, $counterOne), $operator);
        $completeEntry($callNextEntry($session, $operator, $counterOne), $operator);

        // #3: called at counter 2, left waiting for the visitor to arrive.
        $callNextEntry($session, $operator, $counterTwo);

        // #4, #5, #6 remain Waiting.
    }
}
