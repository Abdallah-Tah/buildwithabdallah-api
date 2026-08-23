@extends('layouts.site', [
    'title' => 'Legal',
    'description' => 'Privacy policy and terms of use for the Build With Abdallah Central API.',
])

@section('content')

    <section class="hero">
        <div class="shell hero-solo">
            <div class="reveal">
                <span class="eyebrow">Legal</span>
                <h1>Privacy and terms, in plain language.</h1>
                <p class="lede">
                    This service talks to other services. It is not a consumer product, it sets no
                    advertising cookies and it does not sell anything to anyone. The two sections
                    below cover what it stores and the rules for calling it.
                </p>
            </div>
        </div>
    </section>

    <section class="section--tight section--line" id="privacy">
        <div class="shell">
            <div class="section-head reveal">
                <span class="eyebrow">Privacy</span>
                <h2>What this service stores, and why.</h2>
            </div>

            <div class="cards cards--3 reveal">
                <article class="card">
                    <h3>Contact data</h3>
                    <p>
                        Phone numbers, display names and message bodies that cross the WhatsApp
                        boundary are encrypted at rest. Lookups run against deterministic hashes,
                        so no plaintext identifier is ever indexed.
                    </p>
                </article>
                <article class="card">
                    <h3>Logs</h3>
                    <p>
                        Logs carry IDs and statuses, never message content. Delivery attempts store
                        the attempt count and the last error so failures can be diagnosed without
                        touching the payload.
                    </p>
                </article>
                <article class="card">
                    <h3>Retention</h3>
                    <p>
                        Inbound webhook rows are kept for 30 days by default. Sent media expires
                        after 48 hours. Nothing in this service is kept forever on purpose.
                    </p>
                </article>
            </div>

            <p class="lede reveal" style="margin-top:32px;max-width:680px">
                There are no analytics scripts, no tracking pixels and no advertising cookies on
                these pages. Questions go to the contact page on
                <a class="ul-link" href="https://buildwithabdallah.com/contact" rel="noopener">buildwithabdallah.com</a>.
            </p>
        </div>
    </section>

    <section class="section section--line" id="terms">
        <div class="shell">
            <div class="section-head reveal">
                <span class="eyebrow">Terms</span>
                <h2>The rules for calling the API.</h2>
            </div>

            <div class="cards cards--3 reveal">
                <article class="card">
                    <h3>Authorized callers only</h3>
                    <p>
                        Every request must carry a valid application signature. Secrets are issued
                        per product and can be rotated at any time. Callers that fail verification
                        are rejected before any work is queued.
                    </p>
                </article>
                <article class="card">
                    <h3>Fair use</h3>
                    <p>
                        Applications are limited to 120 requests per minute. Events are delivered
                        at least once, so receivers must honour the stable event ID and treat
                        repeats as duplicates.
                    </p>
                </article>
                <article class="card">
                    <h3>Availability</h3>
                    <p>
                        The service is operated by a single person and provided as is. The
                        readiness probe at <span class="ic">GET /health/ready</span> reports the
                        honest state of every dependency at any moment.
                    </p>
                </article>
            </div>
        </div>
    </section>

@endsection
