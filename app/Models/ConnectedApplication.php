<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConnectedApplication extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['name', 'slug', 'webhook_url', 'request_signing_secret', 'event_signing_secret', 'enabled', 'allowed_ip_ranges', 'metadata', 'last_event_delivered_at'];

    protected $hidden = ['request_signing_secret', 'event_signing_secret'];

    protected function casts(): array
    {
        return [
            'request_signing_secret' => 'encrypted',
            'event_signing_secret' => 'encrypted',
            'enabled' => 'boolean',
            'allowed_ip_ranges' => 'array',
            'metadata' => 'array',
            'last_event_delivered_at' => 'datetime',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class);
    }
}
