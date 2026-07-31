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
        Schema::create('whatsapp_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('whatsapp_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('connected_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_slug')->nullable()->index();
            $table->string('state')->default('new')->index();
            $table->timestamp('customer_service_window_started_at')->nullable();
            $table->timestamp('customer_service_window_expires_at')->nullable()->index();
            $table->timestamp('last_incoming_message_at')->nullable();
            $table->timestamp('last_outgoing_message_at')->nullable();
            $table->timestamp('routed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_conversations');
    }
};
