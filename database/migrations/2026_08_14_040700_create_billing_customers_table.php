<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_customers', function (Blueprint $table): void {
            $table->ulid('id')->primary();
            $table->foreignUlid('connected_application_id')->constrained()->cascadeOnDelete();
            $table->string('external_customer_id');
            $table->string('stripe_customer_id')->unique();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(
                ['connected_application_id', 'external_customer_id'],
                'billing_customers_app_external_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_customers');
    }
};
