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

            <a class="nav-link nav-ext" href="https://buildwithabdallah.com" rel="noopener">buildwithabdallah.com</a>

            <button type="button" class="icon-btn nav-toggle" data-nav-toggle
                    aria-label="Toggle navigation" aria-expanded="false" aria-controls="mobile-menu">
                <span class="burger" aria-hidden="true"><i></i><i></i></span>
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
