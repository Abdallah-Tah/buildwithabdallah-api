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
        Schema::create('application_request_nonces', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('connected_application_id')->constrained()->cascadeOnDelete();
            $table->string('request_id');
            $table->timestamp('timestamp');
            $table->string('body_hash', 64);
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(
                ['connected_application_id', 'request_id'],
                'app_nonces_application_request_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_request_nonces');
    }
};
