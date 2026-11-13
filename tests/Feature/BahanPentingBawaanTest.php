<?php

use App\Livewire\Rancangan\BahanPentingDropdown;
use App\Models\ImportantMaterial;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Services\PembacaNaskahWord;
use App\Support\BahanPentingBawaan;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake();
});

it('mengisi Bahan Penting dengan empat berkas bawaan dan aman dijalankan ulang', function () {
    $this->artisan('bahan-penting:bawaan')->assertSuccessful();
    $this->artisan('bahan-penting:bawaan')->assertSuccessful();

    expect(ImportantMaterial::pluck('button_name')->all())->toEqualCanonicalizing(array_keys(BahanPentingBawaan::DAFTAR));
    foreach (ImportantMaterial::all() as $m) {
        Storage::assertExists($m->file_path);
    }

    // Bawaan yang dihapus Admin tidak muncul lagi, kecuali dipulihkan; berkas unggahan Admin tidak disentuh.
    ImportantMaterial::where('button_name', 'Daftar Periksa Pengajuan')->delete();
    ImportantMaterial::create(['button_name' => 'Unggahan Admin', 'file_path' => 'public/templates/unggahan.pdf']);
    $this->artisan('bahan-penting:bawaan')->assertSuccessful();
    expect(ImportantMaterial::where('button_name', 'Daftar Periksa Pengajuan')->exists())->toBeFalse()
        ->and(ImportantMaterial::where('button_name', 'Unggahan Admin')->exists())->toBeTrue();

    $this->artisan('bahan-penting:bawaan --pulihkan')->assertSuccessful();
    expect(ImportantMaterial::where('button_name', 'Daftar Periksa Pengajuan')->exists())->toBeTrue();
});

it('template matriks terbaca utuh oleh pembaca Word saat bentuk "Matriks" dipilih', function () {
    foreach (['Peraturan Bupati' => 'matriksPerbup', 'Keputusan Bupati' => 'matriksKepbup'] as $jenis => $metode) {
        $path = BahanPentingBawaan::buat($metode);
        $hasil = app(PembacaNaskahWord::class)->baca($path, $jenis, PembacaNaskahWord::BENTUK_MATRIKS);
        @unlink($path);

        $bagian = collect($hasil['baris'])->pluck('bagian')->unique()->values()->all();
        expect($hasil['peringatan'])->toBe([])
            ->and($bagian)->toContain('judul', 'menimbang', 'mengingat', 'diktum', 'penutup');

        if ($jenis === 'Peraturan Bupati') {
            expect(collect($hasil['baris'])->where('bagian', 'batang_tubuh')->pluck('label')->all())
                ->toBe(['BAB I', 'Pasal 1', 'BAB II', 'Pasal 2', 'BAB III', 'Pasal 3']);
        } else {
            expect(collect($hasil['baris'])->where('bagian', 'diktum'))->toHaveCount(4);
        }
    }
});

it('Perangkat Daerah melihat dan bisa mengunduh berkas bawaan dari tombol Bahan Penting', function () {
    $this->artisan('bahan-penting:bawaan')->assertSuccessful();
    $pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]))->assignRole('Perangkat Daerah');
    $matriks = ImportantMaterial::where('button_name', 'Template Matriks Peraturan Bupati')->firstOrFail();

    Livewire::actingAs($pd)->test(BahanPentingDropdown::class)
        ->assertSee('Template Matriks Peraturan Bupati')
        ->assertSee('Contoh Nota Dinas Pengajuan')
        ->call('download', $matriks->id)
        ->assertFileDownloaded('Template Matriks Peraturan Bupati.docx');
});
