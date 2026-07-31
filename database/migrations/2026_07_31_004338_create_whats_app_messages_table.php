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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('whatsapp_conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('whatsapp_contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('connected_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('meta_message_id')->nullable()->unique();
            $table->string('correlation_id')->nullable()->index();
            $table->string('idempotency_key')->nullable();
            $table->string('request_hash', 64)->nullable();
            $table->string('direction')->index();
            $table->string('message_type')->index();
            $table->string('status')->index();
            $table->text('text_body_encrypted')->nullable();
            $table->string('template_name')->nullable();
            $table->string('template_language')->nullable();
            $table->string('media_id')->nullable();
            $table->string('reply_to_meta_message_id')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['connected_application_id', 'idempotency_key'],
                'wa_messages_app_idempotency_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
