@extends('layouts.site', [
    'title' => 'API documentation',
    'description' => 'Integration guide for the Build With Abdallah Central API: HMAC request signing, WhatsApp messaging and Stripe billing endpoints, idempotency rules, error codes and the signed event receiver contract.',
])

@section('content')

    <div class="shell docs">

        {{-- ===================================================== Sidebar --}}
        <aside class="docs-nav" aria-label="Documentation sections">
            <h2>On this page</h2>
            <ul>
                <li><a href="#overview">Overview</a></li>
                <li><a href="#authentication">Authentication</a></li>
                <li><a href="#signing">Signing a request</a></li>
                <li><a href="#messaging">Messaging endpoints</a></li>
                <li><a href="#send-message" class="sub">Send a message</a></li>
                <li><a href="#get-message" class="sub">Get a message</a></li>
                <li><a href="#get-conversation" class="sub">Get a conversation</a></li>
                <li><a href="#route-conversation" class="sub">Route a conversation</a></li>
                <li><a href="#billing">Billing endpoints</a></li>
                <li><a href="#checkout-session" class="sub">Checkout session</a></li>
                <li><a href="#portal-session" class="sub">Portal session</a></li>
                <li><a href="#rules">Idempotency &amp; windows</a></li>
                <li><a href="#errors">Error codes</a></li>
                <li><a href="#events">Receiving events</a></li>
                <li><a href="#health">Health checks</a></li>
            </ul>
        </aside>

        <div class="docs-body">

            {{-- A <header> here would register as a second banner landmark. --}}
            <div class="reveal" style="margin-bottom:56px">
                <span class="eyebrow">API reference &middot; v1</span>
                <h1 style="font-size:clamp(34px,4.4vw,48px)">Central API documentation</h1>
                <p class="lede" style="margin-top:20px">
                    Everything a connected product needs to send WhatsApp messages, start Stripe billing
                    flows, and receive signed events back.
                </p>
                <div class="hero-meta">
                    <span class="pill pill--brand">Base URL: api.buildwithabdallah.com</span>
                    <span class="pill">OpenAPI 3.1</span>
                </div>
            </div>

            {{-- ================================================== Overview --}}
            <section class="docs-section reveal" id="overview">
                <h2>Overview</h2>
                <p>
                    This API is consumed by trusted first-party applications, not by end users. There are no
                    bearer tokens or OAuth flows: each connected application is issued two secrets and signs
                    every request it makes.
                </p>
                <ul class="bullets">
                    <li>All endpoints live under <span class="ic">https://api.buildwithabdallah.com</span>.</li>
                    <li>Requests and responses are JSON. Send <span class="ic">Content-Type: application/json</span>.</li>
                    <li>Product-facing routes are versioned under <span class="ic">/api/v1</span>.</li>
                    <li>Errors always return <span class="ic">{"error": {"code": "...", "message": "..."}}</span>.</li>
                </ul>

                <div class="callout">
                    <p>
                        <strong>Two secrets per application.</strong> The <em>request secret</em> signs calls you
                        make into this API. The <em>event secret</em> verifies events this API sends to your
                        webhook. They are rotated independently and must never be swapped.
                    </p>
                </div>
            </section>

            {{-- ============================================ Authentication --}}
            <section class="docs-section reveal" id="authentication">
                <h2>Authentication</h2>
                <p>
                    Every request to <span class="ic">/api/v1/*</span> must carry these four headers. A missing
                    or malformed header is rejected with <span class="ic">401 UNAUTHENTICATED</span> before any
                    routing happens.
                </p>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Header</th>
                                <th scope="col">Value</th>
                                <th scope="col">Rule</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="ic">X-BWA-App</span></td>
                                <td>Your application slug, e.g. <span class="ic">kirada</span></td>
                                <td>Must exist and be enabled.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">X-BWA-Timestamp</span></td>
                                <td>Unix timestamp in seconds</td>
                                <td>Must be within 300 seconds of server time, in either direction.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">X-BWA-Request-ID</span></td>
                                <td>A fresh UUID or ULID</td>
                                <td>Single use. Reusing one returns <span class="ic">409 REPLAYED_REQUEST</span>.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">X-BWA-Signature</span></td>
                                <td><span class="ic">sha256=&lt;hex&gt;</span></td>
                                <td>HMAC-SHA256 of the canonical string, keyed with your request secret.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 id="canonical">The canonical string</h3>
                <p>
                    Build the signed payload by joining five fields with newlines. The body hash must be taken
                    over the <strong>exact bytes you transmit</strong> &mdash; re-serialising the payload after
                    signing will invalidate the signature.
                </p>

                <div class="code-block">
                    <div class="code-head">
                        <div class="code-tabs"><span class="code-tab" aria-selected="true">canonical</span></div>
                        <button type="button" class="copy-btn">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="5.5" y="5.5" width="8" height="8" rx="1.5"/><path d="M10.5 5.5v-3h-8v8h3"/>
                            </svg>
                            <span data-copy-label>Copy</span>
                        </button>
                    </div>
                    @verbatim
                    <pre class="code" data-lang="text">HTTP_METHOD
REQUEST_PATH
TIMESTAMP
REQUEST_ID
SHA256_HEX_OF_RAW_BODY</pre>
                    @endverbatim
                </div>

                <p>
                    Signatures are compared in constant time. For requests without a body, hash the empty
                    string.
                </p>
            </section>

            {{-- =================================================== Signing --}}
            <section class="docs-section reveal" id="signing">
                <h2>Signing a request</h2>
                <p>
                    A complete, working example in three environments. Store the request secret in your
                    application's secret store &mdash; never in source control.
                </p>

                <div class="code-block" data-code-tabs>
                    <div class="code-head">
                        <div class="code-tabs" role="tablist" aria-label="Language">
                            <button type="button" class="code-tab" role="tab" data-lang="php" aria-selected="true">PHP</button>
                            <button type="button" class="code-tab" role="tab" data-lang="js" aria-selected="false">Node</button>
                            <button type="button" class="code-tab" role="tab" data-lang="bash" aria-selected="false">cURL</button>
                        </div>
                        <button type="button" class="copy-btn">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="5.5" y="5.5" width="8" height="8" rx="1.5"/><path d="M10.5 5.5v-3h-8v8h3"/>
                            </svg>
                            <span data-copy-label>Copy</span>
                        </button>
                    </div>
                    @verbatim
                    <pre class="code" data-lang="php">use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

$path      = '/api/v1/whatsapp/messages';
$body      = json_encode($payload, JSON_THROW_ON_ERROR);
$timestamp = (string) now()->timestamp;
$requestId = (string) Str::uuid();

$canonical = implode("\n", [
    'POST',
    $path,
    $timestamp,
    $requestId,
    hash('sha256', $body),
]);

$signature = 'sha256=' . hash_hmac('sha256', $canonical, config('services.bwa.request_secret'));

// Send the same bytes that were hashed above.
$message = Http::withHeaders([
    'X-BWA-App'        => 'kirada',
    'X-BWA-Timestamp'  => $timestamp,
    'X-BWA-Request-ID' => $requestId,
    'X-BWA-Signature'  => $signature,
])->withBody($body, 'application/json')
    ->post('https://api.buildwithabdallah.com' . $path)
    ->throw()
    ->json();</pre>
                    <pre class="code" data-lang="js" hidden>import { createHash, createHmac, randomUUID } from 'node:crypto';

const path      = '/api/v1/whatsapp/messages';
const body      = JSON.stringify(payload);
const timestamp = Math.floor(Date.now() / 1000).toString();
const requestId = randomUUID();

const canonical = [
  'POST',
  path,
  timestamp,
  requestId,
  createHash('sha256').update(body).digest('hex'),
].join('\n');

const signature = 'sha256=' + createHmac('sha256', process.env.BWA_REQUEST_SECRET)
  .update(canonical)
  .digest('hex');

// Send the same bytes that were hashed above.
const response = await fetch('https://api.buildwithabdallah.com' + path, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-BWA-App': 'kirada',
    'X-BWA-Timestamp': timestamp,
    'X-BWA-Request-ID': requestId,
    'X-BWA-Signature': signature,
  },
  body,
});</pre>
                    <pre class="code" data-lang="bash" hidden># Requires openssl and uuidgen.
ENDPOINT="/api/v1/whatsapp/messages"
BODY='{"recipient":"+12070000000","type":"template","product":"kirada","template":{"name":"order_update","language":"en_US"},"idempotency_key":"order-1042-shipped"}'

TS=$(date +%s)
RID=$(uuidgen)
HASH=$(printf '%s' "$BODY" | openssl dgst -sha256 -hex | awk '{print $NF}')
CANONICAL=$(printf 'POST\n%s\n%s\n%s\n%s' "$ENDPOINT" "$TS" "$RID" "$HASH")
SIG=$(printf '%s' "$CANONICAL" | openssl dgst -sha256 -hmac "$BWA_REQUEST_SECRET" -hex | awk '{print $NF}')

curl -sS "https://api.buildwithabdallah.com$ENDPOINT" \
  -H "Content-Type: application/json" \
  -H "X-BWA-App: kirada" \
  -H "X-BWA-Timestamp: $TS" \
  -H "X-BWA-Request-ID: $RID" \
  -H "X-BWA-Signature: sha256=$SIG" \
  -d "$BODY"</pre>
                    @endverbatim
                </div>
            </section>

            {{-- ================================================= Messaging --}}
            <section class="docs-section reveal" id="messaging">
                <h2>Messaging endpoints</h2>
                <p>
                    Outbound sends are queued, not synchronous. A successful call returns
                    <span class="ic">202 Accepted</span> with an internal message ID; the final delivery state
                    arrives later as an event on your webhook.
                </p>

                {{-- Send a message --}}
                <div class="endpoint" id="send-message">
                    <span class="verb verb--post">POST</span>
                    <code>/api/v1/whatsapp/messages</code>
                    <span class="pill">202</span>
                </div>

                <p>Queue a WhatsApp text or template message.</p>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Field</th>
                                <th scope="col">Type</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="ic">recipient</span></td>
                                <td>string, required</td>
                                <td>E.164, 7&ndash;15 digits. A leading <span class="ic">+</span> is optional.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">type</span></td>
                                <td>string, required</td>
                                <td><span class="ic">text</span> or <span class="ic">template</span>.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">body</span></td>
                                <td>string</td>
                                <td>Required when type is <span class="ic">text</span>. Max 4096 characters.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">template.name</span></td>
                                <td>string</td>
                                <td>Required when type is <span class="ic">template</span>. Max 512 characters.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">template.language</span></td>
                                <td>string</td>
                                <td>Required with a template, e.g. <span class="ic">en_US</span>.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">template.components</span></td>
                                <td>array</td>
                                <td>Optional. Passed through to the provider unchanged.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">product</span></td>
                                <td>string, required</td>
                                <td>Must match your application slug unless you are explicitly allowed to send for others.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">correlation_id</span></td>
                                <td>string, nullable</td>
                                <td>Your own reference. Echoed back on every event.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">idempotency_key</span></td>
                                <td>string, required</td>
                                <td>Stable per business action. See <a class="ul-link" href="#rules">idempotency</a>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="code-block" data-code-tabs>
                    <div class="code-head">
                        <div class="code-tabs" role="tablist" aria-label="Example">
                            <button type="button" class="code-tab" role="tab" data-lang="req" aria-selected="true">Request</button>
                            <button type="button" class="code-tab" role="tab" data-lang="res" aria-selected="false">Response</button>
                        </div>
                        <button type="button" class="copy-btn">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="5.5" y="5.5" width="8" height="8" rx="1.5"/><path d="M10.5 5.5v-3h-8v8h3"/>
                            </svg>
                            <span data-copy-label>Copy</span>
                        </button>
                    </div>
                    @verbatim
                    <pre class="code" data-lang="req">{
  "recipient": "+12070000000",
  "type": "template",
  "product": "kirada",
  "template": {
    "name": "order_update",
    "language": "en_US",
    "components": []
  },
  "correlation_id": "order-1042",
  "idempotency_key": "order-1042-shipped"
}</pre>
                    <pre class="code" data-lang="res" hidden>{
  "data": {
    "id": "01JQ2R8N4C6R3F0M9YB7K1WZ5X",
    "status": "queued",
    "correlation_id": "order-1042",
    "idempotency_key": "order-1042-shipped",
    "direction": "outbound",
    "type": "template",
    "provider": "meta",
    "provider_message_id": null,
    "created_at": "2026-08-14T09:31:07+00:00"
  }
}</pre>
                    @endverbatim
                </div>

                {{-- Get a message --}}
                <div class="endpoint" id="get-message">
                    <span class="verb verb--get">GET</span>
                    <code>/api/v1/whatsapp/messages/{message}</code>
                    <span class="pill">200</span>
                </div>
                <p>
                    Fetch the current state of a message by its internal ID. Returns the same shape as the
                    send response, with <span class="ic">status</span> advanced to
                    <span class="ic">accepted</span>, <span class="ic">sent</span>,
                    <span class="ic">delivered</span>, <span class="ic">read</span> or
                    <span class="ic">failed</span>.
                </p>

                {{-- Get a conversation --}}
                <div class="endpoint" id="get-conversation">
                    <span class="verb verb--get">GET</span>
                    <code>/api/v1/whatsapp/conversations/{conversation}</code>
                    <span class="pill">200</span>
                </div>
                <p>Fetch a conversation, including whether its customer-service window is still open.</p>

                <div class="code-block">
                    <div class="code-head">
                        <div class="code-tabs"><span class="code-tab" aria-selected="true">Response</span></div>
                        <button type="button" class="copy-btn">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="5.5" y="5.5" width="8" height="8" rx="1.5"/><path d="M10.5 5.5v-3h-8v8h3"/>
                            </svg>
                            <span data-copy-label>Copy</span>
                        </button>
                    </div>
                    @verbatim
                    <pre class="code" data-lang="json">{
  "data": {
    "id": "01JQ2R8N4C6R3F0M9YB7K1WZ5X",
    "contact_id": "01JQ2R8MZZ8Q1V4T7X2A0B9C6D",
    "product": "kirada",
    "state": "active",
    "customer_service_window_expires_at": "2026-08-15T09:12:44+00:00",
    "last_incoming_message_at": "2026-08-14T09:12:44+00:00",
    "last_outgoing_message_at": "2026-08-14T09:31:07+00:00"
  }
}</pre>
                    @endverbatim
                </div>

                {{-- Route a conversation --}}
                <div class="endpoint" id="route-conversation">
                    <span class="verb verb--post">POST</span>
                    <code>/api/v1/whatsapp/conversations/{conversation}/route</code>
                    <span class="pill">200</span>
                </div>
                <p>
                    Reassign a conversation to a product. Accepts one field,
                    <span class="ic">product</span>, which must be one of
                    <span class="ic">kirada</span>, <span class="ic">djib-payroll</span>,
                    <span class="ic">smkit</span>, <span class="ic">custom-software</span> or
                    <span class="ic">general-support</span>.
                </p>
            </section>

            {{-- =================================================== Billing --}}
            <section class="docs-section reveal" id="billing">
                <h2>Billing endpoints</h2>
                <p>
                    Stripe credentials stay in this service. Products ask for a session URL and redirect the
                    customer to it; subscription lifecycle updates arrive later as
                    <span class="ic">billing.stripe.*</span> events.
                </p>

                <div class="callout">
                    <p>
                        <strong>Redirect URLs are allow-listed.</strong> Every URL you pass must resolve to your
                        application's registered webhook host, or to a host explicitly configured for your
                        application. Anything else is rejected as a validation error.
                    </p>
                </div>

                {{-- Checkout --}}
                <div class="endpoint" id="checkout-session">
                    <span class="verb verb--post">POST</span>
                    <code>/api/v1/billing/checkout-sessions</code>
                    <span class="pill">201</span>
                </div>
                <p>Create a Stripe Checkout session for a subscription plan.</p>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Field</th>
                                <th scope="col">Type</th>
                                <th scope="col">Notes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="ic">external_customer_id</span></td>
                                <td>string, required</td>
                                <td>Your own customer identifier. Mapped to a Stripe customer here.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">customer.email</span></td>
                                <td>email, required</td>
                                <td>&mdash;</td>
                            </tr>
                            <tr>
                                <td><span class="ic">customer.name</span></td>
                                <td>string, required</td>
                                <td>&mdash;</td>
                            </tr>
                            <tr>
                                <td><span class="ic">plan.id</span> / <span class="ic">plan.name</span></td>
                                <td>string, required</td>
                                <td>Your plan reference and its display name.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">plan.amount</span></td>
                                <td>integer, required</td>
                                <td>Minor units. Between 50 and 99,999,999.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">plan.currency</span></td>
                                <td>string, required</td>
                                <td>Three letters, e.g. <span class="ic">usd</span>.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">plan.interval</span></td>
                                <td>string, required</td>
                                <td><span class="ic">month</span> or <span class="ic">year</span>.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">success_url</span> / <span class="ic">cancel_url</span></td>
                                <td>https URL, required</td>
                                <td>Must be on an allow-listed host. Max 2048 characters.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">idempotency_key</span></td>
                                <td>string, required</td>
                                <td>Stable per checkout attempt.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Portal --}}
                <div class="endpoint" id="portal-session">
                    <span class="verb verb--post">POST</span>
                    <code>/api/v1/billing/portal-sessions</code>
                    <span class="pill">201</span>
                </div>
                <p>
                    Create a Stripe billing portal session so a customer can manage their own subscription.
                    Requires <span class="ic">external_customer_id</span>, an https
                    <span class="ic">return_url</span> on an allow-listed host, and an
                    <span class="ic">idempotency_key</span>.
                </p>
            </section>

            {{-- ===================================================== Rules --}}
            <section class="docs-section reveal" id="rules">
                <h2>Idempotency &amp; the customer-service window</h2>

                <h3 id="idempotency">Idempotency</h3>
                <p>
                    Every write endpoint requires an <span class="ic">idempotency_key</span>. Keys are scoped
                    to your application, so they only need to be unique within your own system. Use a stable
                    key per business action &mdash; not a random value per attempt, which would defeat the
                    purpose.
                </p>
                <ul class="bullets">
                    <li><strong>Same key, same payload</strong> &mdash; returns the original record. Safe to retry.</li>
                    <li><strong>Same key, different payload</strong> &mdash; returns <span class="ic">409 IDEMPOTENCY_CONFLICT</span>. This means a bug on the caller's side, and is deliberately loud.</li>
                    <li><strong>New key</strong> &mdash; creates a new record.</li>
                </ul>
                <p>
                    Note that the idempotency key is separate from
                    <span class="ic">X-BWA-Request-ID</span>. The request ID must be
                    <em>unique on every HTTP call</em>, including retries, because it is the replay defence.
                    The idempotency key must be <em>stable across retries</em>, because it is the
                    deduplication key.
                </p>

                <h3 id="window">The 24-hour customer-service window</h3>
                <p>
                    WhatsApp only allows free-form messages within 24 hours of the customer's last inbound
                    message. This API enforces that before anything reaches the provider.
                </p>
                <ul class="bullets">
                    <li><span class="ic">type: "text"</span> requires an open window, otherwise <span class="ic">422 CUSTOMER_SERVICE_WINDOW_EXPIRED</span>.</li>
                    <li><span class="ic">type: "template"</span> is always allowed.</li>
                    <li>Check <span class="ic">customer_service_window_expires_at</span> on the conversation before choosing which to send.</li>
                </ul>
            </section>

            {{-- ==================================================== Errors --}}
            <section class="docs-section reveal" id="errors">
                <h2>Error codes</h2>
                <p>
                    Errors use a consistent envelope. Handle the <span class="ic">code</span>, not the
                    message text &mdash; messages may be reworded.
                </p>

                <div class="code-block">
                    <div class="code-head">
                        <div class="code-tabs"><span class="code-tab" aria-selected="true">Error envelope</span></div>
                    </div>
                    @verbatim
                    <pre class="code" data-lang="json">{
  "error": {
    "code": "CUSTOMER_SERVICE_WINDOW_EXPIRED",
    "message": "An approved WhatsApp template is required outside the customer-service window."
  }
}</pre>
                    @endverbatim
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th scope="col">Code</th>
                                <th scope="col">HTTP</th>
                                <th scope="col">What to do</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><span class="ic">UNAUTHENTICATED</span></td>
                                <td>401</td>
                                <td>Check the four headers, your clock drift, and that you signed the exact bytes sent.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">APPLICATION_DISABLED</span></td>
                                <td>403</td>
                                <td>The application has been switched off. Do not retry.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">PRODUCT_NOT_AUTHORIZED</span></td>
                                <td>403</td>
                                <td>The <span class="ic">product</span> field does not match your application.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">REPLAYED_REQUEST</span></td>
                                <td>409</td>
                                <td>Generate a fresh <span class="ic">X-BWA-Request-ID</span> for every attempt.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">IDEMPOTENCY_CONFLICT</span></td>
                                <td>409</td>
                                <td>The key was already used with different content. Fix the caller.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">CUSTOMER_SERVICE_WINDOW_EXPIRED</span></td>
                                <td>422</td>
                                <td>Send an approved template instead of free-form text.</td>
                            </tr>
                            <tr>
                                <td><span class="ic">RATE_LIMITED</span></td>
                                <td>429</td>
                                <td>You exceeded 120 requests per minute. Back off and retry.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- ==================================================== Events --}}
            <section class="docs-section reveal" id="events">
                <h2>Receiving events</h2>
                <p>
                    Message status changes and Stripe lifecycle updates are pushed to your registered webhook
                    URL as signed events. Your endpoint must verify them with your <em>event</em> secret,
                    using the same canonical string as above.
                </p>

                <h3 id="event-contract">Receiver contract</h3>
                <ul class="bullets">
                    <li>Respond <span class="ic">200</span> or <span class="ic">202</span> to acknowledge. Anything else is treated as a failure.</li>
                    <li>A <span class="ic">5xx</span> or <span class="ic">429</span> is retried with backoff, up to five attempts. Other statuses are recorded as permanent failures.</li>
                    <li>Verify against the raw request body, and reject stale timestamps and reused request IDs exactly as this API does.</li>
                    <li>Persist <span class="ic">event_id</span> before processing, and ignore an ID you have already seen &mdash; retries are expected.</li>
                    <li>Phone numbers are not included by default. Events carry internal contact and message IDs.</li>
                </ul>

                <div class="code-block">
                    <div class="code-head">
                        <div class="code-tabs"><span class="code-tab" aria-selected="true">whatsapp.message.status</span></div>
                        <button type="button" class="copy-btn">
                            <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <rect x="5.5" y="5.5" width="8" height="8" rx="1.5"/><path d="M10.5 5.5v-3h-8v8h3"/>
                            </svg>
                            <span data-copy-label>Copy</span>
                        </button>
                    </div>
                    @verbatim
                    <pre class="code" data-lang="json">{
  "id": "01JQ2RB4M7X5E2N8P0T3V6Y9QK",
  "event_id": "01JQ2RB4M7X5E2N8P0T3V6Y9QK",
  "type": "whatsapp.message.status",
  "event_type": "whatsapp.message.status",
  "occurred_at": "2026-08-14T09:31:12+00:00",
  "product": "kirada",
  "data": {
    "message_id": "01JQ2R8N4C6R3F0M9YB7K1WZ5X",
    "provider": "meta",
    "provider_message_id": "wamid.HBgLM...",
    "correlation_id": "order-1042",
    "idempotency_key": "order-1042-shipped",
    "status": "delivered",
    "occurred_at": "2026-08-14T09:31:12+00:00",
    "error": null
  }
}</pre>
                    @endverbatim
                </div>

                <p>
                    Billing events follow the same envelope with a
                    <span class="ic">billing.stripe.&lt;stripe event type&gt;</span> type, and a
                    <span class="ic">data</span> object carrying normalised Stripe fields such as
                    <span class="ic">stripe_subscription_id</span>, <span class="ic">status</span>,
                    <span class="ic">currency</span> and <span class="ic">current_period_end</span>.
                </p>

                <div class="callout">
                    <p>
                        <strong>Failed sends carry a reason.</strong> When <span class="ic">status</span> is
                        <span class="ic">failed</span>, the <span class="ic">error</span> object contains the
                        provider's code and message so you can distinguish a bad number from an outage.
                    </p>
                </div>
            </section>

            {{-- ==================================================== Health --}}
            <section class="docs-section reveal" id="health">
                <h2>Health checks</h2>
                <p>Two unauthenticated endpoints, intended for monitoring.</p>

                <div class="endpoint">
                    <span class="verb verb--get">GET</span>
                    <code>/up</code>
                    <span class="pill">200</span>
                </div>
                <p>Liveness only. Confirms the application boots and can serve a request.</p>

                <div class="endpoint">
                    <span class="verb verb--get">GET</span>
                    <code>/health/ready</code>
                    <span class="pill">200 / 503</span>
                </div>
                <p>
                    Readiness. Verifies the database connection, the cache store, the queue configuration and
                    that the active WhatsApp provider has complete credentials. Returns
                    <span class="ic">503</span> when any check fails, so a load balancer can drain the node.
                </p>

                <div class="code-block">
                    <div class="code-head">
                        <div class="code-tabs"><span class="code-tab" aria-selected="true">Response</span></div>
                    </div>
                    @verbatim
                    <pre class="code" data-lang="json">{
  "status": "ready",
  "checks": {
    "database": "ok",
    "cache": "ok",
    "queue": "ok",
    "whatsapp_provider": "ok"
  },
  "whatsapp_provider": "meta"
}</pre>
                    @endverbatim
                </div>
            </section>

            <div class="cta reveal">
                <div>
                    <h2>Need an application provisioned?</h2>
                    <p>Connected applications are issued from the console, with both secrets shown once.</p>
                </div>
                <div class="cta-actions">
                    <a class="btn btn--primary" href="https://buildwithabdallah.com/contact" rel="noopener">Get in touch</a>
                    <a class="btn" href="{{ route('page.about') }}">Architecture</a>
                </div>
            </div>

        </div>
    </div>

@endsection
