<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('sender_id')->nullable()->after('notifiable_id')->constrained('users')->nullOnDelete();
            $table->string('priority', 20)->default('normal')->after('read_at');
            $table->string('link')->nullable()->after('priority');
            $table->string('icon')->nullable()->after('link');
            $table->json('metadata')->nullable()->after('icon');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn(['sender_id', 'priority', 'link', 'icon', 'metadata']);
        });
    }
};
