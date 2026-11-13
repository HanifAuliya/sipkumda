<?php

use App\Livewire\Profile\TandaTanganKabag;
use App\Livewire\Verifikator\TandaTangan\PengelolaTtd;
use App\Models\TandaTangan;
use App\Models\User;
use App\Support\KesiapanAplikasi;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
 * Verifikator adalah Kepala Bagian Hukum: ia mengatur tanda tangannya sendiri di Profil, begitu juga
 * Peneliti. Kelola TTD menjadi menu Admin untuk membantu.
 */

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->admin = tap(User::factory()->create(['tur_selesai_at' => now()]))->assignRole('Admin');
    $this->kabag = tap(User::factory()->create(['tur_selesai_at' => now(), 'nama_user' => 'Taufik Rahman, S.H.', 'NIP' => '197501012000031001']))->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create(['tur_selesai_at' => now(), 'nama_user' => 'Sahruji, S.H.']))->assignRole('Peneliti');
});

function gambarTtdUji(): UploadedFile
{
    $g = imagecreatetruecolor(600, 300);
    imagefill($g, 0, 0, imagecolorallocate($g, 255, 255, 255));
    imagefilledrectangle($g, 100, 100, 499, 199, imagecolorallocate($g, 20, 30, 120));
    ob_start();
    imagepng($g);

    return UploadedFile::fake()->createWithContent('ttd.png', ob_get_clean());
}

it('Kepala Bagian Hukum mengatur tanda tangannya sendiri di Profil, dengan kata sandi', function () {
    $this->actingAs($this->kabag)->get(route('profile.edit'))->assertOk()
        ->assertSee('Sebagai Kepala Bagian Hukum', false);

    Livewire::actingAs($this->kabag)->test(TandaTanganKabag::class)
        ->assertSet('ubah', true)
        ->assertSet('nama', 'Taufik Rahman, S.H.')
        ->assertSet('nip', '197501012000031001')
        ->set('jabatan', 'Pembina Tk. I')
        ->call('simpan')
        ->assertHasErrors(['gambar' => 'required', 'sandiSekarang' => 'required'])
        ->set('gambar', gambarTtdUji())
        ->set('sandiSekarang', 'salah')
        ->call('simpan')
        ->assertHasErrors(['sandiSekarang' => 'current_password'])
        ->set('sandiSekarang', 'password')
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('ubah', false)
        ->assertSee('Ubah Tanda Tangan');

    $ttd = $this->kabag->refresh()->tandaTanganKabag;
    expect($ttd)->not->toBeNull()
        ->and($ttd->status)->toBe('Aktif')
        ->and($ttd->jabatan)->toBe('Pembina Tk. I')
        ->and($ttd->file_ttd)->toStartWith('tanda_tangan/ttd/');
    Storage::disk('local')->assertExists($ttd->file_ttd);

    // Menggantinya membuang gambar lama.
    $lama = $ttd->file_ttd;
    Livewire::actingAs($this->kabag)->test(TandaTanganKabag::class)
        ->call('mulaiUbah')->set('gambar', gambarTtdUji())->set('sandiSekarang', 'password')->call('simpan')->assertHasNoErrors();
    Storage::disk('local')->assertMissing($lama);
    expect(TandaTangan::count())->toBe(1);
});

it('hanya Verifikator yang punya kartu tanda tangan Kepala Bagian Hukum', function () {
    Livewire::actingAs($this->peneliti)->test(TandaTanganKabag::class)->assertForbidden();
    Livewire::actingAs($this->admin)->test(TandaTanganKabag::class)->assertForbidden();
});

it('NIP yang sudah dipakai data lain diarahkan ke Admin', function () {
    TandaTangan::create(['nama_ttd' => 'Data Lama', 'nip_ttd' => '197501012000031001', 'jabatan' => 'Pembina', 'file_ttd' => 'tanda_tangan/ttd/lama.png', 'status' => 'Aktif', 'dibuat_oleh' => $this->admin->id]);

    Livewire::actingAs($this->kabag)->test(TandaTanganKabag::class)
        ->set('jabatan', 'Pembina Tk. I')->set('gambar', gambarTtdUji())->set('sandiSekarang', 'password')
        ->call('simpan')
        ->assertHasErrors('nip')
        ->assertSee('Minta Admin menghubungkannya');
});

it('Kelola TTD menjadi menu Admin; Verifikator mengaturnya di Profil', function () {
    $this->actingAs($this->admin)->get(route('kelola-ttd.main'))->assertOk()->assertSee('Kepala Bagian Hukum')->assertSee('Peneliti');
    $this->actingAs($this->kabag)->get(route('kelola-ttd.main'))->assertForbidden();
    $this->actingAs($this->kabag)->get(route('dashboard'))->assertDontSee(route('kelola-ttd.main'));
});

