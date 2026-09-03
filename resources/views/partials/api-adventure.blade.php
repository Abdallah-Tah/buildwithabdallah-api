@php
    $adv = config('api_adventure');
    $stages = $adv['stages'];
    $services = $adv['services'];
    $destination = $adv['destination'];
    $asset = static fn (string $path): string => asset('images/api-adventure/'.$path);

    // Where the blank sign board sits on each machine, measured from the
    // artwork by scripts/extract-api-adventure-assets.py. Regenerating the
    // sheets moves the boards; re-running the script moves the labels with
    // them, so the overlay never drifts off its sign.
    $manifest = json_decode(@file_get_contents(public_path('images/api-adventure/manifest.json')) ?: '{}', true);
    $sign = static function (string $art) use ($manifest): string {
        $box = $manifest['stations'][basename($art, '.webp')]['sign'] ?? ['top' => 0.2, 'width' => 0.55];

        return '--adv-sign-top: '.round($box['top'] * 100, 2).'%; --adv-sign-w: '.round($box['width'] * 100, 2).'%';
    };

    $summary = 'Request journey: '
        .collect($stages)->pluck('title')->implode(', ')
        .', fanned out to '.collect($services)->pluck('title')->implode(', ')
        .', then returned as a '.$destination['title'].'.';
@endphp

