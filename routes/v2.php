<?php

use App\Http\Controllers\V2\BerkasNaskahController;
use App\Http\Controllers\V2\CetakNaskahController;
use App\Livewire\V2\Beranda;
use App\Http\Middleware\TandaiAplikasiV2;
use App\Livewire\V2\DaftarNaskah;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\PengaturanNaskah;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Aplikasi v2 (matriks sebagai sumber naskah)
|--------------------------------------------------------------------------
| Berjalan berdampingan dengan alur lama, memakai login dan data pengguna yang sama.
*/
Route::middleware(['auth', 'verified', TandaiAplikasiV2::class])->prefix('v2')->name('v2.')->group(function () {
    Route::livewire('/', Beranda::class)->name('beranda');

    Route::livewire('/daftar/{kelompok}', DaftarNaskah::class)->name('daftar');

    // Naskah bisa saja sudah dibuang, misalnya saat pengguna menekan Back atau membuka tautan lama.
    Route::livewire('/naskah/{naskah}', LayarKerja::class)
        ->missing(fn () => redirect()->route('v2.beranda')->with('pesan', 'Naskah yang Anda buka sudah dibuang atau tidak ditemukan.'))
        ->name('naskah.kerja');

    Route::get('/naskah/{naskah}/berkas/{berkas}', BerkasNaskahController::class)->name('naskah.berkas');

    // Membuat Word/PDF cukup berat, jadi dibatasi per pengguna.
    Route::get('/naskah/{naskah}/cetak/{berkas}', CetakNaskahController::class)
        ->whereIn('berkas', ['naskah-word', 'naskah-pdf', 'matriks-pdf', 'nota-dinas'])
        ->middleware('throttle:30,1')
        ->name('naskah.cetak');

    Route::get('/template/{jenis}', [CetakNaskahController::class, 'template'])
        ->whereIn('jenis', ['peraturan-bupati', 'keputusan-bupati'])
        ->middleware('throttle:30,1')
        ->name('template');

    Route::livewire('/pengaturan', PengaturanNaskah::class)
        ->middleware('role:Admin|Verifikator|Super Admin')
        ->name('pengaturan');
});
