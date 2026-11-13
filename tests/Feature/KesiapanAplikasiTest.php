<?php

use App\Models\Naskah;
use App\Models\NaskahRiwayat;
use App\Models\Pengaturan;
use App\Models\PerangkatDaerah;
use App\Models\TandaTangan;
use App\Models\User;
use App\Support\KesiapanAplikasi;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = tap(User::factory()->create(['tur_selesai_at' => now()]))->assignRole('Admin');
    config(['backup.folder' => storage_path('framework/testing/cadangan-' . uniqid())]);
});

afterEach(function () {
    File::deleteDirectory(config('backup.folder'));
});

it('Dashboard Bagian Hukum menunjukkan yang belum siap, dan kartunya hilang setelah semua beres', function () {
    config(['mail.default' => 'log']);

    $this->actingAs($this->admin)->get(route('dashboard'))
        ->assertSee('Kesiapan aplikasi')
        ->assertSee('Nama Bupati dan Sekretaris Daerah')
        ->assertSee('Belum ada tanda tangan aktif')
        ->assertSee('MAIL_MAILER=log')
        ->assertSee('Belum pernah ada cadangan')
        ->assertSee(route('pengaturan'), false);

    // Perangkat Daerah tidak melihatnya.
    $pd = tap(User::factory()->create(['tur_selesai_at' => now(), 'perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]))->assignRole('Perangkat Daerah');
    $this->actingAs($pd)->get(route('dashboard'))->assertDontSee('Kesiapan aplikasi');

    // Semua dibereskan.
    Pengaturan::simpan(Pengaturan::NAMA_BUPATI, 'H. Nama Bupati');
    Pengaturan::simpan(Pengaturan::NAMA_SEKDA, 'H. Nama Sekda');
    TandaTangan::create(['nama_ttd' => 'Kabag', 'nip_ttd' => '1', 'jabatan' => 'Pembina', 'file_ttd' => 'tanda_tangan/ttd/x.png', 'dibuat_oleh' => $this->admin->id, 'status' => 'Aktif']);
    config(['mail.default' => 'smtp']);
    File::ensureDirectoryExists(config('backup.folder') . '/database');
    File::put(config('backup.folder') . '/database/sipkumda-uji.sql.gz', 'x');

    expect(KesiapanAplikasi::belumSiap($this->admin))->toBe([]);
    $this->actingAs($this->admin)->get(route('dashboard'))->assertDontSee('Kesiapan aplikasi');

    // Cadangan yang sudah lama berarti penjadwal berhenti.
    touch(config('backup.folder') . '/database/sipkumda-uji.sql.gz', now()->subDays(5)->timestamp);
    clearstatcache();
    expect(KesiapanAplikasi::belumSiap($this->admin)[0]['keterangan'])->toContain('5 hari lalu');
});

it('Giliran Anda diurutkan dari yang paling lama menunggu dan menampilkan lamanya', function () {
    $pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]))->assignRole('Perangkat Daerah');
    $buat = function (string $tentang, int $hari) use ($pd) {
        $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => $tentang, 'pemrakarsa_id' => $pd->id]);
        $n->forceFill(['tahap' => \App\Enums\TahapNaskah::PersetujuanBerkas, 'diajukan_at' => now()->subDays($hari)])->save();
        $r = NaskahRiwayat::create(['naskah_id' => $n->id, 'user_id' => $pd->id, 'aksi' => 'tahap', 'sesudah' => 'Persetujuan Berkas']);
        $r->forceFill(['created_at' => now()->subDays($hari)])->save();
    };
    $buat('Baru Masuk', 1);
    $buat('Sudah Lama', 9);

    $this->actingAs($this->admin)->get(route('dashboard'))
        ->assertSeeInOrder(['Giliran Anda', 'Sudah Lama', 'Menunggu 9 hari', 'Baru Masuk', 'Menunggu 1 hari']);
});

it('tombol Bahan Penting selalu berisi template Peraturan Bupati dan Keputusan Bupati', function () {
    $pd = tap(User::factory()->create(['tur_selesai_at' => now(), 'perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]))->assignRole('Perangkat Daerah');

    $this->actingAs($pd)->get(route('daftar', 'rancanganku'))
        ->assertSee('Template Peraturan Bupati')
        ->assertSee(route('template', 'keputusan-bupati'), false)
        ->assertDontSee('Belum ada bahan penting');
});
