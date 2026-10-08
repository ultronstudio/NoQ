<?php

namespace Tests\Feature\Queue;

use App\Actions\Queue\SelectServicePointToCall;
use App\Enums\QueueEntryStatus;
use App\Models\QueueEntry;
use App\Models\QueueEntryStatusChange;
use App\Models\QueueSession;
use App\Models\ServicePoint;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectServicePointToCallTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_service_point_that_never_served_anyone_is_picked_over_one_with_history(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $everServed = ServicePoint::factory()->for($virtualQueue)->create();
        $neverServed = ServicePoint::factory()->for($virtualQueue)->create();

        QueueEntryStatusChange::factory()->create([
            'service_point_id' => $everServed->id,
            'to_status' => QueueEntryStatus::Completed,
            'created_at' => now()->subMinute(),
        ]);

        $selected = (new SelectServicePointToCall)($session, collect([$everServed, $neverServed]));

        $this->assertTrue($selected->is($neverServed));
    }

    public function test_the_longest_idle_service_point_is_picked_among_those_with_history(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $recentlyFreed = ServicePoint::factory()->for($virtualQueue)->create();
        $longIdle = ServicePoint::factory()->for($virtualQueue)->create();

        QueueEntryStatusChange::factory()->create([
            'service_point_id' => $recentlyFreed->id,
            'to_status' => QueueEntryStatus::Completed,
            'created_at' => now()->subMinute(),
        ]);

        QueueEntryStatusChange::factory()->create([
            'service_point_id' => $longIdle->id,
            'to_status' => QueueEntryStatus::NoShow,
            'created_at' => now()->subHour(),
        ]);

        $selected = (new SelectServicePointToCall)($session, collect([$recentlyFreed, $longIdle]));

        $this->assertTrue($selected->is($longIdle));
    }

    public function test_busy_service_points_are_excluded(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $busy = ServicePoint::factory()->for($virtualQueue)->create();
        $free = ServicePoint::factory()->for($virtualQueue)->create();

        QueueEntry::factory()->for($session, 'session')->called()->create(['service_point_id' => $busy->id]);

        $selected = (new SelectServicePointToCall)($session, collect([$busy, $free]));

        $this->assertTrue($selected->is($free));
    }

    public function test_inactive_service_points_are_excluded(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $inactive = ServicePoint::factory()->inactive()->for($virtualQueue)->create();

        $selected = (new SelectServicePointToCall)($session, collect([$inactive]));

        $this->assertNull($selected);
    }

    public function test_returns_null_when_nothing_is_eligible(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();

        $selected = (new SelectServicePointToCall)($session, collect());

        $this->assertNull($selected);
    }
}
