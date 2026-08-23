@extends('layouts.site', [
    'title' => 'Architecture',
    'description' => 'Why the Build With Abdallah Central API exists, how the request lifecycle works, the security model behind it, and the engineering decisions and tradeoffs made along the way.',
])

@section('content')

    {{-- ============================================================ Hero --}}
    <section class="hero wash grid-bg" style="padding-bottom:72px">
        <div class="shell">
            <div class="reveal" style="max-width:820px">
                <span class="eyebrow">Architecture notes</span>
                <h1>Why this service exists, and how it is built.</h1>
                <p class="lede" style="max-width:680px">
                    This page is about the system: the problem it solves, the decisions behind it, and the
                    tradeoffs each one carried. For who I am and the rest of my work, see
                    <a class="ul-link" href="https://buildwithabdallah.com" rel="noopener">buildwithabdallah.com</a>.
                </p>
                <div class="hero-meta">
                    <span class="pill pill--brand">Distributed integration</span>
                    <span class="pill pill--brand">Driven by events</span>
                    <span class="pill">Queue isolation</span>
                    <span class="pill">Cryptographic request auth</span>
                    <span class="pill">Encryption at rest</span>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================================================= Why built --}}
    <section class="section section--line" id="why">
        <div class="shell split">
            <div class="reveal">
                <span class="eyebrow">The problem</span>
                <h2>Every product was about to integrate the same providers again.</h2>
            </div>
            <div class="reveal" data-delay="1">
                <p class="lede">
                    I run several products on one server &mdash; Kirada, Djib Payroll, SMKit and client
                    work. Each of them needed WhatsApp messaging, and most of them needed subscription
                    billing.
                </p>
                <p class="lede" style="margin-top:20px">
                    The obvious path was to integrate Meta and Stripe into each product. That path costs
                    the same work <em>n</em> times, scatters production credentials across <em>n</em>
                    codebases, gives every product its own half-built retry logic, and leaves no shared
                    place to answer &ldquo;what happened to this message?&rdquo;. Rotating a leaked token
                    would have meant touching every deployment.
                </p>
                <p class="lede" style="margin-top:20px">
                    So the integration moved up one level. This service owns the provider relationships;
                    products own their own domain. One contract to learn, one place to rotate secrets, one
                    audit trail.
                </p>

                <div class="cards" style="margin-top:32px">
                    <article class="card">
                        <span class="card-tag">Before</span>
                        <h3>n products &times; m providers</h3>
                        <p>Duplicated integrations, credentials in every repo, inconsistent retry behaviour, no shared history.</p>
                    </article>
                    <article class="card">
                        <span class="card-tag">After</span>
                        <h3>n products &times; 1 contract</h3>
                        <p>One signed API, credentials in a single runtime secret store, uniform delivery guarantees, one audit trail.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================================================= Lifecycle --}}
    <section class="section section--line" id="lifecycle">
        <div class="shell">
            <div class="section-head reveal">
                <span class="eyebrow">Request lifecycle</span>
                <h2>From a product call to a delivered event.</h2>
                <p>
                    Controllers only handle transport concerns. Domain work happens in dedicated classes,
                    and anything that talks to a third party runs on a queue.
                </p>
            </div>

            <div class="steps">
                <div class="step reveal" data-delay="1">
                    <span class="step-num">01</span>
                    <div>
                        <strong>Authenticate the caller</strong>
                        <p>
                            <span class="ic">AuthenticateConnectedApplication</span> resolves the application
                            by slug, rejects it if disabled, checks the timestamp is within 300 seconds,
                            rejects a reused request ID, verifies the HMAC against the raw body, and applies
                            a rate limit per application. The nonce is written inside a guarded insert, so
                            two concurrent identical requests cannot both pass.
                        </p>
                    </div>
                </div>

                <div class="step reveal" data-delay="2">
                    <span class="step-num">02</span>
                    <div>
                        <strong>Apply domain rules</strong>
                        <p>
                            <span class="ic">CreateOutboundWhatsAppMessage</span> checks the product belongs to
                            the calling application, resolves the idempotency key, and enforces the 24-hour
                            service window &mdash; open text outside it is refused, templates are
                            allowed. Only then is a message row created.
                        </p>
                    </div>
                </div>

                <div class="step reveal" data-delay="3">
                    <span class="step-num">03</span>
                    <div>
                        <strong>Queue the external call</strong>
                        <p>
                            <span class="ic">SendWhatsAppMessage</span> runs on its own queue. It returns
                            immediately if the message already has a provider ID, so a retry can never
                            cause a duplicate send. If live sending is switched off, it records a
                            <span class="ic">LIVE_SEND_DISABLED</span> failure rather than reaching the network.
                        </p>
                    </div>
                </div>

                <div class="step reveal" data-delay="4">
                    <span class="step-num">04</span>
                    <div>
                        <strong>Normalise what comes back</strong>
                        <p>
                            Provider webhooks arrive on their own route, are signature-checked, stored raw,
                            and processed asynchronously into contacts, conversations, messages and statuses.
                            Unknown message and status types are tolerated rather than throwing, so a provider
                            adding a field does not take the pipeline down.
                        </p>
                    </div>
                </div>

                <div class="step reveal" data-delay="5">
                    <span class="step-num">05</span>
                    <div>
                        <strong>Fan out to the owning product</strong>
                        <p>
                            <span class="ic">WhatsAppMessageObserver</span> turns a status change into an
                            <span class="ic">ApplicationEventDelivery</span> row with a stable ULID, which
                            <span class="ic">DispatchApplicationEvent</span> signs and posts to the product's
                            webhook. The event is persisted before it is sent, so delivery can always be
                            retried or replayed.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================================================== Security --}}
    <section class="section section--line" id="security">
        <div class="shell split split--sticky">
            <div class="reveal">
                <span class="eyebrow">Security model</span>
                <h2>Assume the network is hostile and the caller is wrong.</h2>
                <p class="lede" style="margin-top:18px">
                    The same canonical string is used in all three directions, so there is a single rule to
                    audit rather than one scheme per integration.
                </p>
            </div>

            <div class="reveal" data-delay="1">
                <div class="cards">
                    <article class="card">
                        <span class="card-tag">Request authenticity</span>
                        <h3>Signed over the exact raw body</h3>
                        <p>
                            The signature binds method, path, timestamp, request ID and a SHA-256 of the raw
                            body, compared with <span class="ic">hash_equals</span>. Signing a re-encoded body
                            would let a mismatch slip through, so the raw payload is always used.
                        </p>
                    </article>
                    <article class="card">
                        <span class="card-tag">Replay resistance</span>
                        <h3>Freshness plus a stored nonce</h3>
                        <p>
                            A 300-second window bounds how long a captured request stays useful, and every
                            request ID is persisted, so even inside that window it only works once.
                        </p>
                    </article>
                    <article class="card">
                        <span class="card-tag">Data at rest</span>
                        <h3>Encrypted columns, hashed lookups</h3>
                        <p>
                            Phone numbers, WhatsApp IDs, display names and message bodies use authenticated
                            encryption. Because you cannot query ciphertext, each one has a deterministic
                            SHA-256 sibling column that carries the index.
                        </p>
                    </article>
                    <article class="card">
                        <span class="card-tag">Blast radius</span>
                        <h3>Separate secrets per direction</h3>
                        <p>
                            Each product holds a request secret and an event secret. Compromising the one it
                            uses to call in does not let an attacker forge events coming out, and either can
                            be rotated independently from the console.
                        </p>
                    </article>
                    <article class="card">
                        <span class="card-tag">Observability</span>
                        <h3>Logs carry identifiers only</h3>
                        <p>
                            Structured log lines record message and event IDs, never phone numbers, message
                            text, headers or raw payloads. Raw webhook bodies are redacted after their
                            retention window while the normalised audit trail survives.
                        </p>
                    </article>
                    <article class="card">
                        <span class="card-tag">Human access</span>
                        <h3>Operations console limited to reads</h3>
                        <p>
                            The admin panel requires authenticator app MFA and every policy denies create,
                            update and delete. Anything that mutates state is an explicit console command, which leaves a
                            shell history instead of an anonymous click.
                        </p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    {{-- ======================================================= Decisions --}}
    <section class="section section--line" id="decisions">
        <div class="shell">
            <div class="section-head reveal">
                <span class="eyebrow">Decision record</span>
                <h2>The choices worth defending.</h2>
                <p>
                    Each of these had a cheaper alternative. These are the ones where the cheaper option
                    would have cost more later.
                </p>
            </div>

            <div class="table-wrap reveal">
                <table>
                    <caption class="sr-only">Architecture decisions, the alternative considered, and the reasoning</caption>
                    <thead>
                        <tr>
                            <th scope="col">Decision</th>
                            <th scope="col">Alternative</th>
                            <th scope="col">Why</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Persist the webhook, then queue</td>
                            <td>Process inline during the request</td>
                            <td>Providers retry on timeout. Returning 200 as soon as the payload is durable turns a slow processor into a queue backlog instead of duplicate deliveries.</td>
                        </tr>
                        <tr>
                            <td>Three separate queues</td>
                            <td>One default queue</td>
                            <td>A burst of inbound webhooks would otherwise delay outbound sends and event delivery. Isolation means a stuck lane degrades one workload, not all three.</td>
                        </tr>
                        <tr>
                            <td>ULIDs as public identifiers</td>
                            <td>Auto-increment integers</td>
                            <td>Sequential IDs leak volume and invite enumeration. ULIDs stay sortable by creation time without exposing how much traffic the platform handles.</td>
                        </tr>
                        <tr>
                            <td>Deterministic hash beside each encrypted column</td>
                            <td>Plaintext column with an index</td>
                            <td>Lookups still need an index, but a stolen database dump should not hand over phone numbers. The hash carries the index; the ciphertext carries the value.</td>
                        </tr>
                        <tr>
                            <td>Idempotency key plus request hash</td>
                            <td>Idempotency key alone</td>
                            <td>A retried key with identical content should return the original message. The same key with <em>different</em> content is a caller bug, and gets a 409 rather than a silent surprise.</td>
                        </tr>
                        <tr>
                            <td>Provider interface with a disabled fallback</td>
                            <td>Call the Meta client directly</td>
                            <td>A second provider can be swapped in without touching the tables or the product contract, and it stays hard-disabled so a mistyped env value cannot reroute live traffic.</td>
                        </tr>
                        <tr>
                            <td>Read-only admin panel</td>
                            <td>Full CRUD in the panel</td>
                            <td>Nothing in this data model should be edited by hand. Making the panel observational removes a whole class of accidental production damage.</td>
                        </tr>
                        <tr>
                            <td>Tests refuse any non-disposable database</td>
                            <td>Trust the phpunit config</td>
                            <td>A cached config file silently overrides <span class="ic">phpunit.xml</span>, which would point the refresh-database trait at real data. The suite now aborts instead of finding out afterwards.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- ===================================================== Reliability --}}
    <section class="section section--line" id="reliability">
        <div class="shell split">
            <div class="reveal">
                <span class="eyebrow">Reliability</span>
                <h2>Everything external is retried, and every retry is safe.</h2>
            </div>
            <div class="reveal" data-delay="1">
                <p class="lede">
                    A distributed integration fails partially, not cleanly. The design assumes any call can
                    time out after the far side already acted on it.
                </p>

                <div class="steps" style="margin-top:28px">
                    <div class="step">
                        <span class="step-num">&#8635;</span>
                        <div>
                            <strong>Short-circuit before acting</strong>
                            <p>Each job checks whether its record already shows the work as done &mdash; a processed timestamp, a provider message ID, a delivered timestamp &mdash; and returns immediately if so.</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-num">&#9201;</span>
                        <div>
                            <strong>Bounded retries</strong>
                            <p>Five attempts with a 1, 5, 30, 120 second backoff. Enough to survive a provider blip; not so many that a genuinely broken request retries all day.</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-num">&#9888;</span>
                        <div>
                            <strong>Failures are written down</strong>
                            <p>Every job implements a failure handler that records the code and message back onto the row, so a dead job leaves a diagnosis rather than silence.</p>
                        </div>
                    </div>
                    <div class="step">
                        <span class="step-num">&#8681;</span>
                        <div>
                            <strong>Kill switches</strong>
                            <p>Live sending, automatic replies and the fallback provider are three independent flags. A new deployment starts inert and is switched on deliberately.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================================================== Stack --}}
    <section class="section section--line" id="stack">
        <div class="shell">
            <div class="section-head reveal">
                <span class="eyebrow">Stack</span>
                <h2>What this codebase actually exercises.</h2>
            </div>

            <div class="cards cards--3">
                <article class="card reveal" data-delay="1">
                    <span class="card-tag">Platform</span>
                    <h3>Laravel 13 on PHP 8.5</h3>
                    <ul>
                        <li>Queued jobs &amp; scheduling</li>
                        <li>Eloquent casts &amp; observers</li>
                        <li>Form requests &amp; API resources</li>
                        <li>Middleware &amp; policies</li>
                    </ul>
                </article>
                <article class="card reveal" data-delay="2">
                    <span class="card-tag">Integrations</span>
                    <h3>Meta Cloud API &amp; Stripe</h3>
                    <ul>
                        <li>Signed webhook ingestion</li>
                        <li>Checkout &amp; portal sessions</li>
                        <li>Template messaging</li>
                        <li>Provider abstraction layer</li>
                    </ul>
                </article>
                <article class="card reveal" data-delay="3">
                    <span class="card-tag">Security</span>
                    <h3>Applied cryptography</h3>
                    <ul>
                        <li>HMAC-SHA256 request signing</li>
                        <li>Constant-time comparison</li>
                        <li>Encryption at rest</li>
                        <li>Nonce-based replay defence</li>
                    </ul>
                </article>
                <article class="card reveal" data-delay="4">
                    <span class="card-tag">Operations</span>
                    <h3>Filament 5 &amp; Pulse</h3>
                    <ul>
                        <li>MFA-gated admin panel</li>
                        <li>Strict authorization</li>
                        <li>Queue &amp; exception metrics</li>
                        <li>Readiness probes</li>
                    </ul>
                </article>
                <article class="card reveal" data-delay="5">
                    <span class="card-tag">Testing</span>
                    <h3>Pest 5 feature suite</h3>
                    <ul>
                        <li>Faked HTTP, no live calls</li>
                        <li>Signature helpers &amp; factories</li>
                        <li>Webhook &amp; billing coverage</li>
                        <li>CI across PHP 8.3&ndash;8.5</li>
                    </ul>
                </article>
                <article class="card reveal" data-delay="6">
                    <span class="card-tag">Delivery</span>
                    <h3>Operational tooling</h3>
                    <ul>
                        <li>Console commands for secrets</li>
                        <li>Scheduled data retention</li>
                        <li>Documented rollback path</li>
                        <li>OpenAPI 3.1 specification</li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    {{-- ============================================================= CTA --}}
    <section class="section--tight" style="padding-bottom:104px">
        <div class="shell">
            <div class="cta reveal">
                <div>
                    <h2>Want the integration contract instead?</h2>
                    <p>
                        The documentation covers authentication, signing, every endpoint, the error codes and
                        the receiver contract for events.
                    </p>
                </div>
                <div class="cta-actions">
                    <a class="btn btn--primary" href="{{ route('page.docs') }}">API documentation</a>
                    <a class="btn" href="https://buildwithabdallah.com" rel="noopener">More of my work</a>
                </div>
            </div>
        </div>
    </section>

@endsection
