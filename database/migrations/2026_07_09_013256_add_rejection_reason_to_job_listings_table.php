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
        if (!Schema::hasColumn('job_listings', 'rejection_reason')) {
            Schema::table('job_listings', function (Blueprint $table) {
                $table->text('rejection_reason')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
