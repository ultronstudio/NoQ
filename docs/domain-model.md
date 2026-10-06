# Doménový model front

Tento dokument popisuje základní doménovou a databázovou vrstvu NoQ: entity,
jejich vztahy, stavové automaty a hlavní invarianty. Neobsahuje administrační
UI, veřejnou stránku fronty, QR generování, realtime eventy ani RBAC — to jsou
pozdější milníky, které stavějí na tomto základu.

## Entity

### User

Operátor/správce. Existoval už před tímto milníkem (Fortify). Vlastní
`VirtualQueue` záznamy a může otevírat/zavírat `QueueSession`.

### VirtualQueue

Dlouhodobá definice fronty, např. „Studijní oddělení“. Nese název, popis
a vlastníka (`user_id`). Veřejný identifikátor je `public_id` (ULID),
budoucí veřejná URL fronty i QR kód z něj budou vycházet — nikoliv z
interního `id`.

### QueueSession vs. VirtualQueue

`VirtualQueue` je definice, `QueueSession` je jedno konkrétní provozní
spuštění této definice. Pro „Studijní oddělení“ vznikne nová `QueueSession`
každý den, kdy se fronta znovu otevře. Díky tomu:

- historie front jednotlivých dnů/směn zůstává oddělená (každá session má
  svoje vlastní číslování od 1),
- uzavření dne nemaže ani neresetuje definici fronty,
- statistiky lze později počítat po jednotlivých sezeních i souhrnně.

### ServicePoint

Obslužné místo uvnitř `VirtualQueue` (např. „Přepážka 1“). Neaktivní
service pointy se nemažou (`is_active = false`), aby zůstala zachovaná
historie, ke kterému přepážce byl návštěvník obsloužen.

### QueueEntry

Anonymní pořadový lístek návštěvníka v rámci jedné `QueueSession`. Návštěvník
nikdy není `User` — nemá jméno, e-mail, telefon ani uloženou IP adresu.
Jediná vazba na návštěvníka je hash jeho tracking tokenu.

### QueueEntryStatusChange

Neměnný historický záznam jedné změny stavu `QueueEntry`. Nemá `updated_at`
(`QueueEntryStatusChange::UPDATED_AT === null`) — řádek se po vytvoření nikdy
needituje.

## Vztahy

```
User 1---N VirtualQueue
VirtualQueue 1---N QueueSession
VirtualQueue 1---N ServicePoint
QueueSession 1---N QueueEntry
ServicePoint 1---N QueueEntry (nullable, obsluhující přepážka)
QueueEntry 1---N QueueEntryStatusChange
ServicePoint 1---N QueueEntryStatusChange (nullable)
User 1---N QueueSession (opened_by_user_id, closed_by_user_id)
User 1---N QueueEntryStatusChange (changed_by_user_id, nullable)
```

Eloquent metody jsou v `App\Models\*` (`user()`, `sessions()`,
`servicePoints()`, `activeSession()`, `entries()`, `statusChanges()`, …) —
viz docblocky jednotlivých modelů pro přesné generické typy.

## Stavové enumy

- `App\Enums\QueueSessionStatus`: `Open`, `Paused`, `Closed`.
- `App\Enums\QueueEntryStatus`: `Waiting`, `Called`, `Completed`,
  `Cancelled`, `NoShow`.
- `App\Enums\StatusChangeActor`: `Visitor`, `Operator`, `System`.

## Stavový automat QueueSession

```
(vytvoření) -> Open
Open -> Paused   (PauseQueueSession)
Paused -> Open   (ResumeQueueSession)
Open -> Closed   (CloseQueueSession)
Paused -> Closed (CloseQueueSession)
```

`Closed` je terminální: nelze znovu otevřít, pozastavit ani obnovit
(`QueueSessionStateException`). Nová session pro stejnou `VirtualQueue`
vzniká pouze přes `OpenQueueSession`, a to jen pokud žádná jiná session
dané fronty není `Open` nebo `Paused` (viz Invarianty).

## Stavový automat QueueEntry

```
NULL -> Waiting     (JoinQueue, actor: Visitor)

Waiting -> Called     (CallNextEntry, actor: Operator)
Waiting -> Cancelled  (CancelEntry)
Waiting -> NoShow     (MarkEntryNoShow)

Called -> Completed  (CompleteEntry, actor: Operator)
Called -> Cancelled   (CancelEntry)
Called -> NoShow      (MarkEntryNoShow)
```

