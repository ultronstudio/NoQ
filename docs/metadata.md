# Metadata, SEO a PWA

## Kde co upravit

Základní údaje pocházejí z `.env`:

```dotenv
APP_NAME=NoQ
APP_URL=https://vase-domena.cz
APP_DESCRIPTION="Stručný a pravdivý popis aplikace."
APP_ENV=production
APP_DEBUG=false
```

Doménu doplňte podle nasazení. `APP_URL` musí obsahovat správné schéma (v produkci
HTTPS) a veřejnou doménu. Po změně `.env` v produkci obnovte konfiguraci příkazem
`php artisan config:cache`. Lokálně případně použijte `php artisan config:clear`.

Ostatní společné nastavení je v **`config/metadata.php`**: jazyk, barvy, obrázek pro
sdílení, seznam veřejných rout a zapnutí indexace/PWA. Tento základ předpokládá
nasazení v kořeni vlastní domény, nikoli v podadresáři.

Veřejné stránky jsou ve výchozím stavu indexovatelné nezávisle na `APP_ENV`.
Hlavní stránka je proto i lokálně v sitemap a robots obsahuje odkaz na sitemap.
Pomocí `SEO_INDEXABLE=false` lze indexaci celého webu vypnout, například na stagingu;
pak robots zakáže procházení a sitemap bude prázdná.
PWA je automaticky zapnuté ve všech prostředích nezávisle na `APP_ENV`.
Výslovně ho lze vypnout přes `PWA_ENABLED=false`; localhost má výjimku z požadavku
na HTTPS.
Vypnutí registrace neodinstaluje už nainstalovanou PWA ani stávající service worker;
při vývoji je lze odstranit v nástrojích prohlížeče v části Application.

## Jednotlivé stránky

V libovolné Blade stránce používající stávající layouty stačí:

```blade
@section('title', 'Jak fungují virtuální fronty')
@section('description', 'Konkrétní popis obsahu této stránky.')
```

Název aplikace se k titulku připojí automaticky. Bez vlastního popisu se použije
`APP_DESCRIPTION`. Sdílená šablona `resources/views/partials/metadata.blade.php`
vytváří title, description, robots, ikony, canonical, Open Graph, Twitter Card a
JSON-LD. Nepřidávejte další kopie těchto tagů do layoutů.

Novou veřejnou informační stránku pojmenujte v routách a přidejte její název do
`public_routes` v konfiguraci. Tím získá veřejná metadata a zápis do `/sitemap.xml`.
Tento seznam je určen statickým routám bez povinných parametrů; dynamický katalog
by potřeboval rozšířit sitemap o konkrétní veřejné záznamy.

Přihlášení, administrace a všechny routy mimo seznam dostávají `noindex, nofollow`
v HTML i HTTP hlavičce. Robots není ochrana dat: neveřejný obsah musí dál vyžadovat
autentizaci. Při zapnuté indexaci `/robots.txt` dovoluje procházení, aby vyhledávače
mohly přečíst `noindex`; odkazuje také na sitemap. Nastavení `SEO_INDEXABLE=false`
procházení zakazuje ve všech prostředích.

Canonical, OG URL, sitemap i identifikátory JSON-LD vycházejí z `APP_URL`, nikoli
z hostitele požadavku. Canonical vynechává query parametry včetně UTM.
Pokud přibudou stránky, kde query určuje samostatný indexovatelný obsah (například
stránkování katalogu), upravte tuto politiku v `App\Support\Metadata`.

## Volitelné údaje v sitemap

Pro jednotlivé veřejné routy nastavte v `config/metadata.php` sekci `sitemap`:

```php
'sitemap' => [
    'home' => [
        'lastmod' => env('SITEMAP_HOME_LASTMOD', '2026-10-05'),
        'changefreq' => 'monthly',
        'priority' => 1.0,
    ],
],
```

- `lastmod`: datum poslední významné změny obsahu ve formátu `YYYY-MM-DD`.
  U homepage jde nyní o 5. října 2026, kdy se obsah stránky upravil. Při další
  změně obsahu, odkazů nebo strukturovaných dat datum aktualizujte v konfiguraci
  nebo v `.env` přes `SITEMAP_HOME_LASTMOD`. Samotné načtení sitemap nebo nasazení
  stejného obsahu ho nemění. Pokud datum neznáte, použijte `null` a tag se vynechá.
- `changefreq`: odhad četnosti změn. Povolené hodnoty: `always`, `hourly`, `daily`,
  `weekly`, `monthly`, `yearly`, `never`. Není to příkaz, jak často má robot přijít.
- `priority`: relativní důležitost stránky v rámci tohoto webu, od `0.0` do `1.0`.
  Není to hodnocení proti ostatním webům ani záruka pořadí ve vyhledávání.

Všechny tři hodnoty jsou volitelné. Chybějící, prázdné nebo `null` hodnoty se
nevypisují; `priority=0` se zachová. Formát data, četnost a rozsah priority se
validují. Záznam v `sitemap` sám o sobě stránku nezveřejní: stále musí být mezi
`public_routes`, takže administrace ani přihlášení do sitemap nepřibudou.

