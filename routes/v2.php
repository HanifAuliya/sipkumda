<?php

use App\Http\Controllers\V2\BerkasNaskahController;
use App\Http\Controllers\V2\CetakNaskahController;
use App\Livewire\V2\Beranda;
use App\Livewire\V2\BuatNaskah;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\PengaturanNaskah;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Aplikasi v2 (matriks sebagai sumber naskah)
|--------------------------------------------------------------------------
| Berjalan berdampingan dengan alur lama, memakai login dan data pengguna yang sama.
*/
Route::middleware(['auth', 'verified'])->prefix('v2')->name('v2.')->group(function () {
    Route::livewire('/', Beranda::class)->name('beranda');

    Route::livewire('/naskah/buat', BuatNaskah::class)
        ->middleware('role:Perangkat Daerah')
        ->name('naskah.buat');

    Route::livewire('/naskah/{naskah}', LayarKerja::class)->name('naskah.kerja');

    Route::get('/naskah/{naskah}/berkas/{berkas}', BerkasNaskahController::class)->name('naskah.berkas');

    // Membuat Word/PDF cukup berat, jadi dibatasi per pengguna.
    Route::get('/naskah/{naskah}/cetak/{berkas}', CetakNaskahController::class)
        ->whereIn('berkas', ['naskah-word', 'naskah-pdf', 'matriks-pdf'])
        ->middleware('throttle:30,1')
        ->name('naskah.cetak');

    Route::livewire('/pengaturan', PengaturanNaskah::class)
        ->middleware('role:Admin|Verifikator|Super Admin')
        ->name('pengaturan');
});
