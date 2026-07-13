<?php

namespace App\Application\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

interface NotificationRepositoryInterface
{
    public function create(
        User $recipient,
        string $type,
        array $data,
        ?User $sender = null,
        ?string $link = null,
        ?string $priority = 'normal',
        ?string $category = null
    ): DatabaseNotification;

    public function getForUser(
        User $user,
        bool $unreadOnly = false,
        ?string $filterCategory = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    public function getUnreadCount(User $user): int;

    public function markAsRead(string $id, User $user): bool;

    public function markAllAsRead(User $user): int;

    public function delete(string $id, User $user): bool;

    public function existsRecent(User $recipient, string $type, int $minutes = 5): bool;
}
