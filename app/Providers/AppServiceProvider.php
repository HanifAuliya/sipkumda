<?php

namespace App\Providers;

use Carbon\Carbon;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');

        // Batas request halaman JDIH publik per IP.
        RateLimiter::for('jdih', fn (Request $request) => Limit::perMinute(config('jdih.batas_halaman'))->by($request->ip()));
        RateLimiter::for('jdih-unduhan', fn (Request $request) => Limit::perMinute(config('jdih.batas_unduhan'))->by($request->ip()));

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('notifications:clear')->daily();
        });
    }
}
