<?php

namespace Tests\Feature\Queue;

use App\Actions\Queue\CancelEntry;
use App\Actions\Queue\JoinQueue;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Support\Queue\TrackingToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JoinQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_joining_an_open_session_assigns_sequential_numbers(): void
    {
        $session = QueueSession::factory()->create();
        $join = new JoinQueue;

        $first = $join($session);
        $second = $join($session);
        $third = $join($session);

        $this->assertSame(1, $first->entry->number);
        $this->assertSame(2, $second->entry->number);
        $this->assertSame(3, $third->entry->number);
        $this->assertSame(4, $session->refresh()->next_number);
    }

    public function test_only_an_open_session_accepts_new_entries(): void
    {
        $session = QueueSession::factory()->paused()->create();

        $this->expectException(QueueSessionStateException::class);

        (new JoinQueue)($session);
    }

    public function test_a_closed_session_does_not_accept_new_entries(): void
    {
        $session = QueueSession::factory()->closed()->create();

        $this->expectException(QueueSessionStateException::class);

        (new JoinQueue)($session);
    }

    public function test_new_entry_starts_waiting_with_an_initial_history_record(): void
    {
        $session = QueueSession::factory()->create();

        $result = (new JoinQueue)($session);
        $entry = $result->entry;

        $this->assertSame(QueueEntryStatus::Waiting, $entry->status);

        $history = $entry->statusChanges()->first();
        $this->assertNotNull($history);
        $this->assertNull($history->from_status);
        $this->assertSame(QueueEntryStatus::Waiting, $history->to_status);
        $this->assertSame(StatusChangeActor::Visitor, $history->actor);
    }

    public function test_raw_tracking_token_is_never_persisted(): void
    {
        $session = QueueSession::factory()->create();

        $result = (new JoinQueue)($session);

        $this->assertDatabaseMissing('queue_entries', [
            'tracking_token_hash' => $result->trackingToken,
        ]);

        $row = DB::table('queue_entries')->find($result->entry->id);
        $this->assertNotContains($result->trackingToken, (array) $row);
        $this->assertSame($result->entry->tracking_token_hash, $row->tracking_token_hash);
        $this->assertNotSame($result->trackingToken, $row->tracking_token_hash);
    }

    public function test_an_entry_can_be_looked_up_by_the_hash_of_its_raw_token(): void
    {
        $session = QueueSession::factory()->create();

        $result = (new JoinQueue)($session);

        $found = QueueEntry::query()
            ->where('tracking_token_hash', TrackingToken::hash($result->trackingToken))
            ->first();

        $this->assertNotNull($found);
        $this->assertTrue($found->is($result->entry));
    }

    public function test_position_counts_only_waiting_entries_ahead_in_the_same_session(): void
    {
        $session = QueueSession::factory()->create();
        $join = new JoinQueue;

        $one = $join($session)->entry;
        $two = $join($session)->entry;
        $three = $join($session)->entry;

        $this->assertSame(1, $one->position());
        $this->assertSame(2, $two->position());
        $this->assertSame(3, $three->position());

        (new CancelEntry)($one);

        $this->assertSame(1, $two->position());
        $this->assertSame(2, $three->position());
    }
}
