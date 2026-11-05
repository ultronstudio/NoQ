<?php

namespace App\Livewire\Auth;

use Illuminate\Contracts\View\View;
use Livewire\Component;

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
            $hour >= 5 && $hour < 9  => 'Dobré ráno',
            $hour >= 9 && $hour < 12 => 'Dobré dopoledne',
            $hour >= 12 && $hour < 18 => 'Dobré odpoledne',
            $hour >= 18 && $hour < 22 => 'Dobrý večer',
            default => 'Příjemnou noc',
        };

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
            'Váš požadavek má vysokou prioritu. Stejně jako ostatních 47.',
            'Fronta se nehýbe? Alespoň máte čas přemýšlet o životě.',
            'Čekání na zázrak? Zkuste raději sledovat své pořadí.',
            'Říká se, že čas jsou peníze. Fronty jsou tedy drahý koníček.',
            'Kdo předbíhá, ten se dočká. Problémů.',
            'Fronty učí trpělivosti. Někdy až příliš důkladně.',
            'Všechno jde podle pořadí. Dokud někdo nechce výjimku.',
            'Pořadí je spravedlivé. Lidská trpělivost už méně.',
            'Váš čas nám není lhostejný. Proto tu máme časovač.',
            'Dnes je krásný den na to nebýt číslo 147.',
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
            'V produkci se netestuje. Hlavně když čeká třicet lidí.',
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
            'Ranní ptáče má nižší pořadové číslo. Obvykle.',
            'Kdo dřív přijde, ten dřív mele. Nebo alespoň čeká.',
            'Prokrastinace je také forma čekání. Jen dobrovolná.',
            'Fronta je jediné místo, kde nikdo nechce být populární.',
            'Čekárna: Místo, kde i hodiny vypadají unaveně.',
            'Každé číslo má svůj příběh. Některá velmi dlouhý.',
            'Trpělivost je ctnost. Ale má své limity.',
            'Někdo sbírá známky. My sbíráme pořadová čísla.',
            'Čekání ve frontě: Multiplayer bez zábavy.',
            'Pořadové číslo je jako los. Jen výhru dostanete nakonec.',
            'Některé fronty jsou tak dlouhé, že by potřebovaly přestávku.',
            'Ve frontě čas plyne jinak. Věda to zatím nevysvětlila.',
            'Pořadí se mění. Zákony fyziky zatím zůstávají.',
            'Fronta je společenská událost, na kterou nikdo nechce.',
            'Když máte číslo 42, odpověď už možná znáte.',
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
        ];

        return [
            $greeting . '!',
            $messages[array_rand($messages)],
        ];
    }

    public function render(): View
    {
        return view('livewire.auth.time-welcome');
    }
}
