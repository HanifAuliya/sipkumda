<?php

use App\Livewire\Dokumentasi\AjukanDokumentasiProdukHukum;
use App\Livewire\PerangkatDaerah\Rancangan\AjukanRancangan;
use App\Models\DokumentasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function formArsipManual(User $user, string $tentang)
{
    return Livewire::actingAs($user)->test(AjukanDokumentasiProdukHukum::class)
        ->call('open', 'manual')
        ->set('tentang_dokumentasi', $tentang)
        ->set('jenis_dokumentasi', 'Peraturan Bupati')
        ->set('perangkat_daerah_id', PerangkatDaerah::factory()->create()->id)
        ->set('nomor', '7')
        ->set('tahun', '2020')
        ->set('nomor_berita', '7')
        ->set('tahun_berita', '2020')
        ->set('tanggal_penetapan', '2020-01-02')
        ->set('file_produk_hukum', UploadedFile::fake()->create('perbup.pdf', 10, 'application/pdf'));
}

it('arsip manual menerima judul panjang sampai batas kolom database', function () {
    $admin = tap(User::factory()->create())->assignRole('Verifikator');

    formArsipManual($admin, str_repeat('a', DokumentasiProdukHukum::TENTANG_MAKS))
        ->call('store')
        ->assertHasNoErrors();

    expect(mb_strlen(DokumentasiProdukHukum::first()->tentang_dokumentasi))->toBe(DokumentasiProdukHukum::TENTANG_MAKS);
});

it('arsip manual menolak judul yang melewati batas dengan pesan, bukan error database', function () {
    $admin = tap(User::factory()->create())->assignRole('Verifikator');

    formArsipManual($admin, str_repeat('a', DokumentasiProdukHukum::TENTANG_MAKS + 1))
        ->call('store')
        ->assertHasErrors(['tentang_dokumentasi' => 'max']);

    expect(DokumentasiProdukHukum::count())->toBe(0);
});

it('rancangan aplikasi lama bisa menyimpan judul panjang sampai batas kolom', function () {
    $rancangan = RancanganProdukHukum::factory()->create(['tentang' => str_repeat('a', DokumentasiProdukHukum::TENTANG_MAKS)]);

    expect(mb_strlen($rancangan->refresh()->tentang))->toBe(DokumentasiProdukHukum::TENTANG_MAKS);
});

it('form judul tidak menampilkan penghitung batas', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');

    Livewire::actingAs($pd)->test(AjukanRancangan::class)
        ->assertSeeHtml('<textarea rows="3"')
        ->assertDontSee('karakter');
});

it('arsip manual menyimpan judul tempelan Word menjadi satu baris', function () {
    $admin = tap(User::factory()->create())->assignRole('Verifikator');

    formArsipManual($admin, "Perubahan Atas Peraturan\r\n  Tentang   Dana Desa")
        ->call('store')
        ->assertHasNoErrors();

    expect(DokumentasiProdukHukum::first()->tentang_dokumentasi)->toBe('Perubahan Atas Peraturan Tentang Dana Desa');
});

it('aplikasi lama merapikan judul sebelum memeriksa pengajuan', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');

    Livewire::actingAs($pd)->test(AjukanRancangan::class)
        ->set('tentang', "Dana\r\n  Desa ")
        ->call('submit')
        ->assertSet('tentang', 'Dana Desa')
        ->assertHasErrors('rancangan');
});
