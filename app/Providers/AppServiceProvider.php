<?php

namespace App\Providers;

use Carbon\Carbon;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use App\Support\Wa\PengirimGowa;
use App\Support\Wa\PengirimLog;
use App\Support\Wa\PengirimWa;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Jalur WhatsApp dipilih lewat WA_DRIVER; selain gowa, pesan hanya dicatat di log.
        $this->app->bind(PengirimWa::class, fn () => config('whatsapp.driver') === 'gowa'
            ? new PengirimGowa(config('whatsapp.gowa.url'), config('whatsapp.gowa.user'), config('whatsapp.gowa.password'), config('whatsapp.gowa.device'))
            : new PengirimLog());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        config(['app.locale' => 'id']);
        Carbon::setLocale('id');

        // Batas request JDIH publik per IP, hanya untuk pencarian kata kunci (query tanpa cache).
        // Halaman lain sudah ringan (cache / lookup ID); Limit::none() membuat throttle tidak
        // menulis penghitung sama sekali. Banjir request kasar ditangani di web server/CDN.
        RateLimiter::for('jdih', fn (Request $request) => filled($request->query('q'))
            ? Limit::perMinute(config('jdih.batas_halaman'))->by($request->ip())
            : Limit::none());
        RateLimiter::for('jdih-unduhan', fn (Request $request) => Limit::perMinute(config('jdih.batas_unduhan'))->by($request->ip()));

        $this->app->booted(function () {
            $schedule = app(Schedule::class);
            $schedule->command('notifications:clear')->daily();
            $schedule->command('backup:jalankan')->dailyAt('01:00')->withoutOverlapping(120);
            // Cadangan bila queue worker tidak berjalan, dan pengirim ulang setelah sambungan WhatsApp pulih.
            $schedule->command('wa:kirim')->everyMinute()->withoutOverlapping(10);
        });
    }
}
