@extends('layouts.site', [
    'title' => 'Central API',
    'description' => 'A single signed boundary between Meta WhatsApp, Stripe and every Build With Abdallah product. Messaging orchestration, billing, webhook ingestion and event delivery in one auditable service.',
])

@section('content')

    {{-- ============================================================ Hero --}}
    <section class="hero wash grid-bg">
        <div class="shell hero-solo">
            <div class="reveal">
                <span class="eyebrow">Central API &middot; v1</span>

                <h1>One credential boundary for every product I ship.</h1>

                <p class="lede">
                    Kirada, Djib Payroll and SMKit never hold a Meta token or a Stripe key. They send
                    one signed request to this service, and it handles the providers, the retries, the
                    idempotency and the audit trail on their behalf.
                </p>

                <div class="hero-actions">
                    <a class="btn btn--primary" href="{{ route('page.docs') }}">
                        Read the API docs
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none" aria-hidden="true">
                            <path d="M1 7h11M8 3l4 4-4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <a class="btn" href="{{ route('page.about') }}">How it is built</a>
                </div>

                <div class="hero-meta">
                    <span class="pill pill--brand">Laravel 13</span>
                    <span class="pill pill--brand">PHP 8.5</span>
                    <span class="pill">HMAC-SHA256</span>
                    <span class="pill">Queued &amp; idempotent</span>
                    <span class="pill">Encrypted at rest</span>
                </div>
            </div>

        </div>
    </section>

    {{-- ========================================== API distribution adventure --}}
    {{-- The request journey, played as a level. The stages, statuses and the
         architecture note are plain HTML; the canvas on top is decorative and
         the section reads correctly with it removed or never started. --}}
    <section class="section--tight" id="distribution" aria-labelledby="distribution-title">
        <div class="shell">
            <div class="section-head reveal">
                <h2 id="distribution-title">Watch a request cross the boundary.</h2>
                <p class="lede">
                    One signed call leaves a product, clears the signature gate, is validated and
                    queued by the central API, fans out to the providers it needs, and comes back
                    as a signed event. Nothing below is a mock-up of the path — it is the path.
                </p>
            </div>

            @include('partials.api-adventure')
        </div>
    </section>

    {{-- =========================================================== Stats --}}
    <section class="section--tight">
        <div class="shell">
            <div class="stats reveal">
                <div class="stat">
                    <b><i data-count="3">0</i></b>
                    <span>Isolated queue lanes: webhooks, outbound sends and application events never block each other.</span>
                </div>
                <div class="stat">
                    <b><i data-count="5">0</i></b>
                    <span>Delivery attempts per job, backing off 1s &rarr; 5s &rarr; 30s &rarr; 120s.</span>
                </div>
                <div class="stat">
                    <b><i data-count="300" data-count-suffix="s">0</i></b>
                    <span>Signature freshness window before a request is rejected as stale.</span>
                </div>
                <div class="stat">
                    <b><i data-count="24" data-count-suffix="h">0</i></b>
                    <span>Customer-service window enforced before free-form text requires a template.</span>
                </div>
                <div class="stat">
                    <b><i data-count="0">0</i></b>
                    <span>Provider credentials stored in any product codebase. That is the entire point.</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ==================================================== Capabilities --}}
    <section class="section section--line" id="capabilities">
        <div class="shell">
            <div class="section-head reveal">
                <span class="eyebrow">What it handles</span>
                <h2>Infrastructure built for real products, not a demo.</h2>
                <p>
                    Provider complexity, credentials, retries and audit history stay here. Each product
                    keeps a single contract and clear ownership of the events it cares about.
                </p>
            </div>

            <div class="cards cards--3">
                <article class="card reveal" data-delay="1">
                    <span class="card-tag">01 / Auth</span>
                    <h3>Signed application access</h3>
                    <p>
                        Each product gets its own request and event secrets. Requests are timestamped,
                        signed over the raw body, and replay-protected by a persisted single-use nonce.
                    </p>
                    <ul>
                        <li>HMAC-SHA256 over a canonical string</li>
                        <li>hash_equals comparison</li>
                        <li>120 requests/min per application</li>
                    </ul>
                </article>

                <article class="card reveal" data-delay="2">
                    <span class="card-tag">02 / Messaging</span>
                    <h3>WhatsApp orchestration</h3>
                    <p>
                        A provider-neutral contract routes traffic through the Meta Cloud API, with a
                        second provider kept behind a hard-disabled flag so a stale column can never
                        silently reroute production traffic.
                    </p>
                    <ul>
                        <li>Templates and free-form text</li>
                        <li>Customer-service window enforced</li>
                        <li>Inbound product routing</li>
                    </ul>
                </article>

                <article class="card reveal" data-delay="3">
                    <span class="card-tag">03 / Billing</span>
                    <h3>Centralised Stripe billing</h3>
                    <p>
                        Checkout sessions, portal sessions, customer mapping and the full Stripe webhook
                        lifecycle are coordinated once, then fanned out to whichever product owns the
                        customer.
                    </p>
                    <ul>
                        <li>Redirect host allow-listing</li>
                        <li>Signed webhook verification</li>
                        <li>Per-product billing callbacks</li>
                    </ul>
                </article>

                <article class="card reveal" data-delay="4">
                    <span class="card-tag">04 / Events</span>
                    <h3>Reliable event delivery</h3>
                    <p>
                        Status changes become durable event rows with stable IDs before anything is sent.
                        Delivery is queued, signed, retried on transient failures, and recorded either way.
                    </p>
                    <ul>
                        <li>Stable ULID event IDs</li>
                        <li>Retry only on 5xx and 429</li>
                        <li>Attempt count and last error stored</li>
                    </ul>
                </article>

                <article class="card reveal" data-delay="5">
                    <span class="card-tag">05 / Privacy</span>
                    <h3>Protected data boundaries</h3>
                    <p>
                        Phone numbers, display names and message bodies are encrypted at rest. Lookups run
                        against deterministic SHA-256 hashes, so no plaintext identifier is ever indexed.
                    </p>
                    <ul>
                        <li>Authenticated encryption casts</li>
                        <li>Hash-based contact lookup</li>
                        <li>Logs carry IDs, never content</li>
                    </ul>
                </article>

                <article class="card reveal" data-delay="6">
                    <span class="card-tag">06 / Operations</span>
                    <h3>Observable by design</h3>
                    <p>
                        Queues, exceptions, slow requests and failed deliveries surface in one secured
                        console. It is deliberately read-only &mdash; every mutation is an audited console
                        command instead.
                    </p>
                    <ul>
                        <li>MFA-required operations panel</li>
                        <li>Laravel Pulse metrics</li>
                        <li>Readiness probe for dependencies</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- =============================================== Trust boundaries --}}
    <section class="section section--line" id="boundaries">
        <div class="shell split split--sticky">
            <div class="reveal">
                <span class="eyebrow">How it works</span>
                <h2>Three trust boundaries, one signing scheme.</h2>
                <p class="lede" style="margin-top:18px">
                    Traffic crosses a boundary in three directions. All three verify the exact raw body
                    with the same canonical string, so there is one rule to reason about instead of three.
                </p>
                <div class="code-block" style="margin-top:28px">
                    <div class="code-head">
                        <span class="code-tab" aria-selected="true">canonical string</span>
                    </div>
                    <pre class="code"><span class="tok-kw">METHOD</span>
