<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\ProfileManagement;
use App\Livewire\Dashboard;

use App\Livewire\UserManagement;
use App\Livewire\Rancangan\DaftarRancangan;

use App\Livewire\Perangkatdaerah\Rancangan\Rancanganku;
use App\Livewire\Admin\Rancangan\PersetujuanMain;
use App\Livewire\Verifikator\Rancangan\PilihPeneliti;
use App\Livewire\Verifikator\Rancangan\ValidasiMain;
use App\Livewire\Peneliti\Rancangan\Revisi;

use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;



Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', Dashboard::class)
        ->middleware(['auth', 'verified'])
        ->name('dashboard');

    Route::middleware(['password.confirm'])->group(function () {
        Route::get('/profile', ProfileManagement::class)->name('profile.edit');
    });

    Route::middleware(['role:Admin|Verifikator'])->group(function () {
        Route::get('/user-management', UserManagement::class)->name('user.management');
    });

    Route::get('/daftar-rancangan', DaftarRancangan::class)
        ->middleware('role:Admin|Verifikator|Perangkat_Daerah|Peneliti')
        ->name('daftar-rancangan');

    Route::get('/rancanganku', Rancanganku::class)->name('rancanganku')
        ->middleware('role:Perangkat_Daerah');

    Route::middleware(['role:Admin'])->group(function () {
        Route::get('/rancangan/persetujuan', PersetujuanMain::class)->name('admin.persetujuan');
    });

    Route::middleware(['role:Verifikator'])->group(function () {
        Route::get('/rancangan/pilih-peneliti', PilihPeneliti::class)->name('verifikator.pilih-peneliti');
    });

    Route::get('/revisi/rancangan', Revisi::class)
        ->name('revisi.rancangan');

    Route::middleware(['role:Verifikator'])->group(function () {
        Route::get('/validasi-rancangan', ValidasiMain::class)
            ->name('verifikator.validasi-rancangan');
    });


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
