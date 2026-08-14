<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBillingCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('connected_application');
    }

    public function rules(): array
    {
        return [
            'external_customer_id' => ['required', 'string', 'max:255'],
            'customer.email' => ['required', 'email', 'max:255'],
            'customer.name' => ['required', 'string', 'max:255'],
            'plan.id' => ['required', 'string', 'max:255'],
            'plan.name' => ['required', 'string', 'max:255'],
            'plan.amount' => ['required', 'integer', 'min:50', 'max:99999999'],
            'plan.currency' => ['required', 'string', 'size:3', 'alpha'],
            'plan.interval' => ['required', Rule::in(['month', 'year'])],
            'success_url' => ['required', 'url:https', 'max:2048'],
            'cancel_url' => ['required', 'url:https', 'max:2048'],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }
}