<div class="adv" data-adventure
     style="--adv-pipe-body: url('{{ $asset('pipes/body.webp') }}'); --adv-pipe-body-vertical: url('{{ $asset('pipes/body-vertical.webp') }}')"
     data-adv-duration="{{ $adv['duration'] }}"
     data-adv-replay="{{ $adv['replay_delay'] }}">

    <div class="adv-head">
        <span class="adv-lights" aria-hidden="true"><i></i><i></i><i></i></span>
        <span class="adv-name">API Distribution Adventure</span>
        <span class="adv-host">api.buildwithabdallah.com</span>
    </div>

    <div class="adv-stage">
        {{-- Background depth. Decorative and deliberately sparse: the
             architecture has to stay the thing you read first. --}}
        <div class="adv-scenery" aria-hidden="true">
            {{-- Midground: machinery that reads as a room the pipeline runs
                 through. Deliberately low contrast — the architecture has to
                 stay the thing you read first. --}}
            <img class="adv-prop adv-prop--rack-a" src="{{ $asset('environment/server-rack.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--rack-b" src="{{ $asset('environment/server-rack.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--rack-c" src="{{ $asset('environment/server-rack.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--deck-a" src="{{ $asset('environment/platform-wide.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--deck-b" src="{{ $asset('environment/platform.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--beacon-a" src="{{ $asset('environment/beacon.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--beacon-b" src="{{ $asset('environment/beacon.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--steam" src="{{ $asset('environment/smoke.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--pipe-l" src="{{ $asset('pipes/vertical.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--pipe-r" src="{{ $asset('pipes/vertical.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--rack-d" src="{{ $asset('environment/server-rack.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--beacon-c" src="{{ $asset('environment/beacon.webp') }}" alt="" loading="lazy" decoding="async">
            <img class="adv-prop adv-prop--beacon-d" src="{{ $asset('environment/beacon.webp') }}" alt="" loading="lazy" decoding="async">
        </div>

        {{-- Pipework. Real artwork, tiled rather than stretched: every long run
             is a repeating body between two fittings. --}}
        <div class="adv-pipes" aria-hidden="true">
            <span class="adv-run adv-run--main"></span>
            {{-- The entrance. The runner is not swapped out mid-air: it runs
                 into this bore and the request continues as a packet. --}}
            <span class="adv-mouth">
                <img class="adv-mouth-rim" src="{{ $asset('pipes/elbow-left-down.webp') }}" alt="">
                <span class="adv-mouth-bore"></span>
            </span>
            <span class="adv-run adv-run--descent"></span>
            <img class="adv-fit adv-fit--turn" src="{{ $asset('pipes/elbow-left-up.webp') }}" alt="">
            <span class="adv-run adv-run--fan"></span>
            @foreach ($services as $service)
                <img class="adv-fit adv-fit--tee" data-adv-tee="{{ $service['id'] }}" src="{{ $asset('pipes/tee-up.webp') }}" alt="">
                <span class="adv-run adv-run--branch" data-adv-branch="{{ $service['id'] }}"></span>
            @endforeach
            <img class="adv-fit adv-fit--return" src="{{ $asset('pipes/elbow-up-right.webp') }}" alt="">
            <span class="adv-run adv-run--tail"></span>
            <span class="adv-run adv-run--final"></span>
        </div>

        {{-- Decorative: it draws the request travelling, nothing more. Every
             stage, status and value below is HTML and updates without it. --}}
        <canvas class="adv-canvas" data-adv-canvas aria-hidden="true"></canvas>

        <ol class="adv-track" role="list" aria-label="{{ $summary }}">
            @foreach ($stages as $i => $stage)
                <li class="adv-node adv-node--{{ $stage['tone'] }}"
                    style="--adv-col: {{ $i + 1 }}"
                    data-adv-node="{{ $stage['id'] }}"
                    data-adv-row="main">
                    {{-- Capsule only: the upper stages are modules inserted
                         into one continuous pipe, so their platform artwork is
                         deliberately not used. --}}
                    <span class="adv-machine adv-machine--inline" data-adv-port="{{ $stage['id'] }}">
                        <img src="{{ $asset('stations/'.$stage['art']) }}" alt="" loading="lazy" decoding="async">
                    </span>
                    <span class="adv-chip">
                        @if ($loop->first)
                            <img class="adv-flag" src="{{ $asset('environment/flag.webp') }}" alt="" aria-hidden="true">
                        @endif
                        {{ $stage['title'] }}
                    </span>
                    <button type="button" class="adv-meta" aria-describedby="adv-tip-{{ $stage['id'] }}">
                        <span class="adv-meta-detail">{{ $stage['detail'] }}</span>
                        <span class="adv-meta-status" data-adv-status data-adv-states="{{ implode('|', $stage['states']) }}">
                            <i class="adv-status-dot" aria-hidden="true"></i><b>{{ $stage['states'][0] }}</b>
                        </span>
                    </button>
                    <span class="adv-tip" id="adv-tip-{{ $stage['id'] }}" role="tooltip">{{ $stage['tip'] }}</span>
                </li>
            @endforeach

            @foreach ($services as $i => $service)
                <li class="adv-node adv-node--ok"
                    style="--adv-col: {{ $i + 1 }}"
                    data-adv-node="{{ $service['id'] }}"
                    data-adv-row="service">
                    <span class="adv-machine adv-machine--service" style="{{ $sign($service['art']) }}" data-adv-port="{{ $service['id'] }}">
                        <img src="{{ $asset('stations/'.$service['art']) }}" alt="" loading="lazy" decoding="async">
                        <span class="adv-sign adv-sign--service adv-sign--{{ $service['id'] }}">
                            @if ($service['id'] === 'whatsapp')
                                <svg class="adv-service-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.84 9.84 0 0 0-8.49 14.79L2 22l5.35-1.5A9.96 9.96 0 0 0 12.04 22 9.92 9.92 0 0 0 22 12.08 9.92 9.92 0 0 0 12.04 2Zm0 18.32a8.22 8.22 0 0 1-4.19-1.15l-.3-.18-3.18.89.85-3.1-.2-.32a8.18 8.18 0 0 1-1.26-4.38 8.28 8.28 0 1 1 8.28 8.24Zm4.54-6.2c-.25-.13-1.47-.73-1.7-.81-.23-.09-.4-.13-.57.12-.16.25-.64.81-.79.98-.14.17-.29.19-.54.07-.25-.13-1.05-.39-2-1.23a7.5 7.5 0 0 1-1.38-1.72c-.15-.25-.02-.38.11-.5.11-.11.25-.29.37-.44.13-.14.17-.25.25-.41.08-.17.04-.32-.02-.44-.06-.13-.56-1.35-.77-1.85-.2-.49-.41-.42-.56-.43h-.48c-.17 0-.44.06-.67.31-.23.25-.87.85-.87 2.07 0 1.23.89 2.41 1.02 2.58.12.17 1.75 2.68 4.25 3.76.59.26 1.06.41 1.42.52.6.19 1.14.16 1.57.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.48-.29Z"/></svg>
                            @elseif ($service['id'] === 'ai')
                                <svg class="adv-service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3z"/><path d="M18.6 15.4l.6 1.6 1.6.6-1.6.6-.6 1.6-.6-1.6-1.6-.6 1.6-.6.6-1.6z"/></svg>
                            @elseif ($service['id'] === 'billing')
                                <svg class="adv-service-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/></svg>
                            @endif
                            <span>{{ $service['title'] }}</span>
                        </span>
                        @if ($service['id'] === 'ai')
                            <img class="adv-robot" data-adv-robot src="{{ $asset('robot/idle.webp') }}"
                                 data-adv-robot-wave="{{ $asset('robot/wave.webp') }}" alt="" loading="lazy" decoding="async">
                        @endif
                        <img class="adv-loot adv-loot--{{ $service['id'] }}" src="{{ $asset($service['loot']) }}" alt="" loading="lazy" decoding="async">
                    </span>
                    <button type="button" class="adv-meta" aria-describedby="adv-tip-{{ $service['id'] }}">
                        <span class="adv-meta-detail">{{ $service['detail'] }}</span>
                        <span class="adv-meta-status" data-adv-status data-adv-states="{{ implode('|', $service['states']) }}">
                            <i class="adv-status-dot" aria-hidden="true"></i><b>{{ $service['states'][0] }}</b>
                        </span>
                    </button>
                    <span class="adv-tip" id="adv-tip-{{ $service['id'] }}" role="tooltip">{{ $service['tip'] }}</span>
                </li>
            @endforeach

            <li class="adv-node adv-node--brand adv-node--final"
                data-adv-node="{{ $destination['id'] }}"
                data-adv-row="final">
                <span class="adv-machine adv-machine--final" data-adv-port="{{ $destination['id'] }}">
                    <img src="{{ $asset('stations/signed-event.webp') }}" alt="" loading="lazy" decoding="async">
                    <span class="adv-sign adv-sign--final">
                        <strong>{{ $destination['title'] }}</strong>
                        <span>{{ $destination['detail'] }}</span>
                    </span>
                </span>

                <span class="adv-portal" data-adv-portal aria-hidden="true">
                    <img src="{{ $asset('portal/target.webp') }}" alt="">
                </span>

                <button type="button" class="adv-meta adv-meta--final" aria-describedby="adv-tip-{{ $destination['id'] }}">
                    <span class="adv-meta-status" data-adv-status data-adv-states="{{ implode('|', $destination['states']) }}">
                        <i class="adv-status-dot" aria-hidden="true"></i><b>{{ $destination['states'][0] }}</b>
                    </span>
                </button>
                <span class="adv-tip" id="adv-tip-{{ $destination['id'] }}" role="tooltip">{{ $destination['tip'] }}</span>
            </li>
        </ol>

        {{-- Scoreboard overlay. aria-hidden because the numbers carry no
             architecture meaning — the statuses above are the real content. --}}
        <div class="adv-hud" aria-hidden="true">
            <span class="adv-hud-score"><i>Score</i><b data-adv-score>0</b></span>
            <span class="adv-hud-life">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 20s-7-4.4-7-9.3A4 4 0 0112 8a4 4 0 017 2.7C19 15.6 12 20 12 20z"/></svg>&times;&nbsp;3
            </span>
            <span class="adv-hud-clock">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="14" r="8"/><path d="M12 14v-5M9 2h6M12 2v4"/></svg>
                <b data-adv-timer>00:00</b>
            </span>
        </div>
    </div>

    <div class="adv-foot">
        <div class="adv-panel">
            <span class="adv-panel-title">Legend</span>
            <ul class="adv-legend">
                @foreach ($adv['legend'] as $item)
                    <li><i class="adv-dot adv-dot--{{ $item['tone'] }}" aria-hidden="true"></i>{{ $item['label'] }}</li>
                @endforeach
            </ul>
        </div>

        <div class="adv-panel">
            <span class="adv-panel-title">Request journey</span>
            <p class="adv-journey" data-adv-journey>Connected &rarr; Secured &rarr; Processing &rarr; Delivered</p>
            <div class="adv-controls">
                <button type="button" class="adv-btn" data-adv-toggle aria-pressed="false">
                    <span data-adv-toggle-text>Pause</span>
                </button>
                <button type="button" class="adv-btn" data-adv-replay>Replay</button>
            </div>
        </div>

        <div class="adv-panel">
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
    @php
        $advVersion = collect(glob(public_path('js/api-adventure/*.js')))
            ->map(fn (string $file): int => filemtime($file))
            ->max();
    @endphp

    <script type="module"
            src="{{ asset('js/api-adventure/index.js') }}?v={{ $advVersion }}"></script>
@endpush