`Completed`, `Cancelled` a `NoShow` jsou terminální (`QueueEntryStatus::isTerminal()`).
Přechod `Called -> Waiting` v tomto milníku záměrně neexistuje.

Dovolené přechody jsou definované přímo na enumu
(`QueueEntryStatus::allowedTransitions()` / `canTransitionTo()`), takže je lze
testovat izolovaně bez databáze. Všechny čtyři akce, které mění stav
(`CallNextEntry`, `CompleteEntry`, `CancelEntry`, `MarkEntryNoShow`), proto
sdílí jediný vnitřní mechanismus —
`App\Actions\Queue\Concerns\TransitionsQueueEntryStatus::transition()` — který:

1. zamkne řádek `QueueEntry` (`lockForUpdate`),
2. ověří přechod podle enumu,
3. uloží nový stav,
4. vytvoří odpovídající `QueueEntryStatusChange`.

Díky tomu nemůže žádná akce změnit `QueueEntry::status`, aniž by zároveň
vznikl historický záznam.

## Bezpečnost tracking tokenu

`App\Support\Queue\TrackingToken` generuje 32 bajtů (256 bitů) z
kryptograficky bezpečného generátoru (`random_bytes`), zakóduje je jako 64
hex znaků a spočítá jejich SHA-256 hash. Do databáze
(`queue_entries.tracking_token_hash`) se ukládá **pouze hash**; syrový token
se vrátí volajícímu jen jednou, jako výsledek `JoinQueue`
(`JoinQueueResult::$trackingToken`), a dál se nikde nepersistuje. Pozdější
dohledání vlastního lístku půjde přes
`QueueEntry::where('tracking_token_hash', TrackingToken::hash($raw))`.

Token není odvozen z databázového ID, takže uhodnutí jednoho tokenu
neodhaluje nic o ostatních.

## Concurrency

Veškerá mutace sdíleného počítaného stavu (číslo lístku, aktivní session,
„další v pořadí“) probíhá v `DB::transaction()` s `lockForUpdate()` na
řádku, který danou invariantu chrání:

- **Číslování (`JoinQueue`)**: číslo lístku se bere z `queue_sessions.next_number`,
  ne z `SELECT MAX(number) + 1`. Řádek session se zamkne, takže dva
  souběžné vstupy do stejné session jsou serializované a nikdy nedostanou
  stejné číslo.
- **Volání dalšího (`CallNextEntry`)**: zamyká se stejný řádek session,
  takže dva operátoři klikající „Další" současně na stejné session jsou
  serializovaní — druhý vidí entry, kterou první právě přepnul na `Called`,
  a vybere další v pořadí.
- **Otevření session (`OpenQueueSession`)**: zamyká se řádek `VirtualQueue`,
  takže dva souběžné pokusy otevřít druhou aktivní session pro stejnou
  frontu jsou serializované a druhý pokus invariantu vyhodnotí správně.

### Omezení testování concurrency

Testovací sada běží nad SQLite (`:memory:`), která v jednom PHP procesu
nemá skutečné souběžné transakce — `lockForUpdate()` tam reálné zamykání
řádků na úrovni úložiště nezajišťuje stejně jako MySQL/PostgreSQL. Testy proto
ověřují **výsledek** zamykané logiky (sekvenční číslování, výběr nejnižšího
čekajícího čísla, nemožnost druhé aktivní session) v rámci jednoho procesu,
ne skutečnou paralelní soutěž dvou připojení. Produkční kód je napsaný tak,
aby zamykání fungovalo na MySQL i PostgreSQL (`SELECT ... FOR UPDATE`
v transakci); ověření na reálné souběžnosti vyžaduje integrační test se
dvěma skutečnými DB připojeními, který je nad rámec tohoto milníku.

## Proč se nepočítá a neukládá `current_position`

Pozice čekajícího lístku se dá vždy odvodit z existujících dat: počet
`QueueEntry` ve stavu `Waiting` ve stejné `QueueSession` s nižším `number`,
plus 1 (`QueueEntry::position()`). Uložený sloupec by se musel
přepočítávat při každé změně stavu kterékoli entry před ním ve frontě —
další zdroj nekonzistence navíc k údaji, který je stejně levný spočítat
za běhu. Index `(queue_session_id, status, number)` dělá tento dotaz rychlým.

## Proč QR kód není v databázi

