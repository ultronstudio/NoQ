<?php

namespace App\Livewire\Auth;

use App\Enums\QueueEntryStatus;
use App\Models\QueueEntry;
use App\Models\VirtualQueue;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Throwable;

class TimeWelcome extends Component
{
    public string $title;

    public string $subtitle;

    public function mount(): void
    {
        [$this->title, $this->subtitle] = $this->getTimedMessage();
    }

    public function refresh(): void
    {
        [$this->title, $this->subtitle] = $this->getTimedMessage();
    }

    private function getTimedMessage(): array
    {
        $hour = now()->hour;

        $greeting = match (true) {
            $hour >= 5 && $hour < 9 => 'Dobré ráno',
            $hour >= 9 && $hour < 12 => 'Dobré dopoledne',
            $hour >= 12 && $hour < 18 => 'Dobré odpoledne',
            $hour >= 18 && $hour < 22 => 'Dobrý večer',
            default => 'Příjemnou noc',
        };

        $stats = $this->liveStats();

        $messages = [
            // Fronty, čekání a NoQ
            'Život je krátký. Fronty by měly být také.',
            'Čekání je umění. My ho raději automatizujeme.',
            'Kdo si počká, ten se dočká. Prý.',
            'Fronta není závod. Ale někteří to pořád zkoušejí.',
            'Káva chutná lépe mimo čekárnu.',
            'Všichni jsme si rovni. Jen někdo má nižší pořadové číslo.',
            'Čas běží. Fronta se snad také posouvá.',
            'Nejlepší fronta je ta, ve které nemusíte stát.',
            'Pořádek musí být. Alespoň v databázi.',
            'Někdo počítá ovečky, my raději pořadová čísla.',
            'Předbíhat se nemá. Ani digitálně.',
            'Fronty nezmizí. Ale alespoň už nemusí zabírat chodbu.',
            'Čekání nemusí být kontaktní sport.',
            'Pořadové číslo není životní úspěch. Ale potěší.',
            'Nikdo nechce být poslední. Někdo ale musí.',
            'V NoQ má každý své místo. Doslova.',
            'Méně postávání, více života.',
            'Fronta bez tlačenice. Civilizace pokročila.',
            'Vaše pořadí je důležité. Vaše lokty už méně.',
            'Čekárna není místo, kde musíte zestárnout.',
            'Někdy je nejlepší cesta dopředu prostě počkat.',

            // Sarkasmus a ironie
            'Váš čas je pro nás důležitý. Proto ho počítáme.',
            'Děkujeme za trpělivost. Jinou možnost zatím nemáte.',
            'Všichni chtějí být první. Překvapivě.',
            'Čekání je zdarma. Alespoň zatím.',
            fn (array $stats): ?string => $stats['waitingCount'] !== null && $stats['waitingCount'] >= 2
                ? "Váš požadavek má vysokou prioritu. Stejně jako dalších {$stats['waitingCount']}."
                : null,
            'Fronta se nehýbe? Alespoň máte čas přemýšlet o životě.',
            'Čekání na zázrak? Zkuste raději sledovat své pořadí.',
            'Říká se, že čas jsou peníze. Fronty jsou tedy drahý koníček.',
            'Kdo předbíhá, ten se dočká. Problémů.',
            'Fronty učí trpělivosti. Někdy až příliš důkladně.',
            'Všechno jde podle pořadí. Dokud někdo nechce výjimku.',
            'Pořadí je spravedlivé. Lidská trpělivost už méně.',
            'Váš čas nám není lhostejný. Proto tu máme časovač.',
            fn (array $stats): ?string => $stats['randomTicketNumber'] !== null
                ? "Dnes je krásný den na to nebýt číslo {$stats['randomTicketNumber']}."
                : null,
            'Některé věci nepočkají. Bohužel, fronty ano.',
            'Čekání je součástí života. Bohužel ne ta zábavná.',
            'Nejhorší na čekání je, že za něj nedostanete zaplaceno.',
            'Všichni spěchají. Nikdo ale nechce pustit dalšího.',
            'Jedna fronta, sto názorů na to, kdo je další.',
            'Pořadové číslo vám nedá přednost. Ani když na něj budete zírat.',
            'Systém vás nepředběhne. Někteří lidé by to zkusili.',
            'Pět minut čekání. Pocitově tři pracovní dny.',
            'Když už musíte čekat, můžete u toho alespoň sedět.',
            'Někdo vidí frontu. My vidíme příležitost k optimalizaci.',

            // IT humor, ale výhradně o frontách
            'FIFO. Protože anarchie ve frontě není dobrý nápad.',
            'Fronta není bug. Je to datová struktura.',
            'Na rozdíl od nekonečné smyčky by každá fronta měla skončit.',
            'HTTP 200. Vaše pořadí stále existuje.',
            'HTTP 404. Trpělivost nenalezena.',
            'HTTP 408. Trpělivost vypršela.',
            'HTTP 429. Příliš mnoho lidí. Zkuste chvíli dýchat.',
            'HTTP 503. Odbavování si vzalo přestávku.',
            'Vaše pořadí je uloženo. Vaše nervy bohužel ne.',
            'Fronta je synchronizovaná. Lidé už méně.',
            'Digitální fronta. Protože papírové lístky také jednou dojdou.',
            'Realtime aktualizace. Protože obnovovat stránku každou sekundu je přežitek.',
            'Každá fronta má svůj konec. Pokud správně funguje worker.',
            'Pořadová čísla rostou. Doufejme, že ne exponenciálně.',
            'Databáze si vaše pořadí pamatuje. Vy nemusíte.',
            'Zpracováváme požadavky. Prosíme, nezpracovávejte nás.',
            'Nekonečná fronta je zajímavá teorie. V praxi méně.',
            'Queue overflow. To už zní jako problém.',
            'Systém běží. Fronta stojí. Technicky je všechno v pořádku.',
            'Člověk míní, fronta mění. Scheduler rozhoduje.',
            fn (array $stats): ?string => $stats['waitingCount'] !== null && $stats['waitingCount'] >= 1
                ? 'V produkci se netestuje. Hlavně když '.$this->waitingPeopleClause($stats['waitingCount']).'.'
                : null,
            'Restart fronty není totéž co restart trpělivosti.',
            'Ctrl+Z na předbíhání bohužel neexistuje.',
            'Jeden požadavek odbavíte, dva další přijdou. Klasika.',
            'Na mém počítači ta fronta fungovala.',
            'To není zaseknutá fronta. To je velmi dlouhý interval aktualizace.',
            'Bug ve frontě? Gratulujeme, právě jste v testovacím scénáři.',
            'Odbavení dokončeno. Další proces, prosím.',
            'Žádný race condition. Tady rozhoduje pořadí.',
            'Ne každá fronta potřebuje load balancer. Některé potřebují pauzu.',

            // Byrokracie a absurdní čekání
            'Pro zkrácení čekání prosím vyplňte dalších pět formulářů.',
            'Váš požadavek byl přijat. Nyní čeká na přijetí.',
            'Pro urychlení odbavení jsme vytvořili další frontu.',
            'Čekáte na potvrzení, že můžete začít čekat.',
            'Váš podnět byl předán příslušnému pořadovému číslu.',
            'Oddělení rychlého odbavení má dnes zavřeno.',
            'Pro přidělení pořadového čísla si vyzvedněte pořadové číslo.',
            'Vaše žádost o přednostní odbavení čeká ve frontě.',
            'Všechny fronty jsou důležité. Některé mají vlastní formulář.',
            'Děkujeme, že čekáte. Bez vás by tu byla nuda.',
            'Digitalizace: Místo papírku teď čekáte s telefonem.',
            'Čekání bylo úspěšně zahájeno. Gratulujeme.',
            'Vaše pořadí bylo aktualizováno. Životní situace nikoliv.',
            'Zrychlili jsme evidenci čekání. Samotné čekání je jiný projekt.',
            'Prosíme o trpělivost při zpracování žádosti o trpělivost.',
            'Fronta na odstranění fronty je momentálně plná.',
            'Váš požadavek byl označen jako urgentní. Spolu s ostatními.',
            'Problém s frontou? Zařaďte se do fronty na podporu.',

            // Suchý humor a drobné absurdity
            'Lepší už být ve frontě, než nebýt v ní vůbec.',
            'Ranní ptáče má nižší pořadové číslo.',
            'Ve frontě poznáš sám sebe. A ostatní.',
            'Kde není fronta, tam je čekání. A kde není čekání, tam je něco nečekaného.',
            'Kdo dřív přijde, ten míň čeká.',
            'I tesař ve frontě se někdy utne.',
            'Žádný učený frontu nepředběhne.',
            'Bez pořadového čísla do fronty nelez.',
            'Kdo je ve frontě, ten se počítá',
            'Když můžeš ve frontě stát dnes, můžeš stát i zítra.',
            'Co se ve frontě naučíš, to mimo frontu neuplatníš.',
            'Fronta má velké oči. A lidé v ní velké nervy.',
            'Chodí štěstí dokola... Ale fronta stojí.',
            'Čekající nepadá daleko od fronty.',
            'Jak se do fronty volá, tak se z ní ozývá.',
            'Jaká fronta, taková nálada.',
            'Jen blbec se dvakrát zařadí do fronty za stejným účelem.',
            'Čert může i do fronty',
            'Kdo jsi bez pořadového čísla, hoď kamenem mezi čekající.',
            'Kdo uteče, přijde o své pořadové číslo.',
            'Když čekajícího lapají, pěkně velkou frontu mu dají.',
            'V koho frontě čekáš, toho pořadové číslo máš.',
            'Kovářovic kobyla taky musí stát ve frontě.',
            'Ani láska frontu nepřenese.',
            'Moudřejší ustoupí, ale ve frontě se to nevyplácí.',
            'Nekupuj pořadové číslo v pytli.',
            'Nemusí pršet, jen když je fronta pod střechou.',
            'Není každý den posvícení, ale fronta je pořád.',
            'Není fronta bez práce, ani práce bez fronty.',
            'Po frontě je každý generál.',
            'Pod frontou bývá největší tma.',
            'S frontou roste i čekací doba.',
            'Stará fronta nerezaví.',
            'Už odbavený tomu ve frontě nevěří.',
            'Kde není fronta, není na co čekat.',
            'Prokrastinace je také forma čekání. Jen dobrovolná.',
            'Fronta je jediné místo, kde nikdo nechce být populární.',
            'Čekárna je místo, kde i hodiny vypadají unaveně.',
            'Každé číslo má svůj příběh. Některá velmi dlouhý.',
            'Trpělivost je ctnost. Ale má své limity.',
            'Někdo sbírá známky. My sbíráme pořadová čísla.',
            'Čekání ve frontě je jako multiplayer bez zábavy.',
            'Pořadové číslo je jako los. Jen výhru dostanete nakonec.',
            'Některé fronty jsou tak dlouhé, že by potřebovaly přestávku.',
            'Ve frontě čas plyne jinak. Věda to zatím nevysvětlila.',
            'Pořadí se mění. Zákony fyziky zatím zůstávají.',
            'Fronta je společenská událost, na kterou nikdo nechce.',
            function (array $stats): ?string {
                $n = $this->pickRandomStat($stats, ['waitingCount', 'servedTodayCount', 'calledCount']);

                return $n !== null ? "Když máte číslo {$n}, odpověď už možná znáte." : null;
            },
            'Ve frontě jsme všichni NPC. Dokud nepřijde naše číslo.',
            'Život není fér. Fronta by alespoň být mohla.',
            'Pokud se fronta nehýbe, zkuste na ni přestat koukat.',
            'Méně čekání znamená více času na další čekání.',
            'Fronty jsou důkazem, že lidstvo stále neumí teleportaci.',

            // Trocha moudra s vazbou na NoQ
            'Čas je drahý. Fronty ho rozdávají zdarma.',
            'Nejlepší systém je ten, při kterém nemusíte přemýšlet nad pořadím.',
            'Dobrá organizace začíná tam, kde končí chaos.',
            'Každá ušetřená minuta má smysl.',
            'Není důležité jen to, jak rychle odbavujete, ale i jak lidé čekají.',

            // Živá data z databáze
            fn (array $stats): ?string => $stats['waitingCount'] !== null
                ? 'Aktuálně '.$this->waitingPeopleClause($stats['waitingCount']).'. Trpělivost zatím není v databázi.'
                : null,
            fn (array $stats): ?string => $stats['activeQueuesCount'] !== null
                ? ucfirst($this->activeQueuesPhrase($stats['activeQueuesCount'])).'. Chaos má konečně vlastní evidenci.'
                : null,
            fn (array $stats): ?string => $stats['servedTodayCount'] !== null
                ? 'Dnes už '.$this->servedTodayClause($stats['servedTodayCount']).'. Civilizace postupuje.'
                : null,
            fn (array $stats): ?string => $stats['randomTicketNumber'] !== null
                ? "Číslo {$stats['randomTicketNumber']} stále čeká. Dramatická zápletka pokračuje."
                : null,
            fn (array $stats): ?string => $stats['randomQueueWaitingCount'] !== null
                ? 'V jedné z aktivních front '.$this->waitingPeopleClause($stats['randomQueueWaitingCount']).'. Ostatní to nechávají být.'
                : null,
            fn (array $stats): ?string => $stats['totalQueuesCount'] !== null
                ? 'V systému celkem existuje '.$this->totalQueuesPhrase($stats['totalQueuesCount']).'. Některé právě žijí, jiné jen tak postávají.'
                : null,
            fn (array $stats): ?string => $stats['waitingCount'] !== null
                ? ucfirst($this->waitingVisitorsPhrase($stats['waitingCount'])).'. Trpělivost zatím nikdo nezměřil.'
                : null,

            // Klid v systému (žádná data k předvedení)
            fn (array $stats): ?string => $stats['waitingCount'] === 0 ? 'Momentálně nikdo nečeká. Podezřele klidný den.' : null,
            fn (array $stats): ?string => $stats['waitingCount'] === 0 ? 'Fronty jsou prázdné. Něco se určitě chystá.' : null,
            fn (array $stats): ?string => $stats['waitingCount'] === 0 ? 'Nula čekajících. Statistiky vypadají skoro podezřele.' : null,
            fn (array $stats): ?string => $stats['activeQueuesCount'] === 0 ? 'Žádná fronta? To je technicky nejlepší možná fronta.' : null,

            // Statistiky nejsou k dispozici
            fn (array $stats): ?string => $stats['available'] === false ? 'Statistiky si dnes daly pauzu. Fronty prý funguji dál.' : null,
        ];

        $candidates = [];

        foreach ($messages as $message) {
            $text = $message instanceof \Closure ? $message($stats) : $message;

            if ($text !== null) {
                $candidates[] = $text;
            }
        }

        return [
            $greeting.'!',
            $candidates[array_rand($candidates)],
        ];
    }

