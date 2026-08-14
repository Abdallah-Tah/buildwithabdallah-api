@php
    $links = [
        ['route' => 'page.home',  'label' => 'Overview'],
        ['route' => 'page.about', 'label' => 'Architecture'],
        ['route' => 'page.docs',  'label' => 'API docs'],
    ];
@endphp
<header class="site-header">
    <div class="shell nav">
        <a class="brand" href="{{ route('page.home') }}">
            <img src="{{ asset('images/bwa-logo.jpeg') }}" alt="" width="42" height="42" decoding="async">
            <span>
                <span class="brand-name">Build With <span class="hl">Abdallah</span></span>
                <span class="brand-sub">Central API</span>
            </span>
        </a>

        <nav class="nav-links" aria-label="Primary">
            @foreach ($links as $link)
                <a class="nav-link" href="{{ route($link['route']) }}"
                   @if (request()->routeIs($link['route'])) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            <a class="nav-link" href="{{ route('health.ready') }}">Status</a>
        </nav>

        <div class="nav-actions">
            @include('partials.theme-toggle')

            <a class="btn btn--primary btn--sm nav-cta" href="https://buildwithabdallah.com" rel="noopener">
                buildwithabdallah.com
                <svg width="13" height="13" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                    <path d="M1 7h11M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>

            <button type="button" class="icon-btn nav-toggle" data-nav-toggle
                    aria-label="Toggle navigation" aria-expanded="false" aria-controls="mobile-menu">
                <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
                </svg>
            </button>
        </div>
    </div>

    <div class="mobile-menu" id="mobile-menu">
        <div class="shell">
            @foreach ($links as $link)
                <a href="{{ route($link['route']) }}"
                   @if (request()->routeIs($link['route'])) aria-current="page" @endif>{{ $link['label'] }}</a>
            @endforeach
            <a href="{{ route('health.ready') }}">Status</a>
            <a href="https://buildwithabdallah.com" rel="noopener">buildwithabdallah.com &rarr;</a>
        </div>
    </div>
</header>
