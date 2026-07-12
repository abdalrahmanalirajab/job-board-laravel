<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_session_id')->nullable()->after('stripe_client_secret');
            $table->string('stripe_event_id')->nullable()->after('stripe_session_id');
            $table->json('metadata')->nullable()->after('stripe_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['stripe_session_id', 'stripe_event_id', 'metadata']);
        });
    }
};
