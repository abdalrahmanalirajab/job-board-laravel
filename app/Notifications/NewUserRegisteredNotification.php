<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    public function __construct(
        public readonly User $newUser,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'new_user_registered',
            'user_id' => $this->newUser->id,
            'message' => "New {$this->newUser->role} registered: {$this->newUser->name}",
            'link'    => '/admin/users',
            'icon'    => 'user-plus',
        ];
    }
}
