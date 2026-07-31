# Security

- The previously exposed temporary Meta token is considered compromised and must be revoked/rotated in Meta before production use.
- Credentials exist only in the runtime secret store and `config/services.php`; placeholders are empty.
- Meta POST signatures and BWA HMAC signatures use the exact raw body and `hash_equals`.
- BWA signatures bind method, path, timestamp, request ID, and body hash.
- Connected applications must be enabled, fresh within 300 seconds by default, rate-limited, and protected by a unique persisted request ID.
- Application signing secrets, WhatsApp IDs, phone numbers, display names, and message text are encrypted using Laravel’s authenticated encryption.
- Deterministic SHA-256 hashes provide contact lookup without plaintext indexes.
- Responses and structured logs omit secrets, authorization headers, phone numbers, raw payloads, and customer text.
- Raw webhook payloads are redacted after the configured retention period while normalized audit data remains.
- Outbound Meta traffic is disabled unless `WHATSAPP_LIVE_SEND_ENABLED=true`.
- Automated tests fake HTTP and never require external services.

Restrict production database, cache, queue, and application keys through least-privilege access. Rotate application secrets with the console command, update the counterpart atomically, and verify signed traffic before retiring the old deployment. Laravel application-key rotation requires preserving prior keys until all encrypted records have been re-encrypted.
