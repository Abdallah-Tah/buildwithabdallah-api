@php
    $adv = config('api_adventure');
    $stages = $adv['stages'];
    $services = $adv['services'];
    $destination = $adv['destination'];

    // The journey read as one sentence, used as the accessible description of
    // the whole diagram so a screen reader gets the architecture, not the game.
    $summary = 'Request journey: '
        .collect($stages)->pluck('title')->implode(', ')
        .', fanned out to '.collect($services)->pluck('title')->implode(', ')
        .', then returned as a '.$destination['title'].'.';
@endphp

<div class="adv" data-adventure
     data-adv-duration="{{ $adv['duration'] }}"
     data-adv-replay="{{ $adv['replay_delay'] }}">

    <div class="adv-head">
        <span class="adv-lights" aria-hidden="true"><i></i><i></i><i></i></span>
        <span class="adv-name">API Distribution Adventure</span>
        <span class="adv-host">api.buildwithabdallah.com</span>
    </div>

    {{-- Decorative scoreboard. It is aria-hidden because the numbers carry no
         architecture meaning — the stage statuses below are the real content. --}}
    <div class="adv-hud" aria-hidden="true">
        <div class="adv-hud-cell adv-hud-cell--score">
            <span>Score</span>
            <strong data-adv-score>0</strong>
        </div>
        <div class="adv-hud-cell adv-hud-cell--life">
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 20s-7-4.4-7-9.3A4 4 0 0112 8a4 4 0 017 2.7C19 15.6 12 20 12 20z"/></svg>
            <span>&times; 3</span>
        </div>
        <div class="adv-hud-cell adv-hud-cell--clock">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="8"/><path d="M12 8v4l2.5 2" stroke-linecap="round"/></svg>
            <span data-adv-timer>00:00</span>
        </div>
    </div>

    <div class="adv-stage">
        {{-- The animated layer. Purely decorative: every stage, status and
             transition below exists in HTML and updates without it. --}}
        <canvas class="adv-canvas" data-adv-canvas aria-hidden="true"></canvas>

        <ol class="adv-track" role="list" aria-label="{{ $summary }}">
            @foreach ($stages as $i => $stage)
                <li class="adv-node adv-node--{{ $stage['tone'] }}"
                    style="--adv-col: {{ $i + 1 }}"
                    data-adv-node="{{ $stage['id'] }}"
                    data-adv-row="main">
                    <span class="adv-node-label">{{ $stage['title'] }}</span>

                    <span class="adv-pipe" data-adv-port="{{ $stage['id'] }}">
                        <span class="adv-pipe-cap" aria-hidden="true"></span>
                        <span class="adv-pipe-core">
                            @include('partials.adventure-icon', ['name' => $stage['icon']])
                        </span>
                        <span class="adv-pipe-cap" aria-hidden="true"></span>
                    </span>

                    <button type="button" class="adv-card" aria-describedby="adv-tip-{{ $stage['id'] }}">
                        <span class="adv-card-detail">{{ $stage['detail'] }}</span>
                        <span class="adv-card-status" data-adv-status data-adv-states="{{ implode('|', $stage['states']) }}">
                            Status: <b>{{ $stage['states'][0] }}</b>
                        </span>
                    </button>
                    <span class="adv-tip" id="adv-tip-{{ $stage['id'] }}" role="tooltip">{{ $stage['tip'] }}</span>
                </li>
            @endforeach

            @foreach ($services as $i => $service)
                <li class="adv-node adv-node--ok adv-node--service"
                    style="--adv-col: {{ $i + 1 }}"
                    data-adv-node="{{ $service['id'] }}"
                    data-adv-row="service">
                    <span class="adv-node-label">{{ $service['title'] }}</span>

                    <span class="adv-pipe adv-pipe--sm" data-adv-port="{{ $service['id'] }}">
                        <span class="adv-pipe-cap" aria-hidden="true"></span>
                        <span class="adv-pipe-core">
                            @include('partials.adventure-icon', ['name' => $service['icon']])
                        </span>
                        <span class="adv-pipe-cap" aria-hidden="true"></span>
                    </span>

                    <button type="button" class="adv-card" aria-describedby="adv-tip-{{ $service['id'] }}">
                        <span class="adv-card-detail">{{ $service['detail'] }}</span>
                        <span class="adv-card-status" data-adv-status data-adv-states="{{ implode('|', $service['states']) }}">
                            Status: <b>{{ $service['states'][0] }}</b>
                        </span>
                    </button>
                    <span class="adv-tip" id="adv-tip-{{ $service['id'] }}" role="tooltip">{{ $service['tip'] }}</span>
                </li>
            @endforeach

            <li class="adv-node adv-node--brand adv-node--final"
                data-adv-node="{{ $destination['id'] }}"
                data-adv-row="final">
                <span class="adv-final-rail" data-adv-port="{{ $destination['id'] }}" aria-hidden="true"></span>

                <button type="button" class="adv-card adv-card--final" aria-describedby="adv-tip-{{ $destination['id'] }}">
                    <span class="adv-final-icon" aria-hidden="true">
                        @include('partials.adventure-icon', ['name' => $destination['icon']])
                    </span>
                    <span class="adv-final-text">
                        <strong>{{ $destination['title'] }}</strong>
                        <span class="adv-card-detail">{{ $destination['detail'] }}</span>
                    </span>
                    <span class="adv-card-status" data-adv-status data-adv-states="{{ implode('|', $destination['states']) }}">
                        Status: <b>{{ $destination['states'][0] }}</b>
                    </span>
                </button>
                <span class="adv-tip" id="adv-tip-{{ $destination['id'] }}" role="tooltip">{{ $destination['tip'] }}</span>

                <span class="adv-portal" data-adv-portal aria-hidden="true"><i></i><i></i><i></i></span>
            </li>
        </ol>
    </div>

    <div class="adv-foot">
        <div class="adv-panel adv-panel--legend">
            <span class="adv-panel-title">Legend</span>
            <ul class="adv-legend">
                @foreach ($adv['legend'] as $item)
                    <li><i class="adv-dot adv-dot--{{ $item['tone'] }}" aria-hidden="true"></i>{{ $item['label'] }}</li>
                @endforeach
            </ul>
        </div>

        <div class="adv-panel adv-panel--journey">
            <span class="adv-panel-title">Request journey</span>
            <p class="adv-journey" data-adv-journey>Connected &rarr; Secured &rarr; Processing &rarr; Delivered</p>
            <div class="adv-controls">
                <button type="button" class="adv-btn" data-adv-toggle aria-pressed="false">
                    <span class="adv-btn-play" aria-hidden="true">&#9654;</span>
                    <span class="adv-btn-pause" aria-hidden="true">&#10073;&#10073;</span>
                    <span data-adv-toggle-text>Pause</span>
                </button>
                <button type="button" class="adv-btn" data-adv-replay>Replay</button>
            </div>
        </div>

        <div class="adv-panel adv-panel--progress">
            <span class="adv-panel-title">Level progress</span>
            <div class="adv-bar" role="progressbar" aria-label="Request journey progress"
                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-adv-bar>
                <i data-adv-bar-fill></i>
            </div>
            <span class="adv-progress-value" data-adv-progress>0%</span>
        </div>
    </div>

    <p class="adv-note">
        <span aria-hidden="true">&#9733;</span>
        {{ $adv['note'] }}
        <i class="adv-caret" aria-hidden="true"></i>
    </p>
</div>

@push('scripts')
    {{-- Loaded as a module so the scene can be code-split behind a dynamic
         import: Three.js is only fetched once this section scrolls into view
         and only when the visitor has not asked for reduced motion. --}}
    <script type="module"
            src="{{ asset('js/api-adventure/index.js') }}?v={{ filemtime(public_path('js/api-adventure/index.js')) }}"></script>
@endpush
