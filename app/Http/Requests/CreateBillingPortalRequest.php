<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateBillingPortalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('connected_application');
    }

    public function rules(): array
    {
        return [
            'external_customer_id' => ['required', 'string', 'max:255'],
            'return_url' => ['required', 'url:https', 'max:2048'],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }
}
