<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RouteWhatsAppConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->attributes->has('connected_application');
    }

    public function rules(): array
    {
        return ['product' => ['required', 'string', 'in:kirada,djib-payroll,smkit,custom-software,general-support']];
    }
}
