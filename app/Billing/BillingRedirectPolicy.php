<?php

declare(strict_types=1);

namespace App\Billing;

use App\Models\ConnectedApplication;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class BillingRedirectPolicy
{
    public function authorize(ConnectedApplication $application, string ...$urls): void
    {
        $webhookHost = parse_url((string) $application->webhook_url, PHP_URL_HOST);
        $configuredHosts = Arr::wrap(Arr::get($application->metadata, 'billing_allowed_redirect_hosts', []));
        $allowedHosts = array_values(array_filter(array_unique([$webhookHost, ...$configuredHosts])));

        foreach ($urls as $url) {
            if (! in_array(parse_url($url, PHP_URL_HOST), $allowedHosts, true)) {
                throw ValidationException::withMessages([
                    'redirect_url' => 'The redirect URL is not authorized for this application.',
                ]);
            }
        }
    }
}