<span class="tok-kw">PATH</span>
<span class="tok-kw">TIMESTAMP</span>
<span class="tok-kw">REQUEST_ID</span>
<span class="tok-fn">SHA256</span><span class="tok-pun">(</span><span class="tok-str">raw body</span><span class="tok-pun">)</span></pre>
                </div>
            </div>

            <div class="steps">
                <div class="step reveal" data-delay="1">
                    <span class="step-num">01</span>
                    <div>
                        <strong>Provider &rarr; API</strong>
                        <p>
                            Meta and Stripe webhooks are verified against their own signature schemes,
                            persisted as durable event rows, then queued. The HTTP 200 goes back before any
                            domain work runs, so a slow processor never causes a provider retry storm.
                        </p>
                        <div class="meta">
                            <span class="pill">X-Hub-Signature-256</span>
                            <span class="pill">Stripe-Signature</span>
                        </div>
                    </div>
                </div>

                <div class="step reveal" data-delay="2">
                    <span class="step-num">02</span>
                    <div>
                        <strong>Product &rarr; API</strong>
                        <p>
                            Every <span class="ic">/api/v1</span> route sits behind one middleware that checks
                            the signature, that the application is enabled, that the timestamp is fresh, that
                            the request ID has never been used, and that the caller is inside its rate limit.
                        </p>
                        <div class="meta">
                            <span class="pill">X-BWA-App</span>
                            <span class="pill">X-BWA-Timestamp</span>
                            <span class="pill">X-BWA-Request-ID</span>
                            <span class="pill">X-BWA-Signature</span>
                        </div>
                    </div>
                </div>

                <div class="step reveal" data-delay="3">
                    <span class="step-num">03</span>
                    <div>
                        <strong>API &rarr; product</strong>
                        <p>
                            Outbound events are signed with a separate event secret and posted to the
                            product's webhook. A 200 or 202 marks it delivered; a 5xx or 429 is thrown back
                            onto the queue; anything else is recorded as a permanent failure with its status.
                        </p>
                        <div class="meta">
                            <span class="pill">Separate event secret</span>
                            <span class="pill">Stable event_id</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================================================== Status --}}
    <section class="section--tight">
        <div class="shell">
            <div class="status-panel reveal" data-status-url="{{ route('health.ready') }}">
                <div>
                    <span class="pill" data-status-pill>
                        <i class="dot dot--live"></i>
                        <span data-status-label>Checking&hellip;</span>
                    </span>
                    <h3 style="margin-top:16px;font-size:20px">Live dependency readiness</h3>
                </div>
                <div>
                    <p style="color:var(--dim);font-size:14.5px">
                        Read live from <span class="ic">GET /health/ready</span>, which verifies the database,
                        cache, queue connection and the configured WhatsApp provider on every call.
                    </p>
                    <div class="status-checks" data-status-checks style="margin-top:16px"></div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================================================= CTA --}}
    <section class="section--tight" style="padding-bottom:104px">
        <div class="shell">
            <div class="cta reveal">
                <div>
                    <h2>Built as the foundation for a growing product portfolio.</h2>
                    <p>
                        Adding a product is a configuration exercise, not another provider integration
                        project. Read the integration contract, or see how the whole thing is put together.
                    </p>
                </div>
                <div class="cta-actions">
                    <a class="btn btn--primary" href="{{ route('page.docs') }}">API documentation</a>
                    <a class="btn" href="{{ route('page.about') }}">Architecture</a>
                </div>
            </div>
        </div>
    </section>

@endsection
