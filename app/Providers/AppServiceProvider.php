<?php

namespace App\Providers;

use App\Domain\Notification\EmailChannel;
use App\Domain\Notification\NotificationChannel;
use App\Domain\Notification\SmsChannel;
use App\Domain\Notification\UrgentAlertService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationChannel::class, EmailChannel::class); // default for everyone

        $this->app->when(UrgentAlertService::class)
            ->needs(NotificationChannel::class)
            ->give(SmsChannel::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
