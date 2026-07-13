<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('job_listings', 'benefits')) {
            Schema::table('job_listings', function ($table) {
                $table->text('benefits')->nullable()->after('responsibilities');
            });
        }
    }

    public function down(): void
    {
        Schema::table('job_listings', function ($table) {
            $table->dropColumn('benefits');
        });
    }
};
