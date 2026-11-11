<?php

use App\Exports\DokumentasiExport;
use App\Exports\SingleJenisExport;
use App\Livewire\Dokumentasi\AjukanDokumentasiProdukHukum;
use App\Livewire\Dokumentasi\Dokumentasi;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;
use App\Models\User;
use App\Support\Status;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = tap(User::factory()->create())->assignRole('Admin');

    $pd = PerangkatDaerah::factory()->create();

    $this->arsip = fn (int $nomor, string $jenis, string $tentang) => DokumentasiProdukHukum::create([
        'nomor' => $nomor,
        'tahun' => 2025,
        'jenis_dokumentasi' => $jenis,
        'tentang_dokumentasi' => $tentang,
        'tanggal_pengarsipan' => '2025-06-01',
        'tanggal_penetapan' => '2025-05-20',
        'nomor_tahun_berita' => '10/2025',
        'perangkat_daerah_id' => $pd->id,
        'file_produk_hukum' => 'contoh.pdf',
    ]);
});

it('hanya mengganti nama lama, nilai lain dibiarkan', function () {
    expect(Status::label('Surat Keputusan'))->toBe('Keputusan Bupati')
        ->and(Status::label('Peraturan Bupati'))->toBe('Peraturan Bupati')
        ->and(Status::label('Dalam Proses'))->toBe('Dalam Proses')
        ->and(Status::label(null))->toBeNull();
});

it('kedua nama memakai warna badge yang sama', function () {
    expect(Status::color('Surat Keputusan'))->toBe(Status::color('Keputusan Bupati'))->toBe('amber');
});

it('badge menampilkan nama resmi', function () {
    $html = Blade::render('<x-ui.status-badge value="Surat Keputusan" />');

    expect($html)->toContain('Keputusan Bupati')->not->toContain('Surat Keputusan');
});

it('Dokumentasi hanya menawarkan satu pilihan dan filternya mencakup kedua nama', function () {
    ($this->arsip)(1, 'Surat Keputusan', 'Arsip Nama Lama');
    ($this->arsip)(2, 'Keputusan Bupati', 'Arsip Nama Baru');
    ($this->arsip)(3, 'Peraturan Bupati', 'Arsip Peraturan');

    Livewire::actingAs($this->admin)->test(Dokumentasi::class)
        ->assertViewHas('jenisOptions', fn ($opsi) => $opsi->all() === ['Keputusan Bupati', 'Peraturan Bupati'])
        ->assertDontSee('Surat Keputusan')
        ->set('jenisRancangan', 'Keputusan Bupati')
        ->assertSee('Arsip Nama Lama')
        ->assertSee('Arsip Nama Baru')
        ->assertDontSee('Arsip Peraturan');
});

it('berkas unduhan JDIH memakai nama resmi', function () {
    Storage::fake('local');
    Storage::disk('local')->put('dokumentasi/file_produk_hukum/contoh.pdf', '%PDF-1.4 contoh');

    $arsip = ($this->arsip)(5, 'Surat Keputusan', 'Arsip Nama Lama');
    $arsip->update(['file_produk_hukum' => 'dokumentasi/file_produk_hukum/contoh.pdf']);

    expect($this->get("/produk-hukum/{$arsip->id}/dokumen?unduh=1")->headers->get('Content-Disposition'))
        ->toContain('keputusan-bupati-nomor-5-tahun-2025.pdf');
});

describe('data dari alur sebelumnya', function () {
    beforeEach(function () {
        $pd = User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]);
        $this->rancangan = RancanganProdukHukum::factory()->create(['id_user' => $pd->id, 'jenis_rancangan' => 'Surat Keputusan']);
        $this->fasilitasi = FasilitasiProdukHukum::create([
            'rancangan_id' => $this->rancangan->id_rancangan, 'tanggal_fasilitasi' => now(), 'file_rancangan' => 'x.pdf',
            'status_berkas_fasilitasi' => 'Disetujui', 'status_validasi_fasilitasi' => 'Diterima',
        ]);
    });

    it('arsip baru dari rancangan tersimpan dengan nama resmi', function () {
        Storage::fake('local');
        Notification::fake();

        Livewire::actingAs($this->admin)->test(AjukanDokumentasiProdukHukum::class)
            ->set('rancanganId', $this->fasilitasi->id)
            ->set('nomor', '77')
            ->set('tanggal_penetapan', '2026-01-10')
            ->set('file_produk_hukum', UploadedFile::fake()->create('arsip.pdf', 10, 'application/pdf'))
            ->call('store')
            ->assertHasNoErrors();

        $arsip = DokumentasiProdukHukum::where('nomor', 77)->firstOrFail();

        expect($arsip->jenis_dokumentasi)->toBe('Keputusan Bupati')
            ->and($arsip->file_produk_hukum)->toContain('Keputusan_Bupati_Nomor_77')
            ->and($this->rancangan->refresh()->jenis_rancangan)->toBe('Surat Keputusan');
    });
});

it('ekspor Excel memakai nama resmi tanpa mengubah isi database', function () {
    $arsip = ($this->arsip)(1, 'Surat Keputusan', 'Arsip Nama Lama');
    RancanganProdukHukum::factory()->create(['id_user' => $this->admin->id, 'jenis_rancangan' => 'Surat Keputusan', 'tanggal_pengajuan' => '2025-03-01']);

    $dokumentasi = (new DokumentasiExport('', 'Keputusan Bupati', ''))->collection();
    $masterData = new SingleJenisExport('2025', 'Surat Keputusan');

    expect($dokumentasi->pluck('Jenis Produk Hukum')->all())->toBe(['Keputusan Bupati'])
        ->and($masterData->title())->toBe('Data Keputusan Bupati')
        ->and($masterData->collection()->first()['Jenis'])->toBe('Keputusan Bupati')
        ->and($arsip->refresh()->jenis_dokumentasi)->toBe('Surat Keputusan')
        ->and(RancanganProdukHukum::first()->jenis_rancangan)->toBe('Surat Keputusan');
});
