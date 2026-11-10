<?php

use App\Enums\TahapNaskah as T;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;
use App\Models\TandaTangan;
use App\Models\User;
use App\Services\CetakNaskah;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Database\Seeders\RoleSeeder;
use Database\Seeders\V2RancanganPdSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $perangkat = PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Uji']);
    $this->pd = tap(User::factory()->create(['NIP' => 'perangkatdaerah', 'perangkat_daerah_id' => $perangkat->id]))->assignRole('Perangkat Daerah');

    foreach (['admin' => 'Admin', 'verifikator' => 'Verifikator', 'peneliti' => 'Peneliti'] as $nip => $peran) {
        tap(User::factory()->create(['NIP' => $nip]))->assignRole($peran);
    }

    TandaTangan::create([
        'nama_ttd' => 'NAMA KABAG', 'nip_ttd' => '19731002', 'jabatan' => 'Pembina Tk.I',
        'file_ttd' => 'tanda_tangan/ttd/x.png', 'dibuat_oleh' => User::where('NIP', 'verifikator')->value('id'), 'status' => 'Aktif',
    ]);
});

it('membuat rancangan milik perangkatdaerah di berbagai tahap dan aman dijalankan ulang', function () {
    $this->seed(V2RancanganPdSeeder::class);
    $this->seed(V2RancanganPdSeeder::class);

    $tahap = Naskah::where('pemrakarsa_id', $this->pd->id)->pluck('tahap', 'tentang');

    expect($tahap)->toHaveCount(7)
        ->and($tahap['Pembentukan Tim Koordinasi Penanggulangan Kemiskinan Daerah'])->toBe(T::Draf)
        ->and($tahap['Penyelenggaraan Perpustakaan Desa'])->toBe(T::Draf)
        ->and($tahap['Penetapan Desa Sadar Hukum'])->toBe(T::PerbaikanPd)
        ->and($tahap['Pembentukan Kelompok Kerja Operasional Posyandu'])->toBe(T::Penelitian)
        ->and($tahap['Standar Pelayanan pada Mal Pelayanan Publik'])->toBe(T::Penelitian)
        ->and($tahap['Pedoman Pengelolaan Arsip Dinamis'])->toBe(T::ValidasiRancangan)
        ->and($tahap['Pedoman Penyelenggaraan Forum Anak Daerah'])->toBe(T::Penandatanganan)
        ->and(Naskah::where('jenis', 'Keputusan Bupati')->count())->toBe(3)
        ->and(Naskah::where('jenis', 'Peraturan Bupati')->count())->toBe(4);
});

it('memperlihatkan format baru pada isi naskah', function () {
    $this->seed(V2RancanganPdSeeder::class);

    $keputusan = Naskah::where('tentang', 'Pembentukan Tim Koordinasi Penanggulangan Kemiskinan Daerah')->firstOrFail();
    $blok = collect(TataLetakNaskah::susun($keputusan, $keputusan->baris()->get()));
    $diktum = $blok->firstWhere('tipe', 'diktum');

    expect($diktum['pembuka'])->toBe('Menetapkan')
        ->and(collect($diktum['butir'][0]['baris'])->contains(fn ($b) => isset($b['tabel'])))->toBeTrue()
        ->and(collect($diktum['butir'][1]['baris'])->pluck('nomor')->filter()->values()->all())->toBe(['a.', 'b.', '1.', '2.', 'c.'])
        ->and($blok->firstWhere('tipe', 'penutup')['tembusan'][0])->toBe('Tembusan :')
        ->and($keputusan->baris()->where('bagian', 'judul')->value('rumusan_awal'))->toContain('NOMOR .../.../.../TAHUN ...');

    // Satu pertimbangan dicetak tanpa huruf.
    $satu = Naskah::where('tentang', 'Penetapan Desa Sadar Hukum')->firstOrFail();
    $menimbang = $satu->baris()->where('bagian', 'menimbang')->first();
    expect(TemplateNaskah::penanda($satu, $satu->baris()->get())[$menimbang->id])->toBeNull();

    // Peneliti mengubah, menghapus, dan menyisipkan sehingga perbandingan terlihat.
    $diteliti = Naskah::where('tentang', 'Standar Pelayanan pada Mal Pelayanan Publik')->firstOrFail();
    expect($diteliti->baris()->where('status', B::DIUBAH)->count())->toBe(2)
        ->and($diteliti->baris()->where('status', B::DIHAPUS)->count())->toBe(1)
        ->and($diteliti->baris()->where('bagian', 'mengingat')->get()->last()->teksAkhir())->toStartWith('Peraturan Presiden Nomor 89');
});

it('semua rancangan bisa dibuka dan dicetak', function () {
    $this->seed(V2RancanganPdSeeder::class);
    $cetak = app(CetakNaskah::class);

    foreach (Naskah::all() as $naskah) {
        $this->actingAs($this->pd)->get(route('v2.naskah.kerja', $naskah))->assertOk()->assertSee($naskah->jenis);

        $word = $cetak->wordNaskah($naskah);
        expect(filesize($word))->toBeGreaterThan(1000);
        unlink($word);

        expect($cetak->pdfNaskah($naskah))->toStartWith('%PDF');
    }
});

it('tabel Word tidak terbelah antarhalaman dan kolomnya mengikuti isi', function () {
    $this->seed(V2RancanganPdSeeder::class);
    $naskah = Naskah::where('tentang', 'Pembentukan Tim Koordinasi Penanggulangan Kemiskinan Daerah')->firstOrFail();

    $word = app(CetakNaskah::class)->wordNaskah($naskah);
    $zip = new ZipArchive;
    $zip->open($word);
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    unlink($word);

    // Tata letak konsiderans dan diktum juga berupa tabel; tabel isi (No, Jabatan, Kedudukan)
    // adalah satu-satunya yang barisnya dilarang terbelah.
    // Tabel bisa bersarang di sel diktum, jadi tiap tblGrid dibaca bersama baris yang langsung mengikutinya.
    $kolom = collect(array_slice(explode('<w:tblGrid>', $xml), 1))
        ->filter(fn (string $isi) => str_contains(preg_split('/<\/w:tbl>|<w:tbl>/', $isi)[0], '<w:cantSplit'))
        ->map(function (string $isi) {
            preg_match_all('/w:w="(\d+)"/', strstr($isi, '</w:tblGrid>', true), $lebar);

            return array_map('intval', $lebar[1]);
        })
        ->first(fn (array $lebar) => count($lebar) === 3);

    expect($xml)->toContain('<w:cantSplit')
        ->and($kolom)->not->toBeNull()
        ->and($kolom[0])->toBeLessThan($kolom[1]);
});

it('migrasi mengganti jenis lama pada naskah v2 saja', function () {
    $lama = Naskah::create(['jenis' => 'Keputusan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $this->pd->id, 'nomor_nota' => 'ND-1', 'tanggal_nota' => now()]);
    DB::table('naskah')->where('id', $lama->id)->update(['jenis' => 'Surat Keputusan']);
    $rancanganLama = RancanganProdukHukum::factory()->create(['id_user' => $this->pd->id, 'jenis_rancangan' => 'Surat Keputusan']);

    (require database_path('migrations/2026_11_05_000000_ganti_jenis_naskah_keputusan_bupati.php'))->up();

    expect($lama->refresh()->jenis)->toBe('Keputusan Bupati')
        ->and($rancanganLama->refresh()->jenis_rancangan)->toBe('Surat Keputusan');
});
