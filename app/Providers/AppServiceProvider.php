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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return env('FRONTEND_URL', 'http://localhost:3000') . '/reset-password?token=' . $token . '&email=' . $notifiable->getEmailForPasswordReset();
        });
        Gate::policy(Application::class, ApplicationPolicy::class);
        Gate::policy(Comment::class, CommentPolicy::class);
    }
}
