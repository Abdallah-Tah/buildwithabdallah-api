<?php

declare(strict_types=1);

namespace App\Billing;

use App\Models\BillingCustomer;
use App\Models\ConnectedApplication;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class CreatePortalSession
{
    public function __construct(
        private StripeClient $stripe,
        private BillingRedirectPolicy $redirectPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(ConnectedApplication $application, array $data): array
    {
        $this->redirectPolicy->authorize($application, $data['return_url']);
        $customer = BillingCustomer::query()
            ->whereBelongsTo($application)
            ->where('external_customer_id', $data['external_customer_id'])
            ->first();

        if (! $customer) {
            throw (new ModelNotFoundException)->setModel(BillingCustomer::class);
        }

        return $this->stripe->createPortalSession([
            'customer' => $customer->stripe_customer_id,
            'return_url' => $data['return_url'],
        ], $application->slug.':portal:'.$data['idempotency_key']);
    }
}
