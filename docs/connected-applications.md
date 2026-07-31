# Connected Applications and Kirada

Manage applications without an admin UI:

```shell
php artisan bwa:application:create
php artisan bwa:application:list
php artisan bwa:application:rotate-secret kirada --type=both
php artisan bwa:application:disable kirada
```

Creation and rotation display secrets once. Store Kirada’s request secret in Kirada’s secret store and its event secret in both systems’ secret stores. Listing never displays secrets.

## Kirada outgoing requests

Kirada calls `POST https://api.buildwithabdallah.com/api/v1/whatsapp/messages` with:

```text
X-BWA-App: kirada
X-BWA-Timestamp: <Unix timestamp>
X-BWA-Request-ID: <UUID or ULID>
X-BWA-Signature: sha256=<hex>
```

Canonical content:

```text
HTTP_METHOD
REQUEST_PATH
TIMESTAMP
REQUEST_ID
SHA256_RAW_BODY
```

Example Laravel service:

```php
final class BwaWhatsApp
{
    public function sendTemplate(array $payload): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $timestamp = (string) now()->timestamp;
        $requestId = (string) Str::uuid();
        $path = '/api/v1/whatsapp/messages';
        $canonical = "POST\n{$path}\n{$timestamp}\n{$requestId}\n".hash('sha256', $body);
        $signature = 'sha256='.hash_hmac('sha256', $canonical, config('services.bwa.request_secret'));

        return Http::withHeaders([
            'X-BWA-App' => 'kirada',
            'X-BWA-Timestamp' => $timestamp,
            'X-BWA-Request-ID' => $requestId,
            'X-BWA-Signature' => $signature,
        ])->withBody($body, 'application/json')
            ->post('https://api.buildwithabdallah.com'.$path)
            ->throw()
            ->json();
    }
}
```

Use a stable, unique `idempotency_key` for each business action. Repeating identical content returns the original message; different content with the same key returns 409. Free-form text requires an open customer-service window; templates do not.

## Kirada incoming events

Kirada should expose `POST /api/internal/bwa/whatsapp/events`, accept 200 or 202, and verify the exact raw body using the same canonical format and its event secret. It must reject stale timestamps and replayed request IDs. The event contains stable internal contact/message IDs and omits the phone number by default. Persist the event ID before processing so retries are idempotent.

To add another product, create its connected application, set its webhook URL, add/confirm the definition in `config/bwa_products.php`, store both secrets safely, and implement the same signing verification.
