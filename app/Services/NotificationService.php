<?php

namespace App\Services;

use App\Application\Interfaces\NotificationRepositoryInterface;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\Notification;

class NotificationService
{
    private const DEDUPE_WINDOW_MINUTES = 5;

    public function __construct(
        private readonly NotificationRepositoryInterface $repository
    ) {}

    public function notify(User $recipient, Notification $notification, ?User $sender = null): void
    {
        $className = class_basename($notification);

        if ($this->repository->existsRecent($recipient, $className, self::DEDUPE_WINDOW_MINUTES)) {
            return;
        }

        $data    = $notification->toArray($recipient);
        $link    = $data['link'] ?? null;
        $priority = $data['priority'] ?? 'normal';
        $type    = $className;
        $category = $this->resolveCategory($className);

        $this->repository->create($recipient, $type, $data, $sender, $link, $priority, $category);

        if (method_exists($notification, 'toMail') && $this->shouldSendMail()) {
            try {
                $recipient->notify($notification);
            } catch (\Throwable) {
                // Mail channel failure must not break the flow
            }
        }
    }

    public function notifyMany(array $recipients, Notification $notification, ?User $sender = null): void
    {
        foreach ($recipients as $recipient) {
            if ($recipient instanceof User) {
                $this->notify($recipient, $notification, $sender);
            }
        }
    }

    public function notifyOnce(User $recipient, Notification $notification, ?User $sender = null, ?int $minutes = null): void
    {
        $className  = class_basename($notification);
        $window     = $minutes ?? self::DEDUPE_WINDOW_MINUTES;

        if ($this->repository->existsRecent($recipient, $className, $window)) {
            return;
        }

        $this->notify($recipient, $notification, $sender);
    }

    public function getForUser(User $user, bool $unreadOnly = false, ?string $filterCategory = null, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getForUser($user, $unreadOnly, $filterCategory, $perPage);
    }

    public function getUnreadCount(User $user): int
    {
        return $this->repository->getUnreadCount($user);
    }

    public function markAsRead(string $id, User $user): bool
    {
        return $this->repository->markAsRead($id, $user);
    }

    public function markAllAsRead(User $user): int
    {
        return $this->repository->markAllAsRead($user);
    }

    public function delete(string $id, User $user): bool
    {
        return $this->repository->delete($id, $user);
    }

    private function shouldSendMail(): bool
    {
        $driver = config('mail.default', config('mail.mailer'));
        return in_array($driver, ['smtp', 'sendmail', 'mailgun', 'ses', 'postmark']);
    }

    private function resolveCategory(string $className): string
    {
        $lower = strtolower($className);

        if (str_contains($lower, 'application')) return 'application';
        if (str_contains($lower, 'job')) return 'job';
        if (str_contains($lower, 'payment')) return 'payment';
        if (str_contains($lower, 'comment')) return 'comment';
        if (str_contains($lower, 'user')) return 'user';

        return 'other';
    }
}