Google používá `lastmod`, pokud odpovídá skutečné významné změně stránky;
`changefreq` a `priority` ignoruje. Pro budoucí obsah uložený v databázi bude
vhodné odvozovat datum od změny obsahu konkrétního záznamu.
Zdroj: https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap

## AIO / GEO a JSON-LD

Veřejný obsah se renderuje na serveru, má nadpisy a odpovídá metadatům.
Úvodní stránka obsahuje propojená data `WebSite`, `WebPage` a `WebApplication`;
další veřejné stránky `WebPage`. Nejsou doplněny neověřené ceny, hodnocení ani
identita provozovatele. JSON-LD je bezpečně escapované.

Základem pro AI vyhledávání je dohledatelný, srozumitelný a pravdivý obsah.
Google pro AI Overviews a AI Mode nevyžaduje speciální AI metadata, `llms.txt`
ani zvláštní schema. Implementace sama nezaručuje indexaci nebo citace v AI.
Podrobnosti: https://developers.google.com/search/docs/appearance/ai-features

Při rozšiřování webu přidávejte skutečné návody, odpovědi na dotazy a kontaktní údaje
provozovatele do viditelného obsahu; strukturovaná data musí odpovídat tomu,
co návštěvník vidí. GEO zde znamená optimalizaci pro generativní vyhledávání,
nikoli geografické meta tagy.

## PWA a grafika

`/manifest.webmanifest` přebírá název, popis a barvy ze stejné konfigurace.
Obsahuje ikony 192/512 px a samostatnou maskable ikonu. Instalace se provádí
přes nabídku podporovaného prohlížeče („Nainstalovat aplikaci“ / „Přidat na plochu“).
Mimo localhost je nutné HTTPS; nabídka a podmínky instalace se liší podle prohlížeče.

`public/sw.js` cachuje jen offline stránku a instalační ikony. Navigace jde vždy
na server; při nedostupnosti sítě se zobrazí `public/offline.html`. Přihlášené
stránky, požadavky API/Livewire a stav front se neukládají. Offline nelze vstupovat
do front ani měnit jejich stav. Registrace běží nezávisle na realtime klientovi.

Ikony, favicon a náhled pro sdílení (1200 × 630) jsou odvozené ze souborů
`public/assets/logo/noq-symbol-light.svg` a `noq-logo-light.svg`. Po změně loga:

```sh
npm run build:icons
```

Generátor je v `scripts/generate-brand-assets.mjs`, výstupy v
`public/assets/branding`. Generované obrázky commitujte. Po změně cachovaných
ikon nebo offline stránky zvyšte verzi `CACHE_NAME` v `public/sw.js`.

## Offline test v Chrome na localhostu

1. Otevřete `http://localhost:8000` při zapnutém serveru a připojení.
   Po celou dobu používejte stejný host a port; `localhost` a `127.0.0.1`
   mají samostatné registrace a cache.
2. Otevřete DevTools (`F12`) → **Application → Service workers**. Počkejte,
   až bude `/sw.js` aktivovaný. V **Cache storage → noq-pwa-v1** ověřte,
   že je uložený `/offline.html`. Pokud registrace chybí, zkuste běžný reload
   stránky a zkontrolujte chyby v konzoli; `php artisan config:clear` odstraní
   případnou starou Laravel konfiguraci.
3. Nechte **Bypass for network** a **Update on reload** vypnuté. V panelu
   Service workers zaškrtněte **Offline**.
4. Obnovte stránku běžným `Ctrl+R` (ne `Ctrl+Shift+R`, které obchází service worker).
   Musí se zobrazit **Jste offline**. Samotné přepnutí Offline už otevřenou stránku
   automaticky nenahradí; fallback se použije při další navigaci nebo obnovení.
5. Zrušte **Offline** a klikněte na **Zkusit znovu**. Načte se aktuální stránka
   ze serveru. Pro tento test není nutná instalace PWA na plochu.

Při tomto testu se simulují nedostupné síťové požadavky i k localhostu. Pouhé
odpojení Wi-Fi nestačí, protože místní server může zůstat dostupný.
Offline stránka je samostatná a nepotřebuje běžící Vite; práce s frontami
a administrací nadále vyžaduje serverové připojení.

Podklady: https://developer.chrome.com/docs/devtools/progressive-web-apps

## Ověření a nasazení

```sh
php artisan test
npm run test:pwa
npm run build
```

Po nasazení ověřte veřejné HTTPS URL, `/robots.txt`, `/sitemap.xml` a manifest.
Přesměrování HTTP → HTTPS a alternativních domén na hlavní doménu nastavte na
hostingu. Sitemap odešlete do Google Search Console a Bing Webmaster Tools;
v Search Console ověřte indexaci veřejných stránek. JSON-LD lze ověřit přes
https://validator.schema.org/ a PWA přes panel Application v nástrojích prohlížeče.

Technické podklady:
- https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls
- https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps/Guides/Making_PWAs_installable
