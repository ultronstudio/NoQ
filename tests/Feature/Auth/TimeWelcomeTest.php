<?php

namespace Tests\Feature\Auth;

use App\Enums\QueueEntryStatus;
use App\Enums\QueueSessionStatus;
use App\Livewire\Auth\TimeWelcome;
use App\Models\QueueEntry;
use App\Models\QueueSession;
use App\Models\VirtualQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use ReflectionMethod;
use Tests\TestCase;

class TimeWelcomeTest extends TestCase
{
    use RefreshDatabase;

    private function liveStats(TimeWelcome $component): array
    {
        $method = new ReflectionMethod($component, 'liveStats');
        $method->setAccessible(true);

        return $method->invoke($component);
    }

    private function callPrivate(TimeWelcome $component, string $name, int $n): string
    {
        $method = new ReflectionMethod($component, $name);
        $method->setAccessible(true);

        return $method->invoke($component, $n);
    }

    private function pickRandomStat(TimeWelcome $component, array $stats, array $keys): ?int
    {
        $method = new ReflectionMethod($component, 'pickRandomStat');
        $method->setAccessible(true);

        return $method->invoke($component, $stats, $keys);
    }

    public function test_renders_on_login_page(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_no_active_queue_at_all(): void
    {
        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertTrue($stats['available']);
        $this->assertSame(0, $stats['waitingCount']);
        $this->assertSame(0, $stats['activeQueuesCount']);
        $this->assertSame(0, $stats['totalQueuesCount']);
        $this->assertNull($stats['randomTicketNumber']);
        $this->assertNull($stats['randomQueueWaitingCount']);

        $this->assertNotEmpty($component->get('title'));
        $this->assertNotEmpty($component->get('subtitle'));
    }

    public function test_active_queue_without_waiting_visitors(): void
    {
        $queue = VirtualQueue::factory()->create();
        QueueSession::factory()->for($queue, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);

        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertSame(0, $stats['waitingCount']);
        $this->assertSame(1, $stats['activeQueuesCount']);
        $this->assertSame(0, $stats['randomQueueWaitingCount']);
        $this->assertNull($stats['randomTicketNumber']);
    }

    public function test_single_waiting_visitor(): void
    {
        $queue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($queue, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);
        $entry = QueueEntry::factory()->for($session, 'session')->create([
            'number' => 7,
            'status' => QueueEntryStatus::Waiting,
        ]);

        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertSame(1, $stats['waitingCount']);
        $this->assertSame(1, $stats['activeQueuesCount']);
        $this->assertSame(1, $stats['randomQueueWaitingCount']);
        $this->assertSame($entry->number, $stats['randomTicketNumber']);
    }

    public function test_several_waiting_visitors_across_multiple_queues(): void
    {
        $queueA = VirtualQueue::factory()->create();
        $sessionA = QueueSession::factory()->for($queueA, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);
        QueueEntry::factory()->count(2)->for($sessionA, 'session')->create([
            'status' => QueueEntryStatus::Waiting,
        ]);

        $queueB = VirtualQueue::factory()->create();
        $sessionB = QueueSession::factory()->for($queueB, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Paused,
        ]);
        QueueEntry::factory()->count(3)->for($sessionB, 'session')->create([
            'status' => QueueEntryStatus::Waiting,
        ]);

        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertSame(5, $stats['waitingCount']);
        $this->assertSame(2, $stats['activeQueuesCount']);
        $this->assertNotNull($stats['randomTicketNumber']);
    }

    public function test_large_number_of_waiting_visitors(): void
    {
        $queue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($queue, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);
        QueueEntry::factory()->count(50)->for($session, 'session')->create([
            'status' => QueueEntryStatus::Waiting,
        ]);

        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertSame(50, $stats['waitingCount']);
        $this->assertNotNull($stats['randomTicketNumber']);
    }

    public function test_completed_cancelled_and_closed_session_entries_are_not_counted_as_waiting(): void
    {
        $queue = VirtualQueue::factory()->create();

        $openSession = QueueSession::factory()->for($queue, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);
        QueueEntry::factory()->for($openSession, 'session')->create(['status' => QueueEntryStatus::Completed]);
        QueueEntry::factory()->for($openSession, 'session')->create(['status' => QueueEntryStatus::Cancelled]);
        QueueEntry::factory()->for($openSession, 'session')->create(['status' => QueueEntryStatus::NoShow]);

        // A closed session with a leftover "waiting" row must not count either.
        $closedSession = QueueSession::factory()->closed()->for($queue, 'virtualQueue')->create();
        QueueEntry::factory()->for($closedSession, 'session')->create(['status' => QueueEntryStatus::Waiting]);

        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertSame(0, $stats['waitingCount']);
        $this->assertSame(1, $stats['activeQueuesCount']);
        $this->assertSame(0, $stats['randomQueueWaitingCount']);
        $this->assertNull($stats['randomTicketNumber']);
    }

    public function test_called_count_reflects_only_entries_currently_being_served_in_active_sessions(): void
    {
        $queue = VirtualQueue::factory()->create();

        $openSession = QueueSession::factory()->for($queue, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);
        QueueEntry::factory()->count(2)->for($openSession, 'session')->create(['status' => QueueEntryStatus::Called]);
        QueueEntry::factory()->for($openSession, 'session')->create(['status' => QueueEntryStatus::Waiting]);
        QueueEntry::factory()->for($openSession, 'session')->create(['status' => QueueEntryStatus::Completed]);

        // A "called" entry left over in a closed session must not count.
        $closedSession = QueueSession::factory()->closed()->for($queue, 'virtualQueue')->create();
        QueueEntry::factory()->for($closedSession, 'session')->create(['status' => QueueEntryStatus::Called]);

        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertSame(2, $stats['calledCount']);
        $this->assertSame(1, $stats['waitingCount']);
    }

