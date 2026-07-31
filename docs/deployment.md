# Production Deployment

Target: `https://api.buildwithabdallah.com`

The existing website server uses MySQL with database-backed queues and cache. Deploy this messaging API as a separate site and database; do not merge its messaging tables into the website content database. Database queues are compatible for initial deployment, while Redis is recommended as traffic grows.

1. Point DNS for `api.buildwithabdallah.com` to the production load balancer or web server.
2. Configure HTTPS and redirect HTTP to HTTPS.
3. Set the web document root to this repository’s `public` directory.
4. Install production Composer dependencies and build assets if the default welcome page is retained.
5. Inject all `.env.example` values from the production secret/config store. Use Redis for queue/cache when available; keep live sends false initially.
6. Run `php artisan migrate --force`.
7. Run `php artisan config:cache` and `php artisan route:cache`.
8. Give the web/worker user write access only to `storage` and `bootstrap/cache`.
9. Start workers:

   ```shell
   php artisan queue:work --queue=whatsapp-webhooks,whatsapp-outbound,application-events,default --tries=5 --timeout=60
   ```

10. Run `php artisan schedule:run` every minute. It prunes old webhook payloads daily.
11. Monitor `/up`, `/health/ready`, failed jobs, sanitized logs, queue depth, and event delivery failures.
12. Create Kirada with `bwa:application:create`, safely exchange its two secrets, and test its receiver.
13. Follow the callback transition in `docs/meta-whatsapp-setup.md`.
14. Enable automatic replies only after inbound validation. Enable live sending only after explicit approval and a confirmed manual `bwa:whatsapp:test-send`.

## Rollback

Stop new workers, restore the prior release, run only safe backward-compatible migration rollback where reviewed, clear cached configuration, restart workers, and restore Meta’s old Kirada callback if inbound processing is affected. Do not delete new normalized data during rollback. Keep the old Kirada webhook endpoint until the new path is stable.

## Required environment values

Set `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, database/cache/queue configuration, all `META_WHATSAPP_*` values, both send flags, retention/window settings, and internal signature/event timeouts. Never run `config:show services` in shared logs when real secrets are loaded.
