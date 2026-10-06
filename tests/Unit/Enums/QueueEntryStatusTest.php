<?php

namespace Tests\Unit\Enums;

use App\Enums\QueueEntryStatus;
use Tests\TestCase;

class QueueEntryStatusTest extends TestCase
{
    public function test_waiting_can_transition_to_called_cancelled_or_no_show(): void
    {
        $this->assertTrue(QueueEntryStatus::Waiting->canTransitionTo(QueueEntryStatus::Called));
        $this->assertTrue(QueueEntryStatus::Waiting->canTransitionTo(QueueEntryStatus::Cancelled));
        $this->assertTrue(QueueEntryStatus::Waiting->canTransitionTo(QueueEntryStatus::NoShow));
    }

    public function test_called_can_transition_to_completed_cancelled_or_no_show(): void
    {
        $this->assertTrue(QueueEntryStatus::Called->canTransitionTo(QueueEntryStatus::Completed));
        $this->assertTrue(QueueEntryStatus::Called->canTransitionTo(QueueEntryStatus::Cancelled));
        $this->assertTrue(QueueEntryStatus::Called->canTransitionTo(QueueEntryStatus::NoShow));
    }

    public function test_called_cannot_transition_back_to_waiting(): void
    {
        $this->assertFalse(QueueEntryStatus::Called->canTransitionTo(QueueEntryStatus::Waiting));
    }

    public function test_terminal_statuses_allow_no_further_transition(): void
    {
        foreach ([QueueEntryStatus::Completed, QueueEntryStatus::Cancelled, QueueEntryStatus::NoShow] as $terminal) {
            $this->assertTrue($terminal->isTerminal());
            $this->assertSame([], $terminal->allowedTransitions());

            foreach (QueueEntryStatus::cases() as $target) {
                $this->assertFalse($terminal->canTransitionTo($target));
            }
        }
    }

    public function test_waiting_and_called_are_not_terminal(): void
    {
        $this->assertFalse(QueueEntryStatus::Waiting->isTerminal());
        $this->assertFalse(QueueEntryStatus::Called->isTerminal());
    }
}
