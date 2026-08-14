<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillingCustomer extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'connected_application_id',
        'external_customer_id',
        'stripe_customer_id',
        'email',
        'name',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function connectedApplication(): BelongsTo
    {
        return $this->belongsTo(ConnectedApplication::class);
    }
}
