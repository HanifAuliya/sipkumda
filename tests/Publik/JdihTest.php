<?php

use App\Models\DokumentasiProdukHukum;
use App\Models\PerangkatDaerah;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Cache::flush();

    $pd = PerangkatDaerah::factory()->create(['nama_perangkat_daerah' => 'Dinas Lingkungan Hidup']);

    $buat = fn (array $atribut) => DokumentasiProdukHukum::create($atribut + [
        'tahun' => 2025,
        'tanggal_pengarsipan' => '2025-06-01',
        'tanggal_penetapan' => '2025-05-20',
        'nomor_tahun_berita' => '10/2025',
        'perangkat_daerah_id' => $pd->id,
        'file_produk_hukum' => 'dokumentasi/file_produk_hukum/contoh.pdf',
    ]);

    $this->sampah = $buat(['nomor' => 12, 'jenis_dokumentasi' => 'Peraturan Bupati', 'tentang_dokumentasi' => 'Pengelolaan Sampah Rumah Tangga']);
    $this->stunting = $buat(['nomor' => 7, 'jenis_dokumentasi' => 'Keputusan Bupati', 'tentang_dokumentasi' => 'Tim Percepatan Penurunan Stunting']);
    $this->lama = $buat(['nomor' => 12, 'tahun' => 2023, 'jenis_dokumentasi' => 'Peraturan Bupati', 'tentang_dokumentasi' => 'Tata Kelola Arsip Daerah']);

    Storage::disk('local')->put('dokumentasi/file_produk_hukum/contoh.pdf', '%PDF-1.4 contoh');
});

it('menampilkan beranda SIFKUMDA dengan cuplikan produk hukum', function () {
    $this->get('/')->assertOk()
        ->assertSee('Sistem Informasi')
        ->assertSee('Pengelolaan Sampah Rumah Tangga')
        ->assertSee(route('jdih.index'));
});

it('mencari berdasarkan kata kunci tentang', function () {
    $this->get('/produk-hukum?q=sampah')->assertOk()
        ->assertSee('Pengelolaan Sampah Rumah Tangga')
        ->assertDontSee('Tim Percepatan Penurunan Stunting');
});

it('mencari berdasarkan nomor dan tahun', function () {
    $this->get('/produk-hukum?q=' . urlencode('12/2023'))->assertOk()
        ->assertSee('Tata Kelola Arsip Daerah')
        ->assertDontSee('Pengelolaan Sampah Rumah Tangga');

    $this->get('/produk-hukum?q=' . urlencode('nomor 12'))->assertOk()
        ->assertSee('Tata Kelola Arsip Daerah')
        ->assertSee('Pengelolaan Sampah Rumah Tangga');
});

it('memfilter jenis dan tahun serta mengabaikan nilai yang tidak dikenal', function () {
    $this->get('/produk-hukum?jenis=Keputusan+Bupati')->assertOk()
        ->assertSee('Tim Percepatan Penurunan Stunting')
        ->assertDontSee('Pengelolaan Sampah Rumah Tangga');

    $this->get('/produk-hukum?tahun=2023')->assertOk()
        ->assertSee('Tata Kelola Arsip Daerah')
        ->assertDontSee('Tim Percepatan Penurunan Stunting');

    // Nilai liar diabaikan, bukan error.
    $this->get('/produk-hukum?jenis=%27%20OR%201=1&tahun=abc&urut=DROP&page=-5&q=' . str_repeat('x', 500))->assertOk();
    $this->get('/produk-hukum?q=%25%25%25_')->assertOk();
});

it('menyatukan "Surat Keputusan" dan "Keputusan Bupati" sebagai satu jenis', function () {
    DokumentasiProdukHukum::create([
        'nomor' => 30, 'tahun' => 2025, 'jenis_dokumentasi' => 'Surat Keputusan', 'tentang_dokumentasi' => 'Pembentukan Forum Kota Layak Anak',
        'tanggal_pengarsipan' => '2025-07-01', 'tanggal_penetapan' => '2025-06-25',
        'perangkat_daerah_id' => $this->stunting->perangkat_daerah_id,
    ]);

    $this->get('/produk-hukum?jenis=Keputusan+Bupati')->assertOk()
        ->assertSee('Pembentukan Forum Kota Layak Anak')
        ->assertSee('Tim Percepatan Penurunan Stunting');

    $this->get('/produk-hukum')->assertDontSee('>Surat Keputusan<', false);
});

it('menampilkan detail dan dokumen PDF', function () {
    $this->get("/produk-hukum/{$this->sampah->id}")->assertOk()
        ->assertSee('Peraturan Bupati Nomor 12 Tahun 2025')
        ->assertSee('Dinas Lingkungan Hidup')
        ->assertSee('application/ld+json', false);

    $this->get("/produk-hukum/{$this->sampah->id}/dokumen")->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect($this->get("/produk-hukum/{$this->sampah->id}/dokumen?unduh=1")->headers->get('Content-Disposition'))
        ->toStartWith('attachment')
        ->toContain('peraturan-bupati-nomor-12-tahun-2025.pdf');

    $this->get('/produk-hukum/999999')->assertNotFound();
});

it('tidak membuat sesi atau cookie dan boleh di-cache publik', function () {
    $sesiAwal = DB::table('sessions')->count();

    $respons = $this->get('/produk-hukum?q=sampah');

    $respons->assertOk();
    expect($respons->headers->getCookies())->toBeEmpty()
        ->and($respons->headers->get('Cache-Control'))->toContain('public')
        ->and(DB::table('sessions')->count())->toBe($sesiAwal);
});

it('memperbarui cache saat dokumentasi berubah', function () {
    $this->get('/produk-hukum')->assertSee('Pengelolaan Sampah Rumah Tangga');

    $this->sampah->update(['tentang_dokumentasi' => 'Pengelolaan Limbah Domestik']);

    $this->get('/produk-hukum')->assertSee('Pengelolaan Limbah Domestik')->assertDontSee('Pengelolaan Sampah Rumah Tangga');
});

it('membatasi jumlah pencarian per IP, tetapi tidak membatasi halaman yang ter-cache', function () {
    config(['jdih.batas_halaman' => 3]);

    foreach (range(1, 3) as $_) {
        $this->get('/produk-hukum?q=sampah')->assertOk();
    }
    $this->get('/produk-hukum?q=stunting')->assertStatus(429);

    // Tanpa kata kunci: tidak ada penghitung yang ditulis.
    $this->get('/produk-hukum')->assertOk()->assertHeaderMissing('X-RateLimit-Limit');
});

it('tidak membuka file privat lewat halaman publik', function () {
    Storage::disk('local')->put('rancangan/rancangan/rahasia.docx', 'rahasia');

    $this->get('/view-private/rancangan/rancangan/rahasia.docx')->assertRedirect('/login');
});
