<?php

use App\Livewire\Dokumentasi\AjukanDokumentasiProdukHukum;
use App\Models\DokumentasiProdukHukum;
use App\Models\PerangkatDaerah;
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
        ->call('open')
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

it('arsip manual menyimpan judul tempelan Word menjadi satu baris', function () {
    $admin = tap(User::factory()->create())->assignRole('Verifikator');

    formArsipManual($admin, "Perubahan Atas Peraturan\r\n  Tentang   Dana Desa")
        ->call('store')
        ->assertHasNoErrors();

    expect(DokumentasiProdukHukum::first()->tentang_dokumentasi)->toBe('Perubahan Atas Peraturan Tentang Dana Desa');
});

it('memilih Keputusan Bupati mengganti isian nomor ke format SK yang bisa disesuaikan', function () {
    $admin = tap(User::factory()->create())->assignRole('Verifikator');

    $form = formArsipManual($admin, 'Penetapan Desa Wisata')
        ->assertDontSee('Nomor Keputusan Bupati')
        ->set('jenis_dokumentasi', 'Keputusan Bupati')
        ->assertSee('Nomor Keputusan Bupati')
        ->assertSet('polaDasar', '140/{nomor}/412.1.6/TAHUN {tahun}')
        ->assertSet('bagianNomor', [0 => '140', 4 => '412.1.6']);

    // Bagian kode wajib diisi.
    $form->set('bagianNomor.0', ' ')->call('store')->assertHasErrors('nomor');

    $form->set('bagianNomor.0', '800')->call('store')->assertHasNoErrors();

    expect(DokumentasiProdukHukum::first()->nomor_formatted)->toBe('Nomor 800/7/412.1.6/TAHUN 2020');

    // Kembali ke Peraturan Bupati: nomor biasa, tanpa pola.
    formArsipManual($admin, 'Pedoman Lain')->set('nomor', '8')->set('jenis_dokumentasi', 'Keputusan Bupati')
        ->set('jenis_dokumentasi', 'Peraturan Bupati')->assertSet('polaDasar', '')->call('store')->assertHasNoErrors();

    expect(DokumentasiProdukHukum::where('nomor', 8)->first()->nomor_formatted)->toBe('Nomor 8 Tahun 2020');
});
