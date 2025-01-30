<?php

use App\Livewire\Dashboard;
use App\Livewire\UserManagement;
use App\Livewire\ProfileManagement;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Response;
use App\Http\Controllers\ProfileController;
use App\Livewire\Admin\Fasilitasi\ManajemenFasilitasi;
use App\Livewire\Admin\NotaDinas\KelolaNotaDinas;
use App\Livewire\Rancangan\DaftarRancangan;
use App\Livewire\Admin\Rancangan\PersetujuanMain;
use App\Livewire\Peneliti\Rancangan\RevisiRancangan;

use App\Livewire\Verifikator\Rancangan\ValidasiMain;
use App\Livewire\Verifikator\Rancangan\PilihPeneliti;
use App\Livewire\Verifikator\TandaTangan\PengelolaTtd;

use App\Livewire\Perangkatdaerah\Rancangan\Rancanganku;
use App\Livewire\PerangkatDaerah\Fasilitasi\Fasilitasiku;
use App\Livewire\Verifikator\Fasilitasi\ValidasiFasilitasi;
use App\Livewire\Peneliti\Fasilitasi\PersetujuanFasilitasiMain;



Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    // **Dashboard (Semua Role termasuk Super Admin)**
    Route::get('/dashboard', Dashboard::class)
        ->middleware(['verified'])
        ->name('dashboard');

    // **Daftar Rancangan (Semua Role terkait dan Super Admin)**
    Route::get('/daftar-rancangan', DaftarRancangan::class)
        ->middleware('role:Admin|Verifikator|Perangkat Daerah|Peneliti|Super Admin')
        ->name('daftar-rancangan');


    // **Profile (Semua Role termasuk Super Admin)**
    Route::middleware(['password.confirm'])->group(function () {
        Route::get('/profile', ProfileManagement::class)->name('profile.edit');
    });

    // **User Management (Admin, Verifikator, dan Super Admin)**
    Route::middleware(['role:Admin|Verifikator|Super Admin'])->group(function () {
        Route::get('/user-management', UserManagement::class)->name('user.management');
        Route::get('/validasi-rancangan', ValidasiMain::class)->name('verifikator.validasi-rancangan');
    });

    // **Rancanganku (Perangkat Daerah dan Super Admin)**
    Route::middleware(['role:Perangkat Daerah|Super Admin'])->group(function () {
        Route::get('/rancanganku', Rancanganku::class)
            ->name('rancanganku');
        // Menu Fasilitasiku
        Route::get('/fasilitasiku', Fasilitasiku::class)
            ->name('fasilitasiku.main');
    });
    // **Admin-Only Routes (Termasuk Super Admin)**
    Route::middleware(['role:Admin|Super Admin'])->group(function () {
        Route::get('/rancangan/persetujuan', PersetujuanMain::class)->name('admin.persetujuan');
    });

    // **Verifikator-Only Routes (Termasuk Super Admin)**
    Route::middleware(['role:Verifikator|Super Admin'])->group(function () {
        Route::get('/rancangan/pilih-peneliti', PilihPeneliti::class)->name('verifikator.pilih-peneliti');
    });

    Route::middleware(['role:Peneliti|Super Admin'])->group(function () {
        // **Revisi Rancangan (Semua Role termasuk Super Admin)**
        Route::get('/revisi/rancangan', RevisiRancangan::class)
            ->name('revisi.rancangan');
    });
    // **View Private Files (Semua Role termasuk Super Admin)**
    Route::get('/view-private/{folder}/{subfolder}/{filename}', function ($folder, $subfolder, $filename) {
        // Periksa apakah user sudah login
        if (!auth()->check()) {
            abort(403, 'Unauthorized access.');
        }

        // Path lengkap file
        $filePath = "{$folder}/{$subfolder}/{$filename}";

        // Cek apakah file ada
        if (!Storage::disk('local')->exists($filePath)) {
            abort(404, 'File not found.');
        }

        // Ambil konten file
        $fileContent = Storage::disk('local')->get($filePath);

        // Tentukan MIME type berdasarkan ekstensi file
        $mimeType = Storage::disk('local')->mimeType($filePath);

        // Kembalikan file untuk ditampilkan di browser
        return Response::make($fileContent, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    });

    // **======================================================**
    // **Fasilitasi**
    Route::middleware(['role:Verifikator|Super Admin'])->group(function () {
        Route::get('/validasi/fasilitasi', ValidasiFasilitasi::class)
            ->name('validasi-fasilitasi');
        Route::get('/kelola-ttd', PengelolaTtd::class)
            ->name('kelola-ttd.main');
    });

    Route::middleware(['role:Perangkat Daerah|Super Admin'])->group(function () {
        // Menu Fasilitasiku
        Route::get('/fasilitasiku', Fasilitasiku::class)
            ->name('fasilitasiku.main');
    });

    Route::middleware(['role:Admin|Super Admin|Verifikator|Perangkat Daerah'])->group(function () {
        // Menu Fasilitasiku
        Route::get('/nota-dinas', KelolaNotaDinas::class)
            ->name('nota-dinas.generate');

        Route::get('/manajemen-fasilitasi', ManajemenFasilitasi::class)
            ->name('manajemen-fasilitasi');
    });

    Route::middleware(['role:Peneliti|Super Admin'])->group(function () {
        Route::get('/persetujuan-fasilitasi', PersetujuanFasilitasiMain::class)->name('persetujuan-fasilitasi.main');
    });
});




Route::get('/test-500', function () {
    abort(500); // Memaksa Laravel untuk menampilkan halaman error 500
});





require __DIR__ . '/auth.php';
// Route::middleware(['auth', 'password.confirm'])->group(function () {
//     Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//     Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//     Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
// });

// Route::middleware('auth')->group(function () {
//     Route::get('/profile', Profile::class)->name('profile.edit');
// });

// Route::middleware(['auth', 'password.confirm'])->group(function () {
//     Route::get('/profile', function () {
//         return view('auth.profile'); // File utama dengan semua card Livewire
//     })->name('profile.edit');
// });


// Route::middleware(['auth'])->group(function () {
//     Route::resource('users', UserController::class)->names([
//         'index' => 'users.index',       // Rute untuk menampilkan daftar pengguna
//         'create' => 'users.create',     // Rute untuk form tambah pengguna
//         'store' => 'users.store',       // Rute untuk menyimpan pengguna baru
//         'edit' => 'users.edit',         // Rute untuk form edit pengguna
//         'update' => 'users.update',     // Rute untuk memperbarui pengguna
//         'destroy' => 'users.destroy',   // Rute untuk menghapus pengguna
//     ]);
// });

// Route::middleware(['auth',])->group(function () {
//     Route::get('/user-management', function () {
//         return view('users.manage-user'); // File utama dengan semua card Livewire
//     })->name('user.management');
// });
