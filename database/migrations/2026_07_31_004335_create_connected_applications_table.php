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
        Schema::create('connected_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('webhook_url')->nullable();
            $table->text('request_signing_secret');
            $table->text('event_signing_secret');
            $table->boolean('enabled')->default(true)->index();
            $table->json('allowed_ip_ranges')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('last_event_delivered_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('connected_applications');
    }
};
