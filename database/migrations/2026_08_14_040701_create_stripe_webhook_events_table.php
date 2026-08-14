<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stripe_webhook_events', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->string('stripe_event_id')->unique();
            $table->string('type')->index();
            $table->boolean('livemode')->default(false);
            $table->longText('payload');
            $table->string('status')->default('received')->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stripe_webhook_events');
    }
};
