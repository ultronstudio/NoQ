<?php

namespace Tests\Feature\Queue;

use App\Actions\Queue\CloseQueueSession;
use App\Actions\Queue\OpenQueueSession;
use App\Actions\Queue\PauseQueueSession;
use App\Actions\Queue\ResumeQueueSession;
use App\Enums\QueueSessionStatus;
use App\Exceptions\Queue\QueueSessionStateException;
use App\Models\QueueSession;
use App\Models\User;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueSessionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_a_session_creates_it_open_with_next_number_one(): void
    {
        $operator = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();

        $session = (new OpenQueueSession)($virtualQueue, $operator);

        $this->assertSame(QueueSessionStatus::Open, $session->status);
        $this->assertSame(1, $session->next_number);
        $this->assertTrue($session->openedBy->is($operator));
        $this->assertNotNull($session->opened_at);
    }

    public function test_cannot_open_a_second_active_session_for_the_same_queue(): void
    {
        $operator = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();

        $open = new OpenQueueSession;
        $open($virtualQueue, $operator);

        $this->expectException(QueueSessionStateException::class);

        $open($virtualQueue, $operator);
    }

    public function test_can_open_a_new_session_after_the_previous_one_was_closed(): void
    {
        $operator = User::factory()->create();
        $virtualQueue = VirtualQueue::factory()->create();

        $open = new OpenQueueSession;
        $first = $open($virtualQueue, $operator);
        (new CloseQueueSession)($first, $operator);

        $second = $open($virtualQueue, $operator);

        $this->assertSame(QueueSessionStatus::Open, $second->status);
        $this->assertNotSame($first->id, $second->id);
    }

    public function test_pause_moves_an_open_session_to_paused(): void
    {
        $session = QueueSession::factory()->create();

        $paused = (new PauseQueueSession)($session);

        $this->assertSame(QueueSessionStatus::Paused, $paused->status);
    }

    public function test_cannot_pause_a_session_that_is_not_open(): void
    {
        $session = QueueSession::factory()->closed()->create();

        $this->expectException(QueueSessionStateException::class);

        (new PauseQueueSession)($session);
    }

    public function test_resume_moves_a_paused_session_back_to_open(): void
    {
        $session = QueueSession::factory()->paused()->create();

        $resumed = (new ResumeQueueSession)($session);

        $this->assertSame(QueueSessionStatus::Open, $resumed->status);
    }

    public function test_cannot_resume_a_closed_session(): void
    {
        $session = QueueSession::factory()->closed()->create();

        $this->expectException(QueueSessionStateException::class);

        (new ResumeQueueSession)($session);
    }

    public function test_close_sets_closed_at_and_closed_by(): void
    {
        $session = QueueSession::factory()->create();
        $closer = User::factory()->create();

        $closed = (new CloseQueueSession)($session, $closer);

        $this->assertSame(QueueSessionStatus::Closed, $closed->status);
        $this->assertNotNull($closed->closed_at);
        $this->assertTrue($closed->closedBy->is($closer));
    }

    public function test_a_closed_session_cannot_be_closed_again(): void
    {
        $session = QueueSession::factory()->closed()->create();
        $closer = User::factory()->create();

        $this->expectException(QueueSessionStateException::class);

        (new CloseQueueSession)($session, $closer);
    }
}
