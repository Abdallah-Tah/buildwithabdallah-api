<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('is_admin')->default(false)->index();
            });
        }

        if (! Schema::hasColumn('users', 'app_authentication_secret')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->text('app_authentication_secret')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'app_authentication_recovery_codes')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->text('app_authentication_recovery_codes')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['is_admin', 'app_authentication_secret', 'app_authentication_recovery_codes'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }
};
