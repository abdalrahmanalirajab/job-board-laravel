<?php

namespace App\Providers;

use App\Domain\Events\ApplicationAccepted;
use App\Domain\Events\ApplicationRejected;
use App\Domain\Events\ApplicationSubmitted;
use App\Domain\Events\ApplicationWithdrawn;
use App\Domain\Events\CommentCreated;
use App\Domain\Events\JobApproved;
use App\Domain\Events\JobDeleted;
use App\Domain\Events\JobPosted;
use App\Domain\Events\JobRejected;
use App\Domain\Events\PaymentCompleted;
use App\Domain\Events\PaymentFailed;
use App\Domain\Events\UserRegistered;
use App\Listeners\Notifications\SendAdminLargePaymentNotification;
use App\Listeners\Notifications\SendAdminNewJobNotification;
use App\Listeners\Notifications\SendAdminNewUserNotification;
use App\Listeners\Notifications\SendCandidateApplicationAcceptedNotification;
use App\Listeners\Notifications\SendCandidateApplicationConfirmationNotification;
use App\Listeners\Notifications\SendCandidateApplicationRejectedNotification;
use App\Listeners\Notifications\SendCandidatePaymentCompletedNotification;
use App\Listeners\Notifications\SendEmployerApplicationWithdrawnNotification;
use App\Listeners\Notifications\SendEmployerJobApprovedNotification;
use App\Listeners\Notifications\SendEmployerJobDeletedNotification;
use App\Listeners\Notifications\SendEmployerJobRejectedNotification;
use App\Listeners\Notifications\SendEmployerNewApplicationNotification;
use App\Listeners\Notifications\SendEmployerPaymentCompletedNotification;
use App\Listeners\Notifications\SendEmployerPaymentFailedNotification;
use App\Listeners\Notifications\SendJobCommentNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        UserRegistered::class => [
            SendAdminNewUserNotification::class,
        ],

        JobPosted::class => [
            SendAdminNewJobNotification::class,
        ],

        JobApproved::class => [
            SendEmployerJobApprovedNotification::class,
        ],

        JobRejected::class => [
            SendEmployerJobRejectedNotification::class,
        ],

        JobDeleted::class => [
            SendEmployerJobDeletedNotification::class,
        ],

        ApplicationSubmitted::class => [
            SendEmployerNewApplicationNotification::class,
            SendCandidateApplicationConfirmationNotification::class,
        ],

        ApplicationAccepted::class => [
            SendCandidateApplicationAcceptedNotification::class,
        ],

        ApplicationRejected::class => [
            SendCandidateApplicationRejectedNotification::class,
        ],

        ApplicationWithdrawn::class => [
            SendEmployerApplicationWithdrawnNotification::class,
        ],

        PaymentCompleted::class => [
            SendCandidatePaymentCompletedNotification::class,
            SendEmployerPaymentCompletedNotification::class,
            SendAdminLargePaymentNotification::class,
        ],

        PaymentFailed::class => [
            SendEmployerPaymentFailedNotification::class,
        ],

        CommentCreated::class => [
            SendJobCommentNotification::class,
        ],
    ];
}
