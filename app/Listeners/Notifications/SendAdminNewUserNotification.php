<?php

namespace App\Listeners\Notifications;

use App\Domain\Events\UserRegistered;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendAdminNewUserNotification
{
    public function __construct(
        private readonly NotificationService $service,
    ) {}

    public function handle(UserRegistered $event): void
    {
        try {
            if ($event->role === 'admin') {
                return;
            }

            $admins = User::where('role', 'admin')->get();

            if ($admins->isEmpty()) {
                return;
            }

            $newUser = User::find($event->userId);

            if (!$newUser) {
                return;
            }

            foreach ($admins as $admin) {
                $this->service->notify($admin, new NewUserRegisteredNotification($newUser));
            }
        } catch (\Throwable $e) {
            Log::error('Failed to send admin new user notification', [
                'user_id' => $event->userId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
