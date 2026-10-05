<?php

return [
    // Shared by HTML metadata, social previews, JSON-LD and the PWA manifest.
    // Canonical URLs always use APP_URL from config/app.php.
    'name' => env('APP_NAME', 'NoQ'),
    'description' => env('APP_DESCRIPTION', 'NoQ je systém pro správu virtuálních čekacích front. Připojte se přes odkaz nebo QR kód a sledujte svůj stav bez čekání na jednom místě.'),
    'language' => 'cs',
    'locale' => 'cs_CZ',
    'theme_color' => '#00b7ef',
    'background_color' => '#ffffff',
    'social_image' => 'assets/branding/social.png',

    // Only these named routes may be indexed and appear in the sitemap.
    // Add public information pages here; keep queues and account pages private.
    'public_routes' => ['home'],
    'indexable' => (bool) env('SEO_INDEXABLE', true),

    // Optional XML fields, keyed by the same route names as public_routes.
    // Update lastmod only when page content changes; never use the current request time.
    'sitemap' => [
        'home' => [
            'lastmod' => env('SITEMAP_HOME_LASTMOD', '2026-10-05'),
            'changefreq' => 'monthly',
            'priority' => 1.0,
        ],
    ],

    // Enabled in every environment; an explicit false value can disable registration.
    'pwa_enabled' => (bool) env('PWA_ENABLED', true),
];
