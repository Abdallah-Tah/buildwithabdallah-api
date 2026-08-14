# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- pestphp/pest (PEST) - v5
- phpunit/phpunit (PHPUNIT) - v13
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd at `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs. Never run commands to serve the site. It is always available.
- Use the `herd` CLI to manage services, PHP versions, and sites (e.g. `herd sites`, `herd services:start <service>`, `herd php:list`). Run `herd list` to discover all available commands.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

</laravel-boost-guidelines>

## Project Overview

Build With Abdallah Central API (`api.buildwithabdallah.com`): a headless Laravel service that is the **single credential boundary** between third-party providers (Meta WhatsApp Cloud API, Stripe) and Build With Abdallah products (Kirada, Djib Payroll, SMKit). Products never hold Meta or Stripe credentials; they exchange HMAC-signed requests and events with this API.

There is no product UI. The only human-facing surfaces are the Filament operations panel at `/admin` (read-only, TOTP-required) and Laravel Pulse at `/pulse`. Reference docs live in `docs/` (`architecture.md`, `security.md`, `connected-applications.md`, `deployment.md`, `meta-whatsapp-setup.md`, `openapi.yaml`) — consult them before changing signing, routing, or deployment behavior.

## Commands

```shell
composer run dev          # serve + queue:listen + pail + vite, concurrently
composer test             # config:clear then php artisan test (use this, see gotcha below)
php artisan test --compact
php artisan test --compact --filter=WebhookProcessing
php artisan test tests/Feature/BillingApiTest.php
vendor/bin/pint --dirty --format agent   # required after editing PHP

# Queue workers (all async work is queued)
php artisan queue:work --queue=whatsapp-webhooks,whatsapp-outbound,application-events,default --tries=5 --timeout=60

