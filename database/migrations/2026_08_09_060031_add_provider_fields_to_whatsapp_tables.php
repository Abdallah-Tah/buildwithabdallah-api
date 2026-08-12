<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->string('provider')->nullable()->after('connected_application_id')->index();
            $table->string('provider_message_id')->nullable()->after('provider');
            $table->unique(['provider', 'provider_message_id'], 'wa_messages_provider_id_unique');
        });

        Schema::table('whatsapp_webhook_events', function (Blueprint $table) {
            $table->string('provider')->default('meta')->after('id')->index();
        });

        DB::table('whatsapp_messages')
            ->whereNotNull('meta_message_id')
            ->update([
                'provider' => 'meta',
                'provider_message_id' => DB::raw('meta_message_id'),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_webhook_events', function (Blueprint $table) {
            $table->dropColumn('provider');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropUnique('wa_messages_provider_id_unique');
            $table->dropIndex(['provider']);
            $table->dropColumn(['provider', 'provider_message_id']);
        });
    }
};