QR kód je čistě vizuální derivace veřejné URL fronty, která vychází z
`VirtualQueue::public_id`. Jeho uložení by znamenalo redundanci (stejná
informace existuje dvakrát) a riziko, že se QR a URL rozejdou po změně
domény nebo formátu. QR se bude generovat on-the-fly z `public_id` v
pozdějším milníku.

## Proč žádné RBAC

Všechny operace v tomto milníku vlastní jeden `User` přes
`virtual_queues.user_id`. Autorizace je proto čistě vlastnická:
uživatel smí pracovat jen s `VirtualQueue`, kde `user_id === auth()->id()`,
a se vším, co na danou `VirtualQueue` navazuje (`QueueSession`,
`ServicePoint`, `QueueEntry` — vždy odvozené přes vztah, ne přes vlastní
sloupec). Role a oprávnění nejsou potřeba, dokud aplikace nepodporuje víc
operátorů na jedné frontě; zavedení rolí by teď bylo předčasné
zobecnění bez reálného požadavku.

## Normalizace (3NF)

Schéma je navržené v podstatě ve 3NF: každá tabulka má jediný účel,
neobsahuje odvozené/počítané sloupce (pozice, statistiky) a cizí klíče
odkazují jen na primární klíče. Výjimkou je `queue_entries.status`
a `queue_sessions.status`, které jsou denormalizovaný „aktuální stav“ nad
historií v `queue_entry_status_changes` — to je úmyslné: dotazy na aktuální
stav (např. „kdo čeká“) jsou časté a historická tabulka by pro ně vyžadovala
dražší korelovaný poddotaz. Historie samotná žádný podobný duplicitní údaj
neobsahuje.

## Hlavní invarianty a jejich vynucení

| # | Invariant | Kde je vynucen |
|---|---|---|
| 1 | `virtual_queues.public_id` je unikátní | DB `UNIQUE`, generováno v `VirtualQueue::booted()` |
| 2 | Nejvýš jedna aktivní (`Open`/`Paused`) session na `VirtualQueue` | `OpenQueueSession` + zámek na `VirtualQueue` |
| 3 | `queue_entries.number` je unikátní v rámci session | DB `UNIQUE(queue_session_id, number)` |
| 4 | `next_number` nikdy neklesá | Jen se inkrementuje v `JoinQueue`, nikdy nenastavuje zpět |
| 5 | Nová `QueueEntry` jen do `Open` session | `JoinQueue` kontroluje `status` pod zámkem |
| 6 | `ServicePoint` u entry musí patřit stejné `VirtualQueue` | `CallNextEntry::assertServicePointUsable()` |
| 7 | `ServicePoint` pro odbavení musí být `is_active` | `CallNextEntry::assertServicePointUsable()` |
| 8 | Operátor smí spravovat jen svoje `VirtualQueue` | `App\Policies\*` (vlastnictví přes `user_id`) |
| 9 | `Completed`/`Cancelled`/`NoShow` jsou terminální | `QueueEntryStatus::allowedTransitions()` |
| 10 | Každá změna statusu vytvoří `QueueEntryStatusChange` | `TransitionsQueueEntryStatus::transition()` |
| 11 | Dva souběžné joiny nedostanou stejné číslo | zámek na `QueueSession` v `JoinQueue` |
| 12 | Dva souběžné `CallNextEntry` nevyvolají stejnou entry | zámek na `QueueSession` v `CallNextEntry` |
| 13 | Syrový tracking token se neukládá | `TrackingToken` ukládá jen `hash`, `raw` jen v návratové hodnotě |
| 14 | Uzavřená session nelze znovu otevřít | `CloseQueueSession`/`ResumeQueueSession` hlídají `status` |
| 15 | `CloseQueueSession` nastaví `closed_at` | `CloseQueueSession::__invoke()` |

## Databázová přenositelnost

Migrace používají jen Laravel Schema Builder (`foreignId`, `char`, `string`,
`unsignedInteger`, `unsignedSmallInteger`, `boolean`, `timestamp`) a explicitní
`$table->unique()`/`$table->index()`/`$table->foreign()` volání — žádné
PostgreSQL-specifické partial unique indexy ani MySQL-specifické konstrukce.
Invariant „nejvýš jedna aktivní session“ je proto vynucen v aplikační vrstvě
(zámek + transakce), ne databázovým constraintem, aby fungoval stejně na
SQLite, MySQL i PostgreSQL. `sqlite.foreign_key_constraints` je v projektu
zapnuté (`config/database.php`), takže `ON DELETE CASCADE/RESTRICT/SET NULL`
platí i v testech nad SQLite.
