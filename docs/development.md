# Lokální vývoj

Z kořene projektu spusťte jeden příkaz:

```sh
composer dev
```

Stejně funguje `npm run dev`. Oba příkazy spouštějí společnou sestavu:

- PHP aplikaci na `http://localhost:8000`;
- Laravel Reverb na portu z `REVERB_SERVER_PORT` (v `.env.example` je `6001`);
- zpracování úloh přes `queue:listen`, které při vývoji načítá změny PHP kódu;
- Vite pro frontend a automatické obnovení (výchozí port `5173`).

Před startem se vyčistí cache Laravel konfigurace, aby se použily aktuální hodnoty
z `.env`. Logy jsou společně v terminálu s názvy `server`, `reverb`, `queue`, `vite`.
`Ctrl+C` ukončí celou sestavu. Pokud některá služba skončí nebo nenastartuje,
ukončí se i ostatní; aplikace tak nezůstane běžet bez potřebné služby.
PHP a Vite při obsazeném portu nepřecházejí potichu na jiný port.

Nejprve ukončete případné staré samostatné procesy `php artisan serve` nebo Vite,
jinak jejich porty budou obsazené. `php artisan serve` samotné nadále spouští jen
PHP server; pro celý projekt používejte `composer dev` nebo `npm run dev`.
Samostatný frontend lze spustit přes `npm run dev:vite`.

## Předpoklady

Potřebujete nainstalované PHP/Composer a Node.js/npm, závislosti z `composer install`
a `npm ci`, připravený `.env`, `APP_KEY` a databázové migrace. Databáze nastavená
v `.env` musí být dostupná; pokud používáte MySQL/MariaDB z XAMPPu, zapněte ji
v XAMPPu. Databázi tento příkaz nespravuje ani nevytváří.

## Reverb

`.env.example` obsahuje lokální příklad konfigurace. Pro HTTP localhost:

```dotenv
REVERB_HOST=localhost
REVERB_PORT=6001
REVERB_SCHEME=http
REVERB_SERVER_HOST=127.0.0.1
REVERB_SERVER_PORT=6001
```

`REVERB_APP_ID`, `REVERB_APP_KEY` a `REVERB_APP_SECRET` musí být vyplněné.
Frontend používá odpovídající `VITE_REVERB_*` proměnné. Po změně `.env` restartujte
celý `composer dev`, aby nové nastavení převzal Laravel, Reverb i Vite.
V produkci se veřejná adresa Reverb serveru nastavuje podle HTTPS/WSS proxy.

Port 8080 může být ve Windows rezervovaný systémem (například po instalaci
virtualizačních nástrojů). Projekt proto pro místní vývoj používá port 6001.
Při změně lokálního portu nastavte shodně `REVERB_PORT` i `REVERB_SERVER_PORT`.

Při přechodu prohlížeče offline se Echo odpojí a při návratu online se znovu připojí.
Pokud server neběží nebo je síť nedostupná, prohlížeč může stále zobrazit chybu
právě probíhajícího WebSocket připojení. PWA a offline stránka fungují samostatně.

Podklady: https://laravel.com/docs/13.x/reverb
