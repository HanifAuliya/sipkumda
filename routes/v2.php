<?php

use App\Http\Controllers\V2\BerkasNaskahController;
use App\Livewire\V2\Beranda;
use App\Livewire\V2\BuatNaskah;
use App\Livewire\V2\LayarKerja;
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
});
