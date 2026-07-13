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
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('category')->nullable()->index()->after('type');
        });

        // Backfill existing notifications
        $typeToCategory = [
            'App\\Notifications\\ApplicationAccepted' => 'application',
            'App\\Notifications\\ApplicationRejected' => 'application',
            'App\\Notifications\\ApplicationConfirmationNotification' => 'application',
            'App\\Notifications\\ApplicationWithdrawnNotification' => 'application',
            'App\\Notifications\\NewApplicationNotification' => 'application',
            'App\\Notifications\\JobApproved' => 'job',
            'App\\Notifications\\JobRejected' => 'job',
            'App\\Notifications\\NewJobPostedNotification' => 'job',
            'App\\Notifications\\JobDeletedNotification' => 'job',
            'App\\Notifications\\PaymentCompletedNotification' => 'payment',
            'App\\Notifications\\PaymentFailedNotification' => 'payment',
            'App\\Notifications\\LargePaymentCompletedNotification' => 'payment',
            'App\\Notifications\\CommentCreatedNotification' => 'comment',
            'App\\Notifications\\NewUserRegisteredNotification' => 'user',
        ];

        foreach ($typeToCategory as $type => $category) {
            DB::table('notifications')
                ->where('type', $type)
                ->update(['category' => $category]);
        }
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
