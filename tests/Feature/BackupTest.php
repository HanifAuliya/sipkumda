<?php

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\ExecutableFinder;

beforeEach(function () {
    $this->folder = storage_path('framework/testing/backup-' . uniqid());
    $this->sumber = "{$this->folder}/sumber";

    File::ensureDirectoryExists($this->sumber);
    config([
        'backup.folder' => "{$this->folder}/hasil",
        'backup.sumber_berkas' => ['private' => $this->sumber],
    ]);
});

afterEach(function () {
    File::deleteDirectory($this->folder);
});

function gzBackup(string $folder): array
{
    return File::glob("{$folder}/hasil/database/*.sql.gz");
}

it('membuat dump lengkap yang tidak mengarah ke database tertentu', function () {
    File::put("{$this->sumber}/a.pdf", 'isi');

    $this->artisan('backup:jalankan')->assertSuccessful();

    $files = gzBackup($this->folder);
    expect($files)->toHaveCount(1);

    $sql = implode('', gzfile($files[0]));
    expect($sql)->toContain('CREATE TABLE `users`')
        ->toContain('Dump completed')
        ->not->toMatch('/^(USE|CREATE DATABASE) /m');
})->skip(fn () => (new ExecutableFinder)->find('mysqldump') === null, 'mysqldump tidak tersedia');

it('hanya menyalin berkas yang baru atau berubah', function () {
    File::put("{$this->sumber}/a.pdf", 'isi');
    File::ensureDirectoryExists("{$this->sumber}/livewire-tmp");
    File::put("{$this->sumber}/livewire-tmp/sementara.pdf", 'x');

    $this->artisan('backup:jalankan')
        ->expectsOutput('Berkas baru atau berubah yang disalin: 1')
        ->assertSuccessful();

    expect(File::get("{$this->folder}/hasil/berkas/private/a.pdf"))->toBe('isi')
        ->and(File::exists("{$this->folder}/hasil/berkas/private/livewire-tmp/sementara.pdf"))->toBeFalse();

    $this->artisan('backup:jalankan')
        ->expectsOutput('Berkas baru atau berubah yang disalin: 0')
        ->assertSuccessful();

    File::put("{$this->sumber}/a.pdf", 'isi yang lebih panjang');
    $this->artisan('backup:jalankan')->expectsOutput('Berkas baru atau berubah yang disalin: 1')->assertSuccessful();

    expect(File::get("{$this->folder}/hasil/berkas/private/a.pdf"))->toBe('isi yang lebih panjang');
})->skip(fn () => (new ExecutableFinder)->find('mysqldump') === null, 'mysqldump tidak tersedia');

it('mempertahankan salinan saat berkas aslinya dihapus', function () {
    File::put("{$this->sumber}/a.pdf", 'isi');
    $this->artisan('backup:jalankan')->assertSuccessful();

    File::delete("{$this->sumber}/a.pdf");
    $this->artisan('backup:jalankan')->assertSuccessful();

    expect(File::exists("{$this->folder}/hasil/berkas/private/a.pdf"))->toBeTrue();
})->skip(fn () => (new ExecutableFinder)->find('mysqldump') === null, 'mysqldump tidak tersedia');

it('menghapus cadangan lama tetapi selalu menyisakan yang terbaru', function () {
    config(['backup.simpan_hari' => 14, 'backup.simpan_minimal' => 2]);
    $folder = "{$this->folder}/hasil/database";
    File::ensureDirectoryExists($folder);

    foreach (['lama-1' => 40, 'lama-2' => 30, 'lama-3' => 20, 'baru' => 1] as $nama => $hari) {
        File::put("{$folder}/{$nama}.sql.gz", 'x');
        touch("{$folder}/{$nama}.sql.gz", now()->subDays($hari)->getTimestamp());
    }
    File::put("{$folder}/catatan.txt", 'bukan cadangan');
    touch("{$folder}/catatan.txt", now()->subDays(90)->getTimestamp());

    $this->artisan('backup:jalankan')->assertSuccessful();

    // Dump hari ini dan cadangan "baru" tersisa sebagai dua terbaru, yang lewat 14 hari dihapus.
    expect(File::exists("{$folder}/baru.sql.gz"))->toBeTrue()
        ->and(File::exists("{$folder}/lama-1.sql.gz"))->toBeFalse()
        ->and(File::exists("{$folder}/lama-2.sql.gz"))->toBeFalse()
        ->and(File::exists("{$folder}/lama-3.sql.gz"))->toBeFalse()
        ->and(File::exists("{$folder}/catatan.txt"))->toBeTrue();
})->skip(fn () => (new ExecutableFinder)->find('mysqldump') === null, 'mysqldump tidak tersedia');

it('saat gagal tidak meninggalkan file setengah jadi, tidak menghapus cadangan lama, dan memberi tahu admin', function () {
    $this->seed(RoleSeeder::class);
    $admin = tap(User::factory()->create())->assignRole('Admin');
    $bukanAdmin = tap(User::factory()->create())->assignRole('Verifikator');

    config(['backup.mysqldump' => 'perintah-yang-tidak-ada', 'backup.simpan_minimal' => 0]);
    $folder = "{$this->folder}/hasil/database";
    File::ensureDirectoryExists($folder);
    File::put("{$folder}/lama.sql.gz", 'x');
    touch("{$folder}/lama.sql.gz", now()->subDays(90)->getTimestamp());

    $this->artisan('backup:jalankan')->assertFailed();

    expect(File::files($folder))->toHaveCount(1)
        ->and(File::exists("{$folder}/lama.sql.gz"))->toBeTrue();

    expect($admin->notifikasiNaskah()->first()->data['title'])->toBe('Backup otomatis gagal')
        ->and($admin->notifications()->count())->toBe(2)
        ->and($bukanAdmin->notifications()->count())->toBe(0);
});

it('dijadwalkan setiap hari', function () {
    $this->artisan('schedule:list')->expectsOutputToContain('backup:jalankan')->assertSuccessful();
});
