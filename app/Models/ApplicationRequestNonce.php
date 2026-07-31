<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApplicationRequestNonce extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = ['connected_application_id', 'request_id', 'timestamp', 'body_hash', 'expires_at'];

    protected function casts(): array
    {
        return ['timestamp' => 'datetime', 'expires_at' => 'datetime'];
    }

    public function connectedApplication(): BelongsTo
    {
        return $this->belongsTo(ConnectedApplication::class);
    }
}
