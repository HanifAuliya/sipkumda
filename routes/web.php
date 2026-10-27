<?php

use App\Livewire\Dashboard;
use App\Livewire\UserManagement;
use App\Livewire\ProfileManagement;

use Illuminate\Support\Facades\Route;

use App\Livewire\Dokumentasi\MasterData;
use App\Livewire\Dokumentasi\Dokumentasi;
use App\Livewire\Rancangan\DaftarRancangan;
use App\Livewire\Rancangan\TambahBahanPenting;
use App\Livewire\Admin\NotaDinas\KelolaNotaDinas;

use App\Livewire\Admin\Rancangan\PersetujuanMain;
use App\Http\Controllers\DokumentasiPdfController;
use App\Http\Controllers\PrivateFileController;
use App\Livewire\Peneliti\Rancangan\RevisiRancangan;

use App\Livewire\Verifikator\Rancangan\ValidasiMain;
use App\Http\Controllers\DokumentasiExportController;
use App\Livewire\Verifikator\Rancangan\PilihPeneliti;
use App\Livewire\Admin\Fasilitasi\ManajemenFasilitasi;
use App\Livewire\Verifikator\TandaTangan\PengelolaTtd;
use App\Livewire\PerangkatDaerah\Rancangan\Rancanganku;

use App\Livewire\FasilitasiProdukHukum\DaftarFasilitasi;
use App\Livewire\PerangkatDaerah\Fasilitasi\Fasilitasiku;
use App\Livewire\Verifikator\Fasilitasi\ValidasiFasilitasi;
use App\Livewire\Peneliti\Fasilitasi\PersetujuanFasilitasiMain;
use App\Models\RancanganProdukHukum;
use App\Models\DokumentasiProdukHukum;


Route::get('/', function () {
    // "Keputusan Bupati" (input manual dokumentasi) sama dengan "Surat Keputusan".
    $suratKeputusan = ['Surat Keputusan', 'Keputusan Bupati'];

    return view('welcome', [
        'sk_rancangan' => RancanganProdukHukum::whereIn('jenis_rancangan', $suratKeputusan)->count(),
        'pb_rancangan' => RancanganProdukHukum::where('jenis_rancangan', 'Peraturan Bupati')->count(),
        'sk_dokumentasi' => DokumentasiProdukHukum::whereIn('jenis_dokumentasi', $suratKeputusan)->count(),
        'pb_dokumentasi' => DokumentasiProdukHukum::where('jenis_dokumentasi', 'Peraturan Bupati')->count(),
    ]);
});

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', Dashboard::class)
        ->middleware(['verified'])
        ->name('dashboard');

    // Daftar Rancangan (Semua Role terkait )
    Route::livewire('/daftar-rancangan', DaftarRancangan::class)
        ->middleware('role:Admin|Verifikator|Perangkat Daerah|Peneliti|Super Admin')
        ->name('daftar-rancangan');

    Route::livewire('/daftar-fasilitasi', DaftarFasilitasi::class)
        ->middleware('role:Admin|Verifikator|Perangkat Daerah|Peneliti|Super Admin')
        ->name('daftar-fasilitasi');


    // Profile (Semua Role termasuk Super Admin)
    Route::middleware(['password.confirm'])->group(function () {
        Route::livewire('/profile', ProfileManagement::class)->name('profile.edit');
    });

    // User Management (Admin, Verifikator, dan Super Admin)
    Route::middleware(['role:Admin|Verifikator|Super Admin'])->group(function () {
        Route::livewire('/user-management', UserManagement::class)->name('user.management');
    });

    // Rancanganku (Perangkat Daerah)
    Route::middleware(['role:Perangkat Daerah'])->group(function () {
        Route::livewire('/rancanganku', Rancanganku::class)
            ->name('rancanganku');
    });
    // Admin-Only Routes 
    Route::middleware(['role:Admin'])->group(function () {
        Route::livewire('/rancangan/persetujuan', PersetujuanMain::class)->name('admin.persetujuan');
    });

    // Verifikator-Only Routes 
    Route::middleware(['role:Verifikator'])->group(function () {
        Route::livewire('/rancangan/pilih-peneliti', PilihPeneliti::class)->name('verifikator.pilih-peneliti');
        Route::livewire('/validasi-rancangan', ValidasiMain::class)->name('verifikator.validasi-rancangan');
    });

    Route::middleware(['role:Peneliti'])->group(function () {
        // Revisi Rancangan 
        Route::livewire('/revisi/rancangan', RevisiRancangan::class)
            ->name('revisi.rancangan');
    });
    // View Private Files
    Route::get('/view-private/{folder}/{subfolder}/{filename}', PrivateFileController::class)
        ->where(['folder' => '[a-z_]+', 'subfolder' => '[a-z_]+', 'filename' => '[^/\\\\]+'])
        ->name('private-file');

    // Fasilitasi
    Route::middleware(['role:Verifikator'])->group(function () {
        Route::livewire('/validasi/fasilitasi', ValidasiFasilitasi::class)
            ->name('validasi-fasilitasi');
        Route::livewire('/kelola-ttd', PengelolaTtd::class)
            ->name('kelola-ttd.main');
    });

    Route::middleware(['role:Perangkat Daerah'])->group(function () {
        // Menu Fasilitasiku
        Route::livewire('/fasilitasiku', Fasilitasiku::class)
            ->name('fasilitasiku.main');
    });

    Route::middleware(['role:Admin|Super Admin|Verifikator|Perangkat Daerah'])->group(function () {
        // Menu Fasilitasiku
        Route::livewire('/nota-dinas', KelolaNotaDinas::class)
            ->name('nota-dinas.generate');
    });

    Route::middleware(['role:Peneliti'])->group(function () {
        Route::livewire('/persetujuan-fasilitasi', PersetujuanFasilitasiMain::class)->name('persetujuan-fasilitasi.main');
    });
    Route::middleware(['role:Admin'])->group(function () {
        Route::livewire('/kelola-bahan-penting', TambahBahanPenting::class)
            ->name('bahan-penting');
    });
    // Tamu hanya dapat melihat; pembaruan tahapan dicek lewat policy.
    Route::middleware(['role:Admin|Tamu'])->group(function () {
        Route::livewire('/manajemen-fasilitasi', ManajemenFasilitasi::class)
            ->name('manajemen-fasilitasi');
    });
    Route::middleware(['role:Admin|Verifikator|Perangkat Daerah|Peneliti'])->group(function () {
        Route::livewire('/dokumentasi-produk-hukum', Dokumentasi::class)->name('dokumentasi.main');
    });

    Route::middleware(['role:Admin|Verifikator|Peneliti|Super Admin'])->group(function () {
        Route::livewire('/master-data', MasterData::class)->name('masterdata.main');
        Route::get('/export-master-data', [MasterData::class, 'export'])->name('export.masterdata');
    });

    // Ekspor dokumentasi: sama dengan policy DokumentasiProdukHukum::viewAny
    Route::middleware(['role:Admin|Verifikator'])->group(function () {
        Route::get('/export-dokumentasi', [DokumentasiExportController::class, 'export'])->name('export.dokumentasi');
        Route::get('/export-dokumentasi-pdf', [DokumentasiPdfController::class, 'export'])->name('export.dokumentasi.pdf');
    });
});


require __DIR__ . '/auth.php';
