<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendWhatsAppMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('connected_application');
    }

    public function rules(): array
    {
        return [
            'recipient' => ['required', 'string', 'regex:/^\+?[1-9]\d{6,14}$/'],
            'type' => ['required', Rule::in(['text', 'template'])],
            'body' => [Rule::requiredIf($this->input('type') === 'text'), 'nullable', 'string', 'max:4096'],
            'product' => ['required', 'string', 'max:100'],
            'template' => [Rule::requiredIf($this->input('type') === 'template'), 'nullable', 'array'],
            'template.name' => ['required_with:template', 'string', 'max:512'],
            'template.language' => ['required_with:template', 'string', 'max:35'],
            'template.components' => ['sometimes', 'array'],
            'correlation_id' => ['nullable', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:255'],
        ];
    }
}