    public function test_served_today_counts_only_entries_completed_today(): void
    {
        $queue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($queue, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);

        QueueEntry::factory()->for($session, 'session')->create(['status' => QueueEntryStatus::Completed]);
        QueueEntry::factory()->for($session, 'session')->create(['status' => QueueEntryStatus::Completed]);

        $yesterday = QueueEntry::factory()->for($session, 'session')->create(['status' => QueueEntryStatus::Completed]);
        $yesterday->updated_at = now()->subDay();
        $yesterday->save();

        $component = Livewire::test(TimeWelcome::class);
        $stats = $this->liveStats($component->instance());

        $this->assertSame(2, $stats['servedTodayCount']);
    }

    public function test_repeated_refresh_stays_consistent(): void
    {
        $queue = VirtualQueue::factory()->create();
        $session = QueueSession::factory()->for($queue, 'virtualQueue')->create([
            'status' => QueueSessionStatus::Open,
        ]);
        QueueEntry::factory()->count(3)->for($session, 'session')->create([
            'status' => QueueEntryStatus::Waiting,
        ]);

        $component = Livewire::test(TimeWelcome::class);

        for ($i = 0; $i < 10; $i++) {
            $component->call('refresh');

            $this->assertIsString($component->get('title'));
            $this->assertNotEmpty($component->get('title'));
            $this->assertIsString($component->get('subtitle'));
            $this->assertNotEmpty($component->get('subtitle'));
        }
    }

    public function test_pick_random_stat_skips_unavailable_keys(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $stats = ['waitingCount' => 3, 'servedTodayCount' => null, 'calledCount' => null];

        for ($i = 0; $i < 20; $i++) {
            $this->assertSame(
                3,
                $this->pickRandomStat($component, $stats, ['waitingCount', 'servedTodayCount', 'calledCount'])
            );
        }
    }

    public function test_pick_random_stat_returns_null_when_all_keys_unavailable(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $stats = ['waitingCount' => null, 'servedTodayCount' => null, 'calledCount' => null];

        $this->assertNull(
            $this->pickRandomStat($component, $stats, ['waitingCount', 'servedTodayCount', 'calledCount'])
        );
    }

    public function test_pick_random_stat_can_return_a_genuinely_zero_value(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $stats = ['waitingCount' => 0, 'servedTodayCount' => null, 'calledCount' => null];

        $this->assertSame(
            0,
            $this->pickRandomStat($component, $stats, ['waitingCount', 'servedTodayCount', 'calledCount'])
        );
    }

    public function test_czech_declension_for_waiting_people_clause(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $this->assertSame('čeká 0 lidí', $this->callPrivate($component, 'waitingPeopleClause', 0));
        $this->assertSame('čeká 1 člověk', $this->callPrivate($component, 'waitingPeopleClause', 1));
        $this->assertSame('čekají 2 lidé', $this->callPrivate($component, 'waitingPeopleClause', 2));
        $this->assertSame('čekají 4 lidé', $this->callPrivate($component, 'waitingPeopleClause', 4));
        $this->assertSame('čeká 5 lidí', $this->callPrivate($component, 'waitingPeopleClause', 5));
        $this->assertSame('čeká 47 lidí', $this->callPrivate($component, 'waitingPeopleClause', 47));
    }

    public function test_czech_declension_for_waiting_visitors_phrase(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $this->assertSame('0 čekajících návštěvníků', $this->callPrivate($component, 'waitingVisitorsPhrase', 0));
        $this->assertSame('1 čekající návštěvník', $this->callPrivate($component, 'waitingVisitorsPhrase', 1));
        $this->assertSame('2 čekající návštěvníci', $this->callPrivate($component, 'waitingVisitorsPhrase', 2));
        $this->assertSame('5 čekajících návštěvníků', $this->callPrivate($component, 'waitingVisitorsPhrase', 5));
    }

    public function test_czech_declension_for_active_queues_phrase(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $this->assertSame('0 aktivních front', $this->callPrivate($component, 'activeQueuesPhrase', 0));
        $this->assertSame('1 aktivní fronta', $this->callPrivate($component, 'activeQueuesPhrase', 1));
        $this->assertSame('3 aktivní fronty', $this->callPrivate($component, 'activeQueuesPhrase', 3));
        $this->assertSame('8 aktivních front', $this->callPrivate($component, 'activeQueuesPhrase', 8));
    }

    public function test_czech_declension_for_served_today_clause(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $this->assertSame('bylo odbaveno 0 návštěvníků', $this->callPrivate($component, 'servedTodayClause', 0));
        $this->assertSame('byl odbaven 1 návštěvník', $this->callPrivate($component, 'servedTodayClause', 1));
        $this->assertSame('byli odbaveni 3 návštěvníci', $this->callPrivate($component, 'servedTodayClause', 3));
        $this->assertSame('bylo odbaveno 5 návštěvníků', $this->callPrivate($component, 'servedTodayClause', 5));
    }

    public function test_falls_back_to_safe_message_when_stats_are_unavailable(): void
    {
        $component = Livewire::test(TimeWelcome::class)->instance();

        $method = new ReflectionMethod($component, 'getTimedMessage');
        $method->setAccessible(true);

        // Force the "unavailable" branch by dropping the queue_entries table
        // mid-test and confirm the component still produces a safe message
        // instead of throwing.
        Schema::drop('queue_entries');

        [$title, $subtitle] = $method->invoke($component);

        $this->assertNotEmpty($title);
        $this->assertNotEmpty($subtitle);
    }
}
