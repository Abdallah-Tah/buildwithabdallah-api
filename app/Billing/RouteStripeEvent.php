<?php

declare(strict_types=1);

namespace App\Billing;

use App\Jobs\DispatchApplicationEvent;
use App\Models\ApplicationEventDelivery;
use App\Models\BillingCustomer;
use App\Models\ConnectedApplication;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RouteStripeEvent
{
    /** @param array<string, mixed> $event */
    public function handle(array $event): ?ApplicationEventDelivery
    {
        $object = Arr::get($event, 'data.object', []);
        if (! is_array($object)) {
            return null;
        }

        $customerId = $object['customer'] ?? null;
        $customer = is_string($customerId)
            ? BillingCustomer::query()->where('stripe_customer_id', $customerId)->first()
            : null;
        $application = $customer?->connectedApplication
            ?? $this->applicationFromMetadata((array) ($object['metadata'] ?? []));

        if (! $application) {
            return null;
        }

        $eventId = (string) Str::ulid();
        $eventType = 'billing.stripe.'.(string) $event['type'];
        $delivery = ApplicationEventDelivery::create([
            'event_id' => $eventId,
            'connected_application_id' => $application->id,
            'event_type' => $eventType,
            'payload' => [
                'id' => $eventId,
                'type' => $eventType,
                'occurred_at' => now()->setTimestamp((int) ($event['created'] ?? now()->timestamp))->toIso8601String(),
                'data' => $this->normalizedData($event, $object, $customer),
            ],
        ]);

        DispatchApplicationEvent::dispatch($delivery)->onQueue('application-events');

        return $delivery;
    }

    /** @param array<string, mixed> $metadata */
    private function applicationFromMetadata(array $metadata): ?ConnectedApplication
    {
        $slug = $metadata['bwa_app'] ?? null;

        return is_string($slug)
            ? ConnectedApplication::query()->where('slug', $slug)->first()
            : null;
    }

    /** @param array<string, mixed> $event @param array<string, mixed> $object */
    private function normalizedData(array $event, array $object, ?BillingCustomer $customer): array
    {
        $metadata = (array) ($object['metadata'] ?? []);

        $stripeSubscriptionId = $object['subscription'] ?? null;
        if (! is_string($stripeSubscriptionId) && ($object['object'] ?? null) === 'subscription') {
            $stripeSubscriptionId = $object['id'] ?? null;
        }

        return [
            'stripe_event_id' => $event['id'],
            'stripe_event_type' => $event['type'],
            'stripe_object_id' => $object['id'] ?? null,
            'stripe_customer_id' => $object['customer'] ?? $customer?->stripe_customer_id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'external_customer_id' => $customer?->external_customer_id ?? ($metadata['bwa_external_customer_id'] ?? null),
            'external_plan_id' => $metadata['bwa_external_plan_id'] ?? null,
            'status' => $object['status'] ?? null,
            'payment_status' => $object['payment_status'] ?? null,
            'currency' => $object['currency'] ?? null,
            'amount_due' => $object['amount_due'] ?? null,
            'amount_paid' => $object['amount_paid'] ?? null,
            'current_period_end' => $object['current_period_end'] ?? null,
            'cancel_at_period_end' => $object['cancel_at_period_end'] ?? null,
            'hosted_invoice_url' => $object['hosted_invoice_url'] ?? null,
            'invoice_pdf' => $object['invoice_pdf'] ?? null,
        ];
    }
}
