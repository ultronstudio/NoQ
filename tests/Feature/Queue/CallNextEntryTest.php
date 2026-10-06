<?php

namespace Tests\Feature\Queue;

use App\Actions\Queue\CallNextEntry;
use App\Actions\Queue\JoinQueue;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Exceptions\Queue\InvalidServicePointException;
use App\Exceptions\Queue\NoWaitingQueueEntriesException;
use App\Models\QueueSession;
use App\Models\ServicePoint;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CallNextEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calls_the_waiting_entry_with_the_lowest_number(): void
    {
        $session = QueueSession::factory()->create();
        $join = new JoinQueue;
        $join($session);
        $second = $join($session)->entry;
        $join($session);

        $callNext = new CallNextEntry;

        // #1 gets called first, so the next call must pick #2.
        $callNext($session);
        $called = $callNext($session);

        $this->assertTrue($called->is($second));
        $this->assertSame(QueueEntryStatus::Called, $called->refresh()->status);
    }

    public function test_it_creates_a_status_history_record_for_the_call(): void
    {
        $session = QueueSession::factory()->create();
        $operator = User::factory()->create();
        (new JoinQueue)($session);

        $called = (new CallNextEntry)($session, $operator);

        $history = $called->statusChanges()->latest('id')->first();
        $this->assertSame(QueueEntryStatus::Waiting, $history->from_status);
        $this->assertSame(QueueEntryStatus::Called, $history->to_status);
        $this->assertSame(StatusChangeActor::Operator, $history->actor);
        $this->assertTrue($history->changedBy->is($operator));
    }

    public function test_it_throws_when_there_are_no_waiting_entries(): void
    {
        $session = QueueSession::factory()->create();

        $this->expectException(NoWaitingQueueEntriesException::class);

        (new CallNextEntry)($session);
    }

    public function test_it_assigns_the_given_service_point_to_the_called_entry(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $servicePoint = ServicePoint::factory()->for($virtualQueue)->create();
        (new JoinQueue)($session);

        $called = (new CallNextEntry)($session, null, $servicePoint);

        $this->assertTrue($called->servicePoint->is($servicePoint));
    }

    public function test_it_rejects_a_service_point_from_another_virtual_queue(): void
    {
        $session = QueueSession::factory()->create();
        $foreignServicePoint = ServicePoint::factory()->create();
        (new JoinQueue)($session);

        $this->expectException(InvalidServicePointException::class);

        (new CallNextEntry)($session, null, $foreignServicePoint);
    }

    public function test_it_rejects_an_inactive_service_point(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();
        $inactiveServicePoint = ServicePoint::factory()->for($virtualQueue)->inactive()->create();
        (new JoinQueue)($session);

        $this->expectException(InvalidServicePointException::class);

        (new CallNextEntry)($session, null, $inactiveServicePoint);
    }
}
