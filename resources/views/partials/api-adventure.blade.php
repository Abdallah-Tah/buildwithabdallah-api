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
        </div>

        {{-- Pipework. Real artwork, tiled rather than stretched: every long run
             is a repeating body between two fittings. --}}
        <div class="adv-pipes" aria-hidden="true">
            <span class="adv-run adv-run--main"></span>
            <img class="adv-fit adv-fit--drop" src="{{ $asset('pipes/elbow-left-down.webp') }}" alt="">
            <span class="adv-run adv-run--descent"></span>
            <img class="adv-fit adv-fit--turn" src="{{ $asset('pipes/elbow-up-right.webp') }}" alt="">
            <span class="adv-run adv-run--fan"></span>
            @foreach ($services as $service)
                <img class="adv-fit adv-fit--tee" data-adv-tee="{{ $service['id'] }}" src="{{ $asset('pipes/tee-up.webp') }}" alt="">
                <span class="adv-run adv-run--branch" data-adv-branch="{{ $service['id'] }}"></span>
            @endforeach
            <img class="adv-fit adv-fit--return" src="{{ $asset('pipes/elbow-left-up.webp') }}" alt="">
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
                    <span class="adv-chip">{{ $stage['title'] }}</span>
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
                        <span class="adv-sign adv-sign--service">{{ $service['title'] }}</span>
                        @if ($service['id'] === 'ai')
                            <img class="adv-robot" data-adv-robot src="{{ $asset('robot/idle.webp') }}"
                                 data-adv-robot-wave="{{ $asset('robot/wave.webp') }}" alt="" loading="lazy" decoding="async">
                        @endif
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
            <span class="adv-hud-clock" data-adv-timer>00:00</span>
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
