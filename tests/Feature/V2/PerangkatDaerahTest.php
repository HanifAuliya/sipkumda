<?php

use App\Livewire\V2\PengaturanPerangkatDaerah;
use App\Models\PerangkatDaerah;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
});

it('menu Perangkat Daerah hanya untuk Admin', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    $verifikator = tap(User::factory()->create())->assignRole('Verifikator');

    $this->actingAs($pd)->get(route('v2.perangkat-daerah'))->assertForbidden();
    $this->actingAs($verifikator)->get(route('v2.perangkat-daerah'))->assertForbidden();
    $this->actingAs($this->admin)->get(route('v2.perangkat-daerah'))
        ->assertOk()->assertSee('Tambah Perangkat Daerah');
});

it('menambah, menolak nama ganda, dan mengubah nama', function () {
    PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Kesehatan']);

    $halaman = Livewire::actingAs($this->admin)->test(PengaturanPerangkatDaerah::class)
        ->call('tambah')
        ->call('simpan')->assertHasErrors('nama')
        // Spasi berlebih dirapikan, jadi nama yang sama tetap ditolak.
        ->set('nama', '  Dinas   Kesehatan ')->call('simpan')->assertHasErrors('nama')
        ->set('nama', 'Dinas Perhubungan')->call('simpan')->assertHasNoErrors()
        ->assertSee('Dinas Perhubungan');

    $baru = PerangkatDaerah::where('nama_perangkat_daerah', 'Dinas Perhubungan')->firstOrFail();

    $halaman->call('ubah', $baru->id)->assertSet('nama', 'Dinas Perhubungan')
        // Menyimpan tanpa mengganti nama tidak dianggap ganda dengan dirinya sendiri.
        ->call('simpan')->assertHasNoErrors()
        ->call('ubah', $baru->id)->set('nama', 'Dinas Perhubungan dan Kominfo')->call('simpan')->assertHasNoErrors();

    expect($baru->fresh()->nama_perangkat_daerah)->toBe('Dinas Perhubungan dan Kominfo');
});

it('hanya menghapus Perangkat Daerah yang belum dipakai', function () {
    $dipakai = PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Dipakai']);
    $kosong = PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Kosong']);
    User::factory()->create(['perangkat_daerah_id' => $dipakai->id]);

    Livewire::actingAs($this->admin)->test(PengaturanPerangkatDaerah::class)
        ->assertSee('1 pengguna')
        ->assertSee('Belum dipakai')
        ->call('hapus', $dipakai->id)
        ->call('hapus', $kosong->id);

    expect(PerangkatDaerah::find($dipakai->id))->not->toBeNull()
        ->and(PerangkatDaerah::find($kosong->id))->toBeNull();
});
