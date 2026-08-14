<?php

declare(strict_types=1);

namespace App\Billing;

use App\Models\BillingCustomer;
use App\Models\ConnectedApplication;
use Illuminate\Support\Arr;

class CreateCheckoutSession
{
    public function __construct(
        private StripeClient $stripe,
        private BillingRedirectPolicy $redirectPolicy,
    ) {}

    /** @param array<string, mixed> $data */
    public function handle(ConnectedApplication $application, array $data): array
    {
        $this->redirectPolicy->authorize($application, $data['success_url'], $data['cancel_url']);
        $customer = $this->customer($application, $data);
        $plan = $data['plan'];

        return $this->stripe->createCheckoutSession([
            'mode' => 'subscription',
            'customer' => $customer->stripe_customer_id,
            'client_reference_id' => $application->slug.':'.$data['external_customer_id'],
            'success_url' => $data['success_url'],
            'cancel_url' => $data['cancel_url'],
            'allow_promotion_codes' => 'true',
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => mb_strtolower($plan['currency']),
                    'unit_amount' => $plan['amount'],
                    'recurring' => ['interval' => $plan['interval']],
                    'product_data' => [
                        'name' => $plan['name'],
                        'metadata' => [
                            'bwa_app' => $application->slug,
                            'bwa_external_plan_id' => $plan['id'],
                        ],
                    ],
                ],
            ]],
            'metadata' => $this->metadata($application, $data),
            'subscription_data' => ['metadata' => $this->metadata($application, $data)],
        ], $application->slug.':checkout:'.$data['idempotency_key']);
    }

    /** @param array<string, mixed> $data */
    private function customer(ConnectedApplication $application, array $data): BillingCustomer
    {
        $customer = BillingCustomer::query()
            ->whereBelongsTo($application)
            ->where('external_customer_id', $data['external_customer_id'])
            ->first();
        $details = Arr::only($data['customer'], ['email', 'name']);

        if ($customer) {
            if ($customer->email !== $details['email'] || $customer->name !== $details['name']) {
                $this->stripe->updateCustomer($customer->stripe_customer_id, $details);
                $customer->update($details);
            }

            return $customer;
        }

        $stripeCustomer = $this->stripe->createCustomer([
            ...$details,
            'metadata' => [
                'bwa_app' => $application->slug,
                'bwa_external_customer_id' => $data['external_customer_id'],
            ],
        ], $application->slug.':customer:'.$data['external_customer_id']);

        return BillingCustomer::create([
            'connected_application_id' => $application->id,
            'external_customer_id' => $data['external_customer_id'],
            'stripe_customer_id' => $stripeCustomer['id'],
            ...$details,
        ]);
    }

    /** @param array<string, mixed> $data */
    private function metadata(ConnectedApplication $application, array $data): array
    {
        return [
            'bwa_app' => $application->slug,
            'bwa_external_customer_id' => $data['external_customer_id'],
            'bwa_external_plan_id' => $data['plan']['id'],
        ];
    }
}
