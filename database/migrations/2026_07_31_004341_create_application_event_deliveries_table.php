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
        Schema::create('application_event_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('event_id')->unique();
            $table->foreignUlid('connected_application_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('whatsapp_message_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type');
            $table->json('payload');
            $table->string('status')->default('pending')->index();
            $table->unsignedInteger('attempt_count')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_event_deliveries');
    }
};