# Operations commands
php artisan bwa:application:create {--name=} {--slug=} {--webhook-url=}
php artisan bwa:application:list
php artisan bwa:application:rotate-secret {slug} {--type=request|event|both}
php artisan bwa:application:disable {slug}
php artisan bwa:whatsapp:test-send {recipient} {message}
php artisan bwa:whatsapp:prune-webhooks {--dry-run}   # also scheduled daily
php artisan ops:admin {email} {--name=} {--password=}  # create/promote a panel admin
```

### Testing gotcha

`tests/TestCase.php` throws if the test run is not on `sqlite`/`:memory:`. A cached `bootstrap/cache/config.php` overrides `phpunit.xml` `<env>` entries and would point `RefreshDatabase` at the real database. If tests abort with "Refusing to run tests against the [...] connection", run `php artisan optimize:clear`. `composer test` clears config first for this reason.

## Architecture

### Three trust boundaries

1. **Inbound provider webhooks** (`routes/web.php`, CSRF-exempt): `POST /webhooks/meta/whatsapp` (`meta.whatsapp.signature`), `POST /webhooks/sent/whatsapp` (`sent.whatsapp.signature`), `POST /webhooks/stripe` (signature verified in-controller by `App\Billing\StripeWebhookSignature`). Controllers verify the raw body signature, persist a durable event row, dispatch a job, and return 200 — no domain work happens inline.
2. **Product → API** (`routes/api.php`, all behind the `bwa.application` middleware): `AuthenticateConnectedApplication` validates `X-BWA-App`, `X-BWA-Timestamp`, `X-BWA-Request-ID`, `X-BWA-Signature`, enforces app-enabled, 300s freshness, a persisted single-use nonce (`application_request_nonces`), and a 120/min rate limit. The authenticated `ConnectedApplication` is put on `$request->attributes` as `connected_application`.
3. **API → product** (`ApplicationEventDispatcher` + `DispatchApplicationEvent`): outbound events are signed with the application's *event* secret and POSTed to its webhook URL; 200/202 means delivered, 5xx/429 re-throws for retry.

All three directions use the same canonical string via `App\Messaging\HmacSigner`: `METHOD\nPATH\nTIMESTAMP\nREQUEST_ID\nSHA256(raw body)`, HMAC-SHA256, prefixed `sha256=`, compared with `hash_equals`. Never reconstruct the body from parsed input — always sign/verify `$request->getContent()`.

### Queues

Three isolated workloads, each idempotent and bounded (`tries = 5`, exponential `backoff`): `whatsapp-webhooks` (`ProcessWhatsAppWebhook`), `whatsapp-outbound` (`SendWhatsAppMessage`), `application-events` (`DispatchApplicationEvent`). Every job short-circuits when its record shows the work already completed (`processed_at`, `provider_message_id`, `delivered_at`), and every job's `failed()` writes the failure state back onto the record.

### WhatsApp providers

`WhatsAppProviderManager` resolves `services.whatsapp.provider` to `MetaWhatsAppProvider` (production) or `SentWhatsAppProvider` (fallback, hard-disabled unless `SENT_DM_ENABLED=true`). New channels implement `WhatsAppProvider` and a webhook processor; the WhatsApp tables and product-facing contracts stay unchanged. Two separate safety flags gate real traffic: `WHATSAPP_LIVE_SEND_ENABLED` (sends fail with `LIVE_SEND_DISABLED` when false) and `WHATSAPP_AUTOREPLY_ENABLED`.

### Inbound routing

`WhatsAppWebhookProcessor` normalizes contacts/conversations/messages/statuses, then `ConversationRouter` maps the sender's reply to a product defined in `config/bwa_products.php` (numeric selection or alias, plus menu commands that un-route the conversation). A routed conversation gets a `connected_application_id`; message status changes then fan out to that product via `WhatsAppMessageObserver` → `ApplicationEventDelivery` → `DispatchApplicationEvent`. Adding a product means creating its connected application *and* adding its entry to `config/bwa_products.php`.

### Billing

`POST /api/v1/billing/{checkout,portal}-sessions` create Stripe sessions on behalf of a connected application; `BillingRedirectPolicy` restricts redirect URLs to the app's webhook host plus `metadata.billing_allowed_redirect_hosts`. Inbound Stripe webhooks are persisted as `StripeWebhookEvent` and routed by `RouteStripeEvent` to the owning application (via `BillingCustomer.stripe_customer_id`, falling back to `metadata.bwa_app`) as a `billing.stripe.*` application event. `ConnectedApplication::webhookUrlFor()` sends `billing.*` events to `metadata.billing_webhook_url` when set.

### Data conventions

- Models use `HasUlids`; IDs exposed to products are internal ULIDs, never provider IDs.
- Sensitive columns are `encrypted` casts with a parallel deterministic `*_hash` (SHA-256) column for lookups — e.g. `WhatsAppContact.phone_number_encrypted` + `phone_number_hash`. Query by hash, never by plaintext.
- Encrypted/raw-payload attributes are in `$hidden`; API output goes through `App\Http\Resources\*`. Logs use structured event names (`whatsapp.message.sent`, `application.event.failed`) with IDs only — never phone numbers, message text, secrets, or raw payloads.
- Idempotency: outbound messages key on `(connected_application_id, idempotency_key)` with a `request_hash`; same key + same body returns the original message, same key + different body returns 409 `IDEMPOTENCY_CONFLICT`.
- API errors are always `{"error": {"code": "UPPER_SNAKE", "message": "..."}}`; `CreateOutboundWhatsAppMessage::fail()` and the auth middleware are the reference shape.

### Filament panel

`AdminPanelProvider` is `strictAuthorization()` with mandatory app-based MFA; only `User::isOperationsAdmin()` may enter. Every resource policy extends `App\Policies\ReadOnlyOperationsPolicy` — the panel is deliberately observational, so create/update/delete stay `false`. Mutating operations belong in `bwa:*` console commands, not the panel.

## Testing conventions

Pest, `LazilyRefreshDatabase` for `tests/Feature`. `tests/Pest.php` exposes `signedApplicationHeaders($application, $method, $path, $body, $requestId, $timestamp)` — use it for every authenticated API test rather than hand-rolling signatures. Tests must fake HTTP (`Http::fake()`) and never reach Meta, Sent.dm, or Stripe; `phpunit.xml` already forces the live-send and Sent.dm flags off.
