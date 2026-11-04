<?php

use App\Livewire\Admin\Fasilitasi\ManajemenFasilitasi;
use App\Models\FasilitasiProdukHukum;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    $this->tamu = tap(User::factory()->create())->assignRole('Tamu');
    $this->pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');

    $rancangan = RancanganProdukHukum::factory()->create(['id_user' => $this->pd->id, 'jenis_rancangan' => 'Peraturan Bupati']);

    $this->fasilitasi = FasilitasiProdukHukum::create([
        'rancangan_id' => $rancangan->id_rancangan,
        'tanggal_fasilitasi' => now(),
        'file_rancangan' => 'x.pdf',
        'status_berkas_fasilitasi' => 'Disetujui',
        'status_validasi_fasilitasi' => 'Diterima',
    ]);
});

it('Tamu bisa memperbarui tahapan paraf di Manajemen Fasilitasi', function () {
    Livewire::actingAs($this->tamu)
        ->test(ManajemenFasilitasi::class)
        ->assertSee('Perbarui Tahap')
        ->call('openUpdateStatus', $this->fasilitasi->id)
        ->assertSet('selectedStatus', 'status_paraf_koordinasi')
        ->call('updateStatus')
        ->assertHasNoErrors();

    expect($this->fasilitasi->fresh()->status_paraf_koordinasi)->toBe('Selesai')
        ->and($this->pd->notifications()->exists())->toBeTrue();
});

it('Tamu tidak mendapat hak mengubah fasilitasi selain tahapan', function () {
    expect($this->tamu->can('update', $this->fasilitasi))->toBeFalse()
        ->and($this->tamu->can('delete', $this->fasilitasi))->toBeFalse()
        ->and($this->tamu->can('perbaruiTahapan', $this->fasilitasi))->toBeTrue()
        ->and($this->pd->can('perbaruiTahapan', $this->fasilitasi))->toBeFalse();
});

it('pengguna lain tetap tidak bisa membuka Manajemen Fasilitasi', function () {
    $this->actingAs($this->pd)->get(route('manajemen-fasilitasi'))->assertForbidden();
});

it('menu Tamu di aplikasi lama hanya Dashboard, Profil, dan Manajemen Fasilitasi', function () {
    $this->actingAs($this->tamu)->get(route('manajemen-fasilitasi'))->assertOk()
        ->assertSee(['Dashboard', 'Profil', 'Manajemen Fasilitasi'])
        ->assertDontSee('Daftar Rancangan');
});
