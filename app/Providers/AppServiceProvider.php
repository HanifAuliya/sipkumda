<?php

namespace App\Providers;

use Carbon\Carbon;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

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
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('notifications:clear')->daily();
        });
    }
}
