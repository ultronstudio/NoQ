@php
    $metadata = app(\App\Support\Metadata::class)->page(
        request(),
        $__env->yieldContent('title'),
        $__env->yieldContent('description'),
    );
@endphp

<title>{{ $metadata['title'] }}</title>
<meta name="description" content="{{ $metadata['description'] }}">
<meta name="robots" content="{{ $metadata['robots'] }}">
<meta name="application-name" content="{{ $metadata['name'] }}">
<meta name="theme-color" content="{{ config('metadata.theme_color') }}">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="icon" href="/assets/branding/favicon-32.png" type="image/png" sizes="32x32">
<link rel="apple-touch-icon" href="/assets/branding/apple-touch-icon.png" sizes="180x180">

@if ($metadata['public'])
    <link rel="canonical" href="{{ $metadata['canonical'] }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $metadata['name'] }}">
    <meta property="og:locale" content="{{ config('metadata.locale') }}">
    <meta property="og:title" content="{{ $metadata['title'] }}">
    <meta property="og:description" content="{{ $metadata['description'] }}">
    <meta property="og:url" content="{{ $metadata['canonical'] }}">
    <meta property="og:image" content="{{ $metadata['image'] }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="{{ $metadata['name'] }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metadata['title'] }}">
    <meta name="twitter:description" content="{{ $metadata['description'] }}">
    <meta name="twitter:image" content="{{ $metadata['image'] }}">
    <meta name="twitter:image:alt" content="{{ $metadata['name'] }}">
    <script type="application/ld+json">{!! $metadata['jsonLd'] !!}</script>
@endif

@if (config('metadata.pwa_enabled'))
    <link rel="manifest" href="/manifest.webmanifest">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="{{ $metadata['name'] }}">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <script src="/pwa-register.js" defer></script>
@endif
