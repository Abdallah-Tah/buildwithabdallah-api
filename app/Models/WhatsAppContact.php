<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppContact extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'whatsapp_contacts';

    protected $fillable = ['wa_id_hash', 'wa_id_encrypted', 'phone_number_hash', 'phone_number_encrypted', 'display_name_encrypted', 'locale', 'first_seen_at', 'last_seen_at', 'metadata'];

    protected $hidden = ['wa_id_encrypted', 'phone_number_encrypted', 'display_name_encrypted'];

    protected function casts(): array
    {
        return [
            'wa_id_encrypted' => 'encrypted',
            'phone_number_encrypted' => 'encrypted',
            'display_name_encrypted' => 'encrypted',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class);
    }
}