    /**
     * Snapshot of live, publicly-safe queue statistics used to fill in the
     * dynamic parts of the welcome message. Computed once per call so a
     * single message never mixes numbers from different moments in time.
     *
     * Shown on the (unauthenticated) login page, so only aggregate counts
     * are exposed here — never anything tied to a specific user's queues.
     *
     * @return array{
     *     available: bool,
     *     waitingCount: int|null,
     *     activeQueuesCount: int|null,
     *     totalQueuesCount: int|null,
     *     servedTodayCount: int|null,
     *     randomTicketNumber: int|null,
     *     randomQueueWaitingCount: int|null,
     * }
     */
    private function liveStats(): array
    {
        $unavailable = [
            'available' => false,
            'waitingCount' => null,
            'activeQueuesCount' => null,
            'totalQueuesCount' => null,
            'servedTodayCount' => null,
            'calledCount' => null,
            'randomTicketNumber' => null,
            'randomQueueWaitingCount' => null,
        ];

        try {
            $queues = VirtualQueue::query()
                ->whereNull('archived_at')
                ->with(['activeSession' => fn ($query) => $query->withCount([
                    'entries as waiting_count' => fn ($query) => $query->where('status', QueueEntryStatus::Waiting),
                    'entries as called_count' => fn ($query) => $query->where('status', QueueEntryStatus::Called),
                ])])
                ->get();

            $activeQueues = $queues->filter(fn (VirtualQueue $queue): bool => $queue->activeSession !== null);

            $waitingCount = (int) $activeQueues->sum(fn (VirtualQueue $queue): int => $queue->activeSession->waiting_count);

            $calledCount = (int) $activeQueues->sum(fn (VirtualQueue $queue): int => $queue->activeSession->called_count);

            $servedTodayCount = QueueEntry::query()
                ->where('status', QueueEntryStatus::Completed)
                ->where('updated_at', '>=', now()->startOfDay())
                ->count();

            $randomQueueWaitingCount = null;
            $randomTicketNumber = null;

            if ($activeQueues->isNotEmpty()) {
                $randomQueue = $activeQueues->random();
                $randomQueueWaitingCount = $randomQueue->activeSession->waiting_count;

                $queuesWithWaiting = $activeQueues->filter(
                    fn (VirtualQueue $queue): bool => $queue->activeSession->waiting_count > 0
                );

                if ($queuesWithWaiting->isNotEmpty()) {
                    $ticketQueue = $queuesWithWaiting->random();
                    $offset = random_int(0, $ticketQueue->activeSession->waiting_count - 1);

                    $randomTicketNumber = QueueEntry::query()
                        ->where('queue_session_id', $ticketQueue->activeSession->id)
                        ->where('status', QueueEntryStatus::Waiting)
                        ->orderBy('number')
                        ->skip($offset)
                        ->value('number');
                }
            }

            return [
                'available' => true,
                'waitingCount' => $waitingCount,
                'activeQueuesCount' => $activeQueues->count(),
                'totalQueuesCount' => $queues->count(),
                'servedTodayCount' => $servedTodayCount,
                'calledCount' => $calledCount,
                'randomTicketNumber' => $randomTicketNumber,
                'randomQueueWaitingCount' => $randomQueueWaitingCount,
            ];
        } catch (Throwable $e) {
            report($e);

            return $unavailable;
        }
    }

