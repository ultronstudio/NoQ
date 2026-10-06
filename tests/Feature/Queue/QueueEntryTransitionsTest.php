<?php

namespace Tests\Feature\Queue;

use App\Actions\Queue\CancelEntry;
use App\Actions\Queue\CompleteEntry;
use App\Actions\Queue\MarkEntryNoShow;
use App\Enums\QueueEntryStatus;
use App\Enums\StatusChangeActor;
use App\Exceptions\Queue\InvalidQueueEntryTransitionException;
use App\Models\QueueEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueEntryTransitionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_moves_a_called_entry_to_completed_with_history(): void
    {
        $entry = QueueEntry::factory()->called()->create();
        $operator = User::factory()->create();

        $completed = (new CompleteEntry)($entry, $operator);

        $this->assertSame(QueueEntryStatus::Completed, $completed->status);

        $history = $completed->statusChanges()->latest('id')->first();
        $this->assertSame(QueueEntryStatus::Called, $history->from_status);
        $this->assertSame(QueueEntryStatus::Completed, $history->to_status);
        $this->assertSame(StatusChangeActor::Operator, $history->actor);
    }

    public function test_cannot_complete_a_waiting_entry(): void
    {
        $entry = QueueEntry::factory()->create();

        $this->expectException(InvalidQueueEntryTransitionException::class);

        (new CompleteEntry)($entry);
    }

    public function test_cancel_is_allowed_from_waiting(): void
    {
        $entry = QueueEntry::factory()->create();

        $cancelled = (new CancelEntry)($entry);

        $this->assertSame(QueueEntryStatus::Cancelled, $cancelled->status);
    }

    public function test_cancel_is_allowed_from_called(): void
    {
        $entry = QueueEntry::factory()->called()->create();

        $cancelled = (new CancelEntry)($entry, StatusChangeActor::Operator);

        $this->assertSame(QueueEntryStatus::Cancelled, $cancelled->status);
    }

    public function test_no_show_is_allowed_from_waiting_and_called(): void
    {
        $waiting = QueueEntry::factory()->create();
        $called = QueueEntry::factory()->called()->create();

        $markNoShow = new MarkEntryNoShow;

        $this->assertSame(QueueEntryStatus::NoShow, $markNoShow($waiting)->status);
        $this->assertSame(QueueEntryStatus::NoShow, $markNoShow($called)->status);
    }

    public function test_terminal_statuses_cannot_transition_further(): void
    {
        $completed = QueueEntry::factory()->completed()->create();

        $this->expectException(InvalidQueueEntryTransitionException::class);

        (new CancelEntry)($completed);
    }

    public function test_cancelled_entry_cannot_be_marked_no_show(): void
    {
        $entry = QueueEntry::factory()->create();
        $cancelled = (new CancelEntry)($entry);

        $this->expectException(InvalidQueueEntryTransitionException::class);

        (new MarkEntryNoShow)($cancelled);
    }

    public function test_every_transition_creates_exactly_one_new_history_record(): void
    {
        $entry = QueueEntry::factory()->called()->create();

        $this->assertSame(0, $entry->statusChanges()->count());

        (new CompleteEntry)($entry);

        $this->assertSame(1, $entry->statusChanges()->count());
    }
}