it('Admin menghubungkan data Kabag ke akun Verifikator, lalu Verifikator melihatnya di Profil', function () {
    $ttd = TandaTangan::create(['nama_ttd' => 'TAUFIK RAHMAN, S.H.', 'nip_ttd' => '1975', 'jabatan' => 'Pembina', 'file_ttd' => 'tanda_tangan/ttd/a.png', 'status' => 'Aktif', 'dibuat_oleh' => $this->admin->id]);

    Livewire::actingAs($this->admin)->test(PengelolaTtd::class)
        ->call('openModalEdit', $ttd->id)
        ->assertSee('Taufik Rahman, S.H.')
        ->set('user_id', $this->kabag->id)
        ->call('save')
        ->assertHasNoErrors();

    expect($ttd->refresh()->user_id)->toBe($this->kabag->id);

    Livewire::actingAs($this->kabag)->test(TandaTanganKabag::class)
        ->assertSet('ubah', false)
        ->assertSee('TAUFIK RAHMAN, S.H.');

    // Akun yang sudah punya tanda tangan tidak bisa dihubungkan ke data lain.
    $lain = TandaTangan::create(['nama_ttd' => 'Plt', 'nip_ttd' => '1980', 'jabatan' => 'Penata', 'file_ttd' => 'tanda_tangan/ttd/b.png', 'status' => 'Aktif', 'dibuat_oleh' => $this->admin->id]);
    Livewire::actingAs($this->admin)->test(PengelolaTtd::class)
        ->call('openModalEdit', $lain->id)
        ->set('user_id', $this->kabag->id)
        ->call('save')
        ->assertHasErrors('user_id');
});

it('Admin mengganti gambar tanda tangan orang lain dengan kata sandinya sendiri', function () {
    $ttd = TandaTangan::create(['nama_ttd' => 'Kabag', 'nip_ttd' => '1975', 'jabatan' => 'Pembina', 'file_ttd' => 'tanda_tangan/ttd/a.png', 'status' => 'Aktif', 'dibuat_oleh' => $this->admin->id]);

    Livewire::actingAs($this->admin)->test(PengelolaTtd::class)
        ->call('openModalEdit', $ttd->id)
        ->set('file_ttd', gambarTtdUji())
        ->call('save')
        ->assertHasErrors(['sandiAdmin' => 'required'])
        ->set('sandiAdmin', 'password')
        ->call('save')
        ->assertHasNoErrors();

    expect($ttd->refresh()->file_ttd)->not->toBe('tanda_tangan/ttd/a.png');
    Storage::disk('local')->assertExists($ttd->file_ttd);
});

it('Admin membantu mengunggah, melihat, dan menghapus tanda tangan peneliti', function () {
    Livewire::actingAs($this->admin)->test(PengelolaTtd::class)
        ->set('tab', 'peneliti')
        ->assertSee('Sahruji, S.H.')
        ->assertSee('Belum ada tanda tangan')
        ->call('bukaPeneliti', $this->peneliti->id)
        ->set('gambarPeneliti', gambarTtdUji())
        ->call('simpanPeneliti')
        ->assertHasErrors(['sandiAdmin' => 'required'])
        ->set('sandiAdmin', 'password')
        ->call('simpanPeneliti')
        ->assertHasNoErrors();

    $path = $this->peneliti->refresh()->file_ttd;
    expect($path)->toStartWith('tanda_tangan/peneliti/');

    $this->actingAs($this->admin)->get(route('kelola-ttd.peneliti', $this->peneliti))->assertOk();
    $this->actingAs($this->kabag)->get(route('kelola-ttd.peneliti', $this->peneliti))->assertForbidden();
    // Bukan peneliti: tidak dilayani.
    $this->actingAs($this->admin)->get(route('kelola-ttd.peneliti', $this->kabag))->assertNotFound();

    Livewire::actingAs($this->admin)->test(PengelolaTtd::class)->set('tab', 'peneliti')->call('hapusPeneliti', $this->peneliti->id);
    expect($this->peneliti->refresh()->file_ttd)->toBeNull();
    Storage::disk('local')->assertMissing($path);
});

it('Kesiapan aplikasi menyebut peneliti yang belum mengunggah tanda tangan', function () {
    $butir = collect(KesiapanAplikasi::untuk($this->admin))->firstWhere('judul', 'Tanda tangan peneliti');
    expect($butir['siap'])->toBeFalse()
        ->and($butir['keterangan'])->toContain('Sahruji, S.H.')
        ->and($butir['tautan'])->toBe(route('kelola-ttd.main'));

    // Verifikator diarahkan ke Profil untuk tanda tangannya sendiri.
    $kabag = collect(KesiapanAplikasi::untuk($this->kabag))->firstWhere('judul', 'Tanda tangan Kepala Bagian Hukum');
    expect($kabag['tautan'])->toBe(route('profile.edit'));
});
