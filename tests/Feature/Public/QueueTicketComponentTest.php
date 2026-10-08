<?php

namespace Tests\Feature\Public;

use App\Enums\QueueEntryStatus;
use App\Livewire\Public\QueueTicket;
use App\Models\QueueSession;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QueueTicketComponentTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_public_page_is_reachable_without_authentication(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();

        $response = $this->get(route('queue.public', $virtualQueue));

        $response->assertOk();
    }

    public function test_a_visitor_can_join_an_open_queue_and_see_their_ticket(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();

        $component = Livewire::test(QueueTicket::class, ['virtualQueue' => $virtualQueue])
            ->call('join')
            ->assertHasNoErrors();

        $this->assertNotNull($component->get('token'));

        $entry = $session->entries()->sole();
        $this->assertSame(QueueEntryStatus::Waiting, $entry->status);

        $component->assertSee('#'.$entry->number);
    }

    public function test_joining_is_rejected_with_a_friendly_error_when_there_is_no_open_session(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();

        Livewire::test(QueueTicket::class, ['virtualQueue' => $virtualQueue])
            ->call('join')
            ->assertHasErrors('join');
    }

    public function test_a_visitor_can_cancel_their_own_waiting_ticket(): void
    {
        $virtualQueue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($virtualQueue)->create();

        $component = Livewire::test(QueueTicket::class, ['virtualQueue' => $virtualQueue])
            ->call('join');

        $entry = $session->entries()->sole();

        $component->call('cancel');

        $this->assertNull($component->get('token'));
        $this->assertSame(QueueEntryStatus::Cancelled, $entry->refresh()->status);
    }
}
