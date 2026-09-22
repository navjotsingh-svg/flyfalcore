<?php

namespace App\Providers;

use App\Models\Otp;
use App\Services\OtpService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        View::share('falcore', config('falcore'));
        View::composer('layouts.app', function ($view) {
            $view->with('pendingNewsletterOtp', app(OtpService::class)->pending(Otp::NEWSLETTER));
        });
    }
}
