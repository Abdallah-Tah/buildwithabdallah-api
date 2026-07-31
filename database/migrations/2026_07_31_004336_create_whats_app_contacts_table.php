<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('wa_id_hash', 64)->unique();
            $table->text('wa_id_encrypted');
            $table->string('phone_number_hash', 64)->index();
            $table->text('phone_number_encrypted');
            $table->text('display_name_encrypted')->nullable();
            $table->string('locale')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_contacts');
    }
};
