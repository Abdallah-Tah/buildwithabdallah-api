{{--
    Public site shell.

    Styles and scripts are served straight from public/ rather than through
    Vite: this app has no node build in its deploy path, and the public pages
    must never depend on one. Asset URLs carry a filemtime cache-buster.
--}}
@php
    $asset = static fn (string $path): string => asset($path).'?v='.(file_exists(public_path($path)) ? filemtime(public_path($path)) : 1);
    $pageTitle = trim($title ?? '');
    $pageDescription = $description ?? 'The Build With Abdallah Central API is the single credential boundary between WhatsApp, Stripe and every Build With Abdallah product.';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle !== '' ? $pageTitle.' — Build With Abdallah Central API' : 'Build With Abdallah — Central API' }}</title>
    <meta name="description" content="{{ $pageDescription }}">
    <meta name="theme-color" content="#09090b">
    <link rel="canonical" href="{{ url()->current() }}">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Build With Abdallah Central API">
    <meta property="og:title" content="{{ $pageTitle !== '' ? $pageTitle : 'Build With Abdallah — Central API' }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/bwa-banner.jpeg') }}">
    <meta name="twitter:card" content="summary_large_image">

    <link rel="icon" href="{{ asset('images/bwa-mark.svg') }}" type="image/svg+xml">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/bwa-logo.jpeg') }}">

    {{-- Resolve the theme before first paint so the page never flashes. Mirrors
         the contract on buildwithabdallah.com: same key, same tri-state. --}}
    <script>
        (() => {
            const stored = (() => { try { return localStorage.getItem('bwa.theme'); } catch { return null; } })();
            const choice = ['auto', 'light', 'dark'].includes(stored) ? stored : 'auto';
            const dark = choice === 'dark' || (choice === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            const el = document.documentElement;
            el.classList.toggle('dark', dark);
            el.dataset.theme = choice;
            el.dataset.resolvedTheme = dark ? 'dark' : 'light';
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600&amp;family=Manrope:wght@400;500;600;700&amp;family=Space+Grotesk:wght@400;500;600;700&amp;display=swap" rel="stylesheet">

    <link rel="stylesheet" href="{{ $asset('css/site.css') }}">
</head>
<body>
    <a class="skip-link" href="#main">Skip to content</a>

    @include('partials.site-nav')

    <main id="main">
        @yield('content')
    </main>

    @include('partials.site-footer')

    <script src="{{ $asset('js/site.js') }}" defer></script>
</body>
</html>
