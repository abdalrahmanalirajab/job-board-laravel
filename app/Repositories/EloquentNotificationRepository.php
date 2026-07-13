<?php

namespace App\Repositories;

use App\Application\Interfaces\NotificationRepositoryInterface;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function create(
        User $recipient,
        string $type,
        array $data,
        ?User $sender = null,
        ?string $link = null,
        ?string $priority = 'normal',
        ?string $category = null
    ): DatabaseNotification {
        return $recipient->notifications()->create([
            'id'         => (string) Str::uuid(),
            'type'       => $type,
            'data'       => $data,
            'sender_id'  => $sender?->id,
            'priority'   => $priority,
            'link'       => $link,
            'icon'       => $data['icon'] ?? null,
            'metadata'   => $data['metadata'] ?? null,
            'category'   => $category,
        ]);
    }

    public function getForUser(
        User $user,
        bool $unreadOnly = false,
        ?string $filterCategory = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = $user->notifications();

        if ($unreadOnly) {
            $query->whereNull('read_at');
        }

        if ($filterCategory) {
            $query->where('category', $filterCategory);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(string $id, User $user): bool
    {
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return false;
        }

        if ($notification->read_at !== null) {
            return true;
        }

        $notification->markAsRead();
        return true;
    }

    public function markAllAsRead(User $user): int
    {
        $count = $user->unreadNotifications()->count();
        $user->unreadNotifications->markAsRead();
        return $count;
    }

    public function delete(string $id, User $user): bool
    {
        $notification = $user->notifications()->find($id);

        if (!$notification) {
            return false;
        }

        $notification->delete();
        return true;
    }

    public function existsRecent(User $recipient, string $type, int $minutes = 5): bool
    {
        return $recipient->notifications()
            ->where('type', $type)
            ->where('created_at', '>=', Carbon::now()->subMinutes($minutes))
            ->exists();
    }
}