    /**
     * Picks one of the given (already-computed) stats at random, skipping
     * any that are null (unavailable), so a message can reference "some
     * real number" without caring which specific stat it ends up being.
     *
     * @param  list<string>  $keys
     */
    private function pickRandomStat(array $stats, array $keys): ?int
    {
        $available = array_values(array_filter(
            array_map(fn (string $key) => $stats[$key] ?? null, $keys),
            fn (?int $value): bool => $value !== null,
        ));

        return $available === [] ? null : $available[array_rand($available)];
    }

    /**
     * "čeká 1 člověk" / "čekají 3 lidé" / "čeká 5 lidí" / "čeká 0 lidí".
     */
    private function waitingPeopleClause(int $n): string
    {
        return match (true) {
            $n === 1 => 'čeká 1 člověk',
            $n >= 2 && $n <= 4 => "čekají {$n} lidé",
            default => "čeká {$n} lidí",
        };
    }

    /**
     * "1 čekající návštěvník" / "3 čekající návštěvníci" / "5 čekajících návštěvníků".
     */
    private function waitingVisitorsPhrase(int $n): string
    {
        return match (true) {
            $n === 1 => '1 čekající návštěvník',
            $n >= 2 && $n <= 4 => "{$n} čekající návštěvníci",
            default => "{$n} čekajících návštěvníků",
        };
    }

    /**
     * "1 aktivní fronta" / "3 aktivní fronty" / "8 aktivních front".
     */
    private function activeQueuesPhrase(int $n): string
    {
        return match (true) {
            $n === 1 => '1 aktivní fronta',
            $n >= 2 && $n <= 4 => "{$n} aktivní fronty",
            default => "{$n} aktivních front",
        };
    }

    /**
     * "1 fronta" / "3 fronty" / "8 front".
     */
    private function totalQueuesPhrase(int $n): string
    {
        return match (true) {
            $n === 1 => '1 fronta',
            $n >= 2 && $n <= 4 => "{$n} fronty",
            default => "{$n} front",
        };
    }

    /**
     * "byl odbaven 1 návštěvník" / "byli odbaveni 3 návštěvníci" / "bylo odbaveno 5 návštěvníků".
     */
    private function servedTodayClause(int $n): string
    {
        return match (true) {
            $n === 1 => 'byl odbaven 1 návštěvník',
            $n >= 2 && $n <= 4 => "byli odbaveni {$n} návštěvníci",
            default => "bylo odbaveno {$n} návštěvníků",
        };
    }

    public function render(): View
    {
        return view('livewire.auth.time-welcome');
    }
}
