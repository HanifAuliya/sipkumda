<?php

use App\Http\Controllers\FotoProfilController;
use App\Http\Controllers\JdihController;
use App\Http\Controllers\PrivateFileController;
use App\Http\Controllers\TandaTanganSayaController;
use App\Livewire\Dokumentasi\Dokumentasi;
use App\Livewire\ProfileManagement;
use App\Livewire\Rancangan\TambahBahanPenting;
use App\Livewire\UserManagement;
use App\Livewire\Verifikator\TandaTangan\PengelolaTtd;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// Halaman publik (JDIH): tanpa sesi & cookie agar ringan untuk banyak pengunjung
// dan bisa di-cache browser/proxy. Jangan memakai fitur yang butuh sesi di sini.
Route::withoutMiddleware([
    \Illuminate\Cookie\Middleware\EncryptCookies::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class,
])->middleware('throttle:jdih')->group(function () {
    Route::get('/', [JdihController::class, 'beranda'])->name('beranda');
    Route::get('/produk-hukum', [JdihController::class, 'index'])->name('jdih.index');
    Route::get('/produk-hukum/{dokumentasi}', [JdihController::class, 'show'])
        ->whereNumber('dokumentasi')->name('jdih.show');
    Route::get('/produk-hukum/{dokumentasi}/dokumen', [JdihController::class, 'dokumen'])
        ->whereNumber('dokumentasi')->middleware('throttle:jdih-unduhan')->name('jdih.dokumen');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/foto-profil', FotoProfilController::class)->name('foto-profil');
    Route::get('/tanda-tangan-saya', TandaTanganSayaController::class)->name('tanda-tangan-saya');
});

Route::middleware('auth')->group(function () {
    // Profil (semua peran). Halaman bebas dibuka; kata sandi hanya diminta untuk tindakan berisiko:
    // ganti kata sandi, ganti email, dan ganti gambar tanda tangan.
    Route::livewire('/profile', ProfileManagement::class)->name('profile.edit');

    // Panduan pengguna per peran (isi dari docs/panduan/*.md).
    Route::livewire('/panduan/{peran?}', \App\Livewire\Panduan::class)->name('panduan');

    // Tur pengenalan di Dashboard selesai atau dilewati: tidak muncul otomatis lagi.
    Route::post('/tur-pengenalan/selesai', function () {
        request()->user()->forceFill(['tur_selesai_at' => now()])->save();

        return response()->noContent();
    })->middleware('throttle:30,1')->name('tur.selesai');

    Route::middleware(['role:Admin|Verifikator|Super Admin'])->group(function () {
        Route::livewire('/user-management', UserManagement::class)->name('user.management');
    });

    // Berkas privat (produk hukum dan gambar tanda tangan).
    Route::get('/view-private/{folder}/{subfolder}/{filename}', PrivateFileController::class)
        ->where(['folder' => '[a-z_]+', 'subfolder' => '[a-z_]+', 'filename' => '[^/\\\\]+'])
        ->name('private-file');

    Route::middleware(['role:Admin'])->group(function () {
        Route::livewire('/kelola-bahan-penting', TambahBahanPenting::class)->name('bahan-penting');

        // Pemilik tanda tangan mengaturnya sendiri di Profil; Admin membantu di sini.
        Route::livewire('/kelola-ttd', PengelolaTtd::class)->name('kelola-ttd.main');
        Route::get('/kelola-ttd/peneliti/{user}', function (\App\Models\User $user) {
            abort_unless($user->hasRole('Peneliti') && $user->file_ttd && Storage::disk('local')->exists($user->file_ttd), 404);

            return Storage::disk('local')->response($user->file_ttd, 'tanda-tangan.png', [
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=600',
            ]);
        })->whereNumber('user')->name('kelola-ttd.peneliti');
    });

    Route::middleware(['role:Admin|Verifikator|Perangkat Daerah|Peneliti'])->group(function () {
        Route::livewire('/dokumentasi-produk-hukum', Dokumentasi::class)->name('dokumentasi.main');
    });
});

require __DIR__ . '/auth.php';
require __DIR__ . '/v2.php';
