<?php

use App\Models\PerangkatDaerah;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/*
 * Deploy lewat Git di hosting tanpa SSH: sifkumda:siapkan mengisi data dasar dan aman diulang,
 * sifkumda:selaraskan tidak menyentuh apa pun di luar paket produksi (tanpa file REVISION).
 */

beforeEach(fn () => Storage::fake('local'));

it('menyiapkan peran, Perangkat Daerah, dan Super Admin pertama, lalu aman dijalankan ulang', function () {
    config()->set('sifkumda.superadmin', ['nip' => '197001012000031001', 'nama' => 'Admin Utama', 'email' => 'admin@contoh.go.id']);

    $this->artisan('sifkumda:siapkan')->assertSuccessful()->expectsOutputToContain('Kata sandi sementara');

    $admin = User::where('NIP', '197001012000031001')->firstOrFail();
    $jumlahPd = PerangkatDaerah::count();

    expect($admin->hasRole('Super Admin'))->toBeTrue()
        ->and($admin->wajib_ganti_sandi)->toBeTrue()
        ->and($jumlahPd)->toBeGreaterThan(0);

    // Rilis berikutnya: tidak ada Perangkat Daerah ganda dan kata sandi Super Admin tidak diganti.
    $sandi = $admin->password;
    $this->artisan('sifkumda:siapkan')->assertSuccessful()->doesntExpectOutputToContain('Kata sandi sementara');

    expect(PerangkatDaerah::count())->toBe($jumlahPd)
        ->and($admin->fresh()->password)->toBe($sandi);
});

it('tanpa NIP Super Admin di .env tidak membuat akun', function () {
    config()->set('sifkumda.superadmin', ['nip' => null, 'nama' => null, 'email' => null]);

    $this->artisan('sifkumda:siapkan')->assertSuccessful();

    expect(User::count())->toBe(0);
});

it('menyelaraskan hanya pada paket produksi', function () {
    expect(file_exists(base_path('REVISION')))->toBeFalse();

    $this->artisan('sifkumda:selaraskan')->assertSuccessful()->expectsOutputToContain('Bukan paket produksi');
});
