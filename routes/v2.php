<?php

use App\Http\Controllers\V2\BerkasNaskahController;
use App\Http\Controllers\V2\CetakNaskahController;
use App\Livewire\V2\Beranda;
use App\Livewire\V2\DaftarNaskah;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\Notifikasi;
use App\Livewire\V2\PantauNaskah;
use App\Livewire\V2\PengaturanFormat;
use App\Livewire\V2\PengaturanNaskah;
use App\Livewire\V2\PengaturanPerangkatDaerah;
use App\Support\FormatDokumen;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Alur naskah (matriks sebagai sumber naskah)
|--------------------------------------------------------------------------
| Awalan /v2 dan nama rute v2.* dipertahankan supaya tautan dan notifikasi yang sudah beredar tetap berlaku.
*/
Route::middleware(['auth', 'verified'])->prefix('v2')->name('v2.')->group(function () {
    Route::livewire('/', Beranda::class)->name('beranda');

    Route::livewire('/daftar/{kelompok}', DaftarNaskah::class)->name('daftar');

    Route::livewire('/notifikasi', Notifikasi::class)->name('notifikasi');

    // Naskah bisa saja sudah dibuang, misalnya saat pengguna menekan Back atau membuka tautan lama.
    Route::livewire('/naskah/{naskah}', LayarKerja::class)
        ->missing(fn () => redirect()->route('v2.beranda')->with('pesan', 'Naskah yang Anda buka sudah dibuang atau tidak ditemukan.'))
        ->name('naskah.kerja');

    Route::livewire('/naskah/{naskah}/pantau', PantauNaskah::class)
        ->missing(fn () => redirect()->route('v2.beranda')->with('pesan', 'Naskah yang Anda buka sudah dibuang atau tidak ditemukan.'))
        ->name('naskah.pantau');

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

    Route::middleware('role:Admin|Verifikator|Super Admin')->group(function () {
        Route::livewire('/pengaturan/format', PengaturanFormat::class)->name('format');

        // Lambang kop naskah atau logo kop nota yang sedang dipakai, untuk pratinjau di menu Format Dokumen.
        Route::get('/pengaturan/format/gambar/{bagian}', function (string $bagian) {
            $format = $bagian === 'nota' ? FormatDokumen::nota() : FormatDokumen::naskah($bagian === 'kepbup' ? FormatDokumen::KEPBUP : FormatDokumen::PERBUP);
            $path = $bagian === 'nota' ? FormatDokumen::pathLogoNota($format) : FormatDokumen::pathLambang($format);
            abort_unless($path, 404);

            return response()->file($path, ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, max-age=31536000, immutable']);
        })->whereIn('bagian', ['kepbup', 'perbup', 'nota'])->name('format.gambar');

        // PDF pratinjau milik pengguna yang sedang membuka menu Format Dokumen, ditampilkan di samping formulir.
        Route::get('/pengaturan/format/pratinjau/{bagian}', function (string $bagian) {
            $path = PengaturanFormat::pathPratinjau(auth()->id(), $bagian);
            abort_unless(Storage::disk('local')->exists($path), 404);

            return response(Storage::disk('local')->get($path), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="pratinjau.pdf"',
                'Cache-Control' => 'private, no-store',
                'X-Content-Type-Options' => 'nosniff',
            ]);
        })->whereIn('bagian', ['kepbup', 'perbup', 'nota'])->middleware('throttle:120,1')->name('format.pratinjau');
    });

    // Daftar Perangkat Daerah adalah data induk (dipakai akun pengguna dan dokumentasi), jadi hanya Admin.
    Route::livewire('/pengaturan/perangkat-daerah', PengaturanPerangkatDaerah::class)
        ->middleware('role:Admin|Super Admin')
        ->name('perangkat-daerah');

    Route::livewire('/pengaturan', PengaturanNaskah::class)
        ->middleware('role:Admin|Verifikator|Super Admin')
        ->name('pengaturan');
});
