<?php

namespace App\Providers;

use Carbon\Carbon;

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
        Carbon::setLocale('id'); // Set lokal ke bahasa Indonesia
        setlocale(LC_TIME, 'id_ID.utf8'); // Set lokal sistem
    }
}
