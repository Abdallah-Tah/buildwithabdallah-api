# Build With Abdallah Messaging API

The API is the single credential boundary between Meta WhatsApp Cloud API and Build With Abdallah products.

```text
Meta WhatsApp Cloud API
        |
        v
GET/POST /webhooks/meta/whatsapp
        |
        v
durable webhook event -> whatsapp-webhooks queue
        |
        +-- contacts / conversations / messages / statuses
        +-- product router
        +-- signed application-events queue -> Kirada and future products

Trusted products -> signed /api/v1 requests -> whatsapp-outbound queue -> Meta
```

Controllers only verify transport concerns and invoke domain services. Webhook events are persisted before HTTP 200 is returned. `ProcessWhatsAppWebhook`, `SendWhatsAppMessage`, and `DispatchApplicationEvent` isolate the three asynchronous workloads.

## Persistence

- `connected_applications`: encrypted request/event secrets and application delivery settings.
- `whatsapp_contacts`: deterministic lookup hashes plus encrypted identifiers and names.
- `whatsapp_conversations`: product route, state, and customer-service window.
- `whatsapp_messages`: encrypted text, direction, type, delivery state, and idempotency.
- `whatsapp_webhook_events`: durable raw receipt and processing audit.
- `application_request_nonces`: replay prevention.
- `application_event_deliveries`: stable event IDs and delivery attempts.

Media IDs and safe metadata are normalized, but media is not downloaded. Unknown message and status types are tolerated. New channels can add clients/processors without changing the WhatsApp tables or leaking Meta credentials to products.

## Queues

Run:

```shell
php artisan queue:work --queue=whatsapp-webhooks,whatsapp-outbound,application-events,default --tries=5 --timeout=60
```

Use Redis in production and the database driver locally. Every external operation is queued, bounded, and idempotent.
