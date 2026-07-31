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
        Schema::create('whatsapp_webhook_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('payload_hash', 64)->unique();
            $table->string('object_type')->nullable();
            $table->string('event_type')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('received_at')->index();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamp('failed_at')->nullable()->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_webhook_events');
    }
};
