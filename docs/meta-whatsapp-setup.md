# Meta WhatsApp Setup

No Meta app, business account, or phone number should be created or replaced.

Set the callback URL to:

`https://api.buildwithabdallah.com/webhooks/meta/whatsapp`

1. Store a newly generated verification token in the production secret store as `META_WHATSAPP_VERIFY_TOKEN`.
2. In Meta App Dashboard, open WhatsApp > Configuration and edit the webhook callback.
3. Enter the callback URL and the same verification token.
4. Subscribe the WhatsApp Business Account to the `messages` field.
5. Store the app secret, access token, phone-number ID, business-account ID, and explicit Graph API version in the production secret store.
6. Never paste credentials into source, docs, tickets, screenshots, or logs.

GET verification accepts Meta’s dotted or PHP-normalized query names, requires `subscribe`, and returns only the exact challenge. POST requests require `X-Hub-Signature-256`, computed over the exact raw body with the Meta App Secret. Invalid requests receive 403 and are not persisted.

## Safe callback migration

1. Deploy this API without changing Meta.
2. Confirm `GET https://api.buildwithabdallah.com/up`.
3. Confirm `GET https://api.buildwithabdallah.com/health/ready`.
4. Test GET verification and a controlled signed POST.
5. Change Meta’s callback to the URL above and subscribe to `messages`.
6. Send a personal test message, confirm durable storage and the product menu/routing state.
7. Select Kirada and confirm a signed Kirada application event.
8. Keep Kirada’s old webhook route available during observation.
9. If validation fails, restore the old Kirada callback URL. Remove that rollback route only after stable operation.

Automatic replies are controlled by `WHATSAPP_AUTOREPLY_ENABLED`; live sends are independently protected by `WHATSAPP_LIVE_SEND_ENABLED`. Leave live sends disabled until the receive path, queues, and Kirada event verification are proven.
