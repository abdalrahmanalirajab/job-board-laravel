<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Application;
use App\Policies\ApplicationPolicy;
use App\Models\Comment;
use App\Policies\CommentPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Application\Interfaces\PaymentRepositoryInterface::class,
            \App\Infrastructure\Persistence\EloquentPaymentRepository::class,
        );

        $this->app->bind(
            \App\Application\Interfaces\PaymentGatewayInterface::class,
            \App\Infrastructure\Gateway\StripePaymentGateway::class,
        );

        $this->app->bind(
            \App\Application\Interfaces\PaymentEventDispatcherInterface::class,
            \App\Infrastructure\Event\LaravelPaymentEventDispatcher::class,
        );

        $this->app->bind(
            \App\Application\Interfaces\PaymentApplicationInterface::class,
            \App\Infrastructure\Persistence\EloquentPaymentApplicationService::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Auth\Notifications\ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $url = env('FRONTEND_URL', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . $notifiable->getEmailForPasswordReset();

            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject('Reset Your Password')
                ->greeting('Hello!')
                ->line('You are receiving this email because we received a password reset request for your account.')
                ->action('Reset Password', $url)
                ->line('If you did not request a password reset, no further action is required.')
                ->line('Thank you for using our application!');
        });
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
    }
}
