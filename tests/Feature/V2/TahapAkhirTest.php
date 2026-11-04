<?php

use App\Enums\TahapNaskah as T;
use App\Livewire\V2\BarisKerja;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\Lonceng;
use App\Livewire\Verifikator\TandaTangan\PengelolaTtd;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NotaDinas;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;
use App\Models\TandaTangan;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\CetakNaskah;
use App\Services\PembacaNaskahWord;
use App\Support\PemeriksaPenomoran;
use App\Support\TataLetakNaskah;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->alur = app(AlurNaskah::class);
    $this->perangkat = PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Uji']);
    $this->pd = tap(User::factory()->create(['perangkat_daerah_id' => $this->perangkat->id]))->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create())->assignRole('Peneliti');
    $this->ttd = TandaTangan::create([
        'nama_ttd' => 'NAMA KABAG', 'nip_ttd' => '19731002', 'jabatan' => 'Pembina Tk.I',
        'file_ttd' => 'tanda_tangan/ttd/x.png', 'dibuat_oleh' => $this->verifikator->id, 'status' => 'Aktif',
    ]);
});

/** Naskah yang sudah melewati validasi fasilitasi, siap diterbitkan nota dinasnya. */
function naskahFinal(object $uji, string $jenis = 'Peraturan Bupati', string $tentang = 'Dana Desa'): Naskah
{
    $alur = $uji->alur;
    $n = Naskah::create(['jenis' => $jenis, 'tentang' => $tentang, 'pemrakarsa_id' => $uji->pd->id, 'nomor_nota' => 'ND-'.uniqid(), 'tanggal_nota' => now()]);
    $alur->isiBaris($n, [
        ['bagian' => 'judul', 'label' => null, 'teks' => 'JUDUL'],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => 'Isi pasal.'],
    ]);
    $n->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => 'x.pdf', 'nama_asli' => 'x.pdf', 'diunggah_oleh' => $uji->pd->id]);

    $alur->jalankan($n, $uji->pd, 'ajukan');
    $alur->jalankan($n, $uji->admin, 'setujui_berkas');
    $alur->jalankan($n, $uji->verifikator, 'tugaskan_peneliti', null, $uji->peneliti->id);
    $n->baris()->get()->each(fn (B $b) => $alur->tetapkan($b, $uji->peneliti));
    $alur->jalankan($n, $uji->peneliti, 'teruskan_ke_verifikator');
    $alur->jalankan($n, $uji->verifikator, 'setujui_rancangan');
    $alur->jalankan($n, $uji->pd, 'ajukan_fasilitasi');
    $alur->jalankan($n, $uji->admin, 'setujui_fasilitasi');

    return $alur->jalankan($n, $uji->verifikator, 'finalkan');
}

function teksPdfAkhir(string $pdf): string
{
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, $pdf);
    $teks = (new Smalot\PdfParser\Parser())->parseFile($path)->getText();
    unlink($path);

    return preg_replace('/\s+/u', '', $teks);
}

describe('nota dinas', function () {
    it('terbit dengan nomor sesuai format, lalu masuk tahap penandatanganan', function () {
        $n = naskahFinal($this);
        expect($n->tahap)->toBe(T::Final)->and($n->terkunci_at)->not->toBeNull();

        $n = $this->alur->terbitkanNota($n, $this->admin, '12', 'Sekretaris Daerah', $this->ttd->id);

        expect($n->tahap)->toBe(T::Penandatanganan)
            ->and($n->notaDinas->nomor_nota)->toBe('180/12/NDPB/KUM/' . now()->year);
    });

    it('nomor Surat Keputusan memakai kode NDSK', function () {
        $n = $this->alur->terbitkanNota(naskahFinal($this, 'Surat Keputusan'), $this->verifikator, '3', 'Sekda', $this->ttd->id);

        expect($n->notaDinas->nomor_nota)->toBe('180/3/NDSK/KUM/' . now()->year);
    });

    it('menolak nomor ganda, termasuk "012" dan "12", di jenis yang sama', function () {
        $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        $kedua = naskahFinal($this);

        expect(fn () => $this->alur->terbitkanNota($kedua, $this->admin, '012', 'Sekda', $this->ttd->id))->toThrow(ValidationException::class);

        // Jenis berbeda boleh memakai nomor yang sama.
        $sk = $this->alur->terbitkanNota(naskahFinal($this, 'Surat Keputusan'), $this->admin, '12', 'Sekda', $this->ttd->id);
        expect($sk->tahap)->toBe(T::Penandatanganan);
    });

    it('menolak nomor yang sudah dipakai nota dinas alur lama', function () {
        $rancangan = RancanganProdukHukum::factory()->create(['id_user' => $this->pd->id, 'jenis_rancangan' => 'Peraturan Bupati']);
        $fasilitasi = FasilitasiProdukHukum::create(['rancangan_id' => $rancangan->id_rancangan, 'tanggal_fasilitasi' => now(), 'file_rancangan' => 'x.pdf', 'status_berkas_fasilitasi' => 'Disetujui', 'status_validasi_fasilitasi' => 'Diterima']);
        NotaDinas::create(['fasilitasi_id' => $fasilitasi->id, 'nomor_inputan' => '20', 'nomor_nota' => 'x', 'tanggal_nota' => now(), 'tanda_tangan_id' => $this->ttd->id, 'kepada' => 'Sekda']);

        expect(fn () => $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '20', 'Sekda', $this->ttd->id))->toThrow(ValidationException::class);
    });

    it('isian wajib dan hak akses diperiksa', function () {
        $n = naskahFinal($this);

        expect(fn () => $this->alur->terbitkanNota($n, $this->admin, 'abc', 'Sekda', $this->ttd->id))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->terbitkanNota($n, $this->admin, '1', '', $this->ttd->id))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->terbitkanNota($n, $this->admin, '1', 'Sekda', null))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->terbitkanNota($n, $this->pd, '1', 'Sekda', $this->ttd->id))->toThrow(HttpException::class);
        expect(fn () => $this->alur->jalankan($n, $this->admin, 'terbitkan_nota'))->toThrow(HttpException::class);
    });

    it('matriks draf sebelum nota terbit, lalu memuat nomor dan penandatangan sesudahnya', function () {
        $n = naskahFinal($this);
        $cetak = app(CetakNaskah::class);

        expect(teksPdfAkhir($cetak->pdfMatriks($n)))->toContain('DRAF')->not->toContain('NAMAKABAG');

        $n = $this->alur->terbitkanNota($n, $this->admin, '12', 'Sekda', $this->ttd->id);
        $matriks = teksPdfAkhir($cetak->pdfMatriks($n->fresh()));

        expect($matriks)->not->toContain('DRAF')->toContain('180/12/NDPB/KUM')->toContain('NAMAKABAG')->toContain('19731002');
    });

    it('nota dinas PDF memuat data naskah dan bisa diunduh yang berhak', function () {
        $n = $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        $teks = teksPdfAkhir(app(CetakNaskah::class)->pdfNotaDinas($n));

        expect($teks)->toContain('180/12/NDPB/KUM')->toContain('DanaDesa')->toContain('DinasUji')->toContain('NAMAKABAG');

        $this->actingAs($this->pd)->get(route('v2.naskah.cetak', [$n, 'nota-dinas']))->assertOk()->assertDownload();
        $this->actingAs(tap(User::factory()->create())->assignRole('Perangkat Daerah'))->get(route('v2.naskah.cetak', [$n, 'nota-dinas']))->assertForbidden();
    });

    it('tanda tangan yang dipakai nota dinas v2 tidak bisa dihapus', function () {
        $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        $this->actingAs($this->verifikator);

        Livewire::test(PengelolaTtd::class)->call('delete', $this->ttd->id);

        expect(TandaTangan::find($this->ttd->id))->not->toBeNull();
    });
});

describe('penandatanganan', function () {
    beforeEach(function () {
        $this->n = $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
    });

    it('tercatat berurutan lalu menuju arsip', function () {
        $n = $this->n;
        expect($this->alur->aksiTersedia($n, $this->admin)['tandai_tahapan']['label'])->toBe('Tandai Paraf Koordinasi selesai');

        $n = $this->alur->tandaiTahapan($n, $this->admin, now()->subDay()->toDateString());
        expect($this->alur->aksiTersedia($n, $this->admin)['tandai_tahapan']['label'])->toBe('Tandai Asisten selesai')
            ->and($n->tahapan_ttd['paraf_koordinasi'])->toBe(now()->subDay()->toDateString());

        $n = $this->alur->tandaiTahapan($n, $this->admin);
        $n = $this->alur->tandaiTahapan($n, $this->admin);
        expect($n->tahap)->toBe(T::Penandatanganan);

        $n = $this->alur->tandaiTahapan($n, $this->admin);
        expect($n->tahap)->toBe(T::Arsip)->and($n->tahapan_ttd)->toHaveKeys(array_keys(AlurNaskah::TAHAPAN_TTD));
    });

    it('menolak tanggal masa depan dan pengguna tanpa hak', function () {
        expect(fn () => $this->alur->tandaiTahapan($this->n, $this->admin, now()->addDay()->toDateString()))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->tandaiTahapan($this->n, $this->pd))->toThrow(HttpException::class);
    });

    it('buka kunci mengulang tanda tangan tanpa menerbitkan nota dinas baru', function () {
        $n = $this->alur->tandaiTahapan($this->n, $this->admin);

        expect(fn () => $this->alur->jalankan($n, $this->verifikator, 'buka_kunci_ttd'))->toThrow(ValidationException::class);

        $n = $this->alur->jalankan($n, $this->verifikator, 'buka_kunci_ttd', 'Koreksi dari Sekda');
        expect($n->tahap)->toBe(T::ValidasiFasilitasi)->and($n->tahapan_ttd)->toBeNull()->and($n->terkunci_at)->toBeNull();

        // Selama dibuka, matriks kembali berstatus draf.
        expect(teksPdfAkhir(app(CetakNaskah::class)->pdfMatriks($n->fresh())))->toContain('DRAF');

        $n = $this->alur->jalankan($n, $this->verifikator, 'finalkan');
        expect($n->tahap)->toBe(T::Penandatanganan)->and($n->notaDinas()->count())->toBe(1);
    });
});

describe('arsip ke JDIH', function () {
    beforeEach(function () {
        $n = $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        foreach (AlurNaskah::TAHAPAN_TTD as $_) {
            $n = $this->alur->tandaiTahapan($n, $this->admin);
        }
        $this->n = $n;
        $this->pdf = UploadedFile::fake()->create('ttd.pdf', 10, 'application/pdf');
    });

    it('membuat dokumentasi yang tampil di JDIH dan menyelesaikan naskah', function () {
        $n = $this->alur->arsipkan($this->n, $this->admin, '7', '2026-11-01', '7', '2026', $this->pdf);

        $d = DokumentasiProdukHukum::findOrFail($n->dokumentasi_id);
        expect($n->tahap)->toBe(T::Selesai)
            ->and($d->jenis_dokumentasi)->toBe('Peraturan Bupati')
            ->and($d->tentang_dokumentasi)->toBe('Dana Desa')
            ->and((int) $d->tahun)->toBe(2026)
            ->and($d->nomor_tahun_berita)->toBe('7/2026')
            ->and($d->perangkat_daerah_id)->toBe($this->perangkat->id);
        Storage::disk('local')->assertExists($d->file_produk_hukum);

        $this->get(route('jdih.show', $d))->assertOk()->assertSee('Dana Desa');
    });

    it('menolak nomor yang sudah ada pada tahun yang sama', function () {
        DokumentasiProdukHukum::create(['nomor' => '7', 'tahun' => 2026, 'tanggal_pengarsipan' => now(), 'perangkat_daerah_id' => $this->perangkat->id, 'jenis_dokumentasi' => 'Peraturan Bupati', 'tentang_dokumentasi' => 'x']);

        expect(fn () => $this->alur->arsipkan($this->n, $this->admin, '7', '2026-11-01', null, null, $this->pdf))->toThrow(ValidationException::class);
        expect($this->n->fresh()->tahap)->toBe(T::Arsip);
    });

    it('PD tidak bisa mengarsipkan', function () {
        expect(fn () => $this->alur->arsipkan($this->n, $this->pd, '7', '2026-11-01', null, null, $this->pdf))->toThrow(HttpException::class);
    });

    it('naskah selesai tidak bisa diubah dan tidak menunggu siapa pun', function () {
        $n = $this->alur->arsipkan($this->n, $this->admin, '7', '2026-11-01', null, null, $this->pdf);

        expect(Naskah::menunggu($this->admin)->whereKey($n->id)->exists())->toBeFalse()
            ->and($this->alur->aksiTersedia($n, $this->admin))->toBe([]);
        expect(fn () => $this->alur->tetapkan($n->baris()->first(), $this->verifikator))->toThrow(HttpException::class);
    });

    it('seluruh tahap akhir berjalan lewat layar', function () {
        $this->actingAs($this->admin);

        Livewire::test(LayarKerja::class, ['naskah' => $this->n])
            ->call('pilihAksi', 'arsipkan')
            ->set('nomorArsip', '8')
            ->set('tanggalPenetapan', '2026-11-01')
            ->call('jalankanAksi')
            ->assertHasErrors('fileArsip')
            ->set('fileArsip', UploadedFile::fake()->create('ttd.pdf', 10, 'application/pdf'))
            ->call('jalankanAksi')
            ->assertHasNoErrors();

        expect($this->n->fresh()->tahap)->toBe(T::Selesai);
    });
});

it('layar menerbitkan nota dinas dan mencatat tahapan', function () {
    $n = naskahFinal($this);
    $this->actingAs($this->admin);

    $layar = Livewire::test(LayarKerja::class, ['naskah' => $n])
        ->call('pilihAksi', 'terbitkan_nota')
        ->call('jalankanAksi')
        ->assertHasErrors('nomorInputan')
        ->set('nomorInputan', '15')->set('kepada', 'Sekda')->set('tandaTanganId', $this->ttd->id)
        ->call('jalankanAksi')
        ->assertHasNoErrors();

    expect($n->fresh()->tahap)->toBe(T::Penandatanganan);

    $layar->call('pilihAksi', 'tandai_tahapan')->assertSet('tanggalTahapan', now()->toDateString())->call('jalankanAksi')->assertHasNoErrors();

    expect($n->fresh()->tahapan_ttd)->toHaveKey('paraf_koordinasi');
});

describe('kelengkapan layar', function () {
    it('lonceng menampilkan notifikasi sendiri dan menuju naskahnya', function () {
        $n = naskahFinal($this);
        $this->actingAs($this->pd);
        $notif = $this->pd->notifications()->latest()->first();

        Livewire::test(Lonceng::class)->assertSee($n->nomor)->call('buka', $notif->id)->assertRedirect(route('v2.naskah.kerja', $n));
        expect($notif->fresh()->read_at)->not->toBeNull();

        $this->actingAs(tap(User::factory()->create())->assignRole('Perangkat Daerah'));
        Livewire::test(Lonceng::class)->call('buka', $this->pd->notifications()->first()->id)->assertNotFound();
    });

    it('draf bisa dibuang pemiliknya saja, dan hanya saat draf', function () {
        $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'x', 'pemrakarsa_id' => $this->pd->id]);
        Storage::disk('local')->put("naskah/{$n->slug}/a.pdf", 'x');

        expect(fn () => $this->alur->buangDraf($n, $this->admin))->toThrow(HttpException::class);

        $this->alur->buangDraf($n, $this->pd);
        expect(Naskah::find($n->id))->toBeNull();
        Storage::disk('local')->assertMissing("naskah/{$n->slug}/a.pdf");

        expect(fn () => $this->alur->buangDraf(naskahFinal($this), $this->pd))->toThrow(HttpException::class);
    });

    it('versi lama bisa dibuka, tapi hanya versi naskah itu', function () {
        $n = naskahFinal($this);
        $lain = naskahFinal($this);
        $this->actingAs($this->admin);

        Livewire::test(LayarKerja::class, ['naskah' => $n])
            ->set('tab', 'riwayat')
            ->call('lihatVersi', $n->versi()->first()->id)
            ->assertSee('Isi pasal.');

        Livewire::test(LayarKerja::class, ['naskah' => $n])->call('lihatVersi', $lain->versi()->first()->id)->assertNotFound();
    });

    it('template Word bisa diunduh dan terbaca kembali', function () {
        $this->actingAs($this->pd)->get(route('v2.template', 'peraturan-bupati'))->assertOk()->assertDownload('Template Peraturan Bupati.docx');
        $this->actingAs($this->pd)->get(route('v2.template', 'surat-keputusan'))->assertOk();
        $this->actingAs($this->pd)->get('/v2/template/lain')->assertNotFound();

        foreach (['Peraturan Bupati', 'Surat Keputusan'] as $jenis) {
            $path = app(CetakNaskah::class)->wordTemplate($jenis);
            rename($path, $salinan = $path . '.docx');
            $hasil = (new PembacaNaskahWord())->baca($salinan, $jenis);
            unlink($salinan);

            expect($hasil['ringkasan'])->toHaveKeys(['judul', 'menimbang', 'mengingat', 'diktum', 'penutup', 'lampiran']);
        }
    });

    it('rapikan nomor tersedia dari layar', function () {
        $n = naskahFinal($this);
        $this->actingAs($this->admin);

        Livewire::test(BarisKerja::class, ['baris' => $n->baris()->first(), 'naskah' => $n])
            ->call('rapikanNomor', "(3) satu\n(7) dua")
            ->assertReturned("(1) satu\n(2) dua");
    });
});

describe('penomoran dan tabel', function () {
    it('merapikan nomor ayat dan butir tanpa menyentuh rujukan', function () {
        $hasil = TataLetakNaskah::rapikanNomor("(2) Sesuai ayat (9):\nb. satu;\nd. dua.\n(5) Lanjut:\n1. x;\n4. y.\nKalimat biasa:\nc. baru.");

        expect($hasil)->toBe("(1) Sesuai ayat (9):\na. satu;\nb. dua.\n(2) Lanjut:\n1. x;\n2. y.\nKalimat biasa:\na. baru.");
    });

    it('mengenali tabel di isi pasal dan membuangnya dari pemisah', function () {
        $baris = TataLetakNaskah::barisTeks("Besaran:\n| Jabatan | Nilai |\n| --- | --- |\n| Ketua | Rp1 |\n(1) Lanjut.");

        expect($baris[1]['tabel'])->toBe([['Jabatan', 'Nilai'], ['Ketua', 'Rp1']])
            ->and($baris[2]['nomor'])->toBe('(1)');
    });

    it('tabel tercetak di Word dan PDF', function () {
        $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'x', 'pemrakarsa_id' => $this->pd->id]);
        $this->alur->isiBaris($n, [['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => "| Jabatan | Nilai |\n| Ketua & Wakil | Rp1 |"]]);
        $cetak = app(CetakNaskah::class);

        expect(teksPdfAkhir($cetak->pdfNaskah($n)))->toContain('Ketua&Wakil');

        $path = $cetak->wordNaskah($n);
        $zip = new ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        expect($xml)->toContain('Ketua &amp; Wakil')->toContain('w:tblBorders');
    });

    it('pembaca Word mempertahankan tabel data tetapi tidak tabel tata letak', function () {
        $word = new PhpWord();
        $bagian = $word->addSection();

        $letak = $bagian->addTable();
        $r = $letak->addRow();
        foreach (['Menimbang', ':', 'a.', 'bahwa satu;'] as $isi) {
            $r->addCell(1000)->addText($isi);
        }

        $bagian->addText('Pasal 1');
        $bagian->addText('Besaran:');

        $data = $bagian->addTable();
        foreach ([['No', 'Jabatan', 'Nilai'], ['1', 'Ketua', 'Rp1']] as $sel) {
            $r = $data->addRow();
            foreach ($sel as $isi) {
                $r->addCell(1000)->addText($isi);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'naskah') . '.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);
        $hasil = collect((new PembacaNaskahWord())->baca($path, 'Peraturan Bupati')['baris']);
        unlink($path);

        expect($hasil->firstWhere('bagian', 'menimbang')['teks'])->toBe('bahwa satu;')
            ->and($hasil->firstWhere('label', 'Pasal 1')['teks'])->toContain("| No | Jabatan | Nilai |\n| 1 | Ketua | Rp1 |");
    });

    function barisPeriksa(array $data)
    {
        return collect($data)->map(fn ($d, $i) => (new B(['bagian' => 'batang_tubuh', 'label' => $d[0], 'rumusan_awal' => $d[1] ?? '']))
            ->forceFill(['id' => $i + 1, 'status' => $d[2] ?? B::BELUM]));
    }

    it('memperingatkan nomor melompat, ganda, dan mundur', function () {
        $pesan = array_column(PemeriksaPenomoran::periksa(barisPeriksa([
            ['BAB I'], ['Pasal 1'], ['Pasal 2'], ['Pasal 4'], ['Pasal 4'], ['Pasal 3'], ['BAB III'],
        ])), 'pesan');

        expect($pesan)->toContain('Nomor melompat dari Pasal 2 ke Pasal 4.', 'Pasal 4 tertulis dua kali.', 'Pasal 3 muncul setelah Pasal 4.', 'Nomor melompat dari BAB I ke BAB III.');
    });

    it('memperingatkan rujukan ke pasal yang tidak ada, tapi bukan rujukan ke peraturan lain', function () {
        $pesan = array_column(PemeriksaPenomoran::periksa(barisPeriksa([
            ['Pasal 1', 'Sebagaimana dimaksud dalam Pasal 2 ayat (1) dan Pasal 80 huruf a.'],
            ['Pasal 2', 'Berdasarkan Pasal 18 ayat (6) Undang-Undang Dasar dan Pasal 96 Peraturan Pemerintah.'],
        ])), 'pesan');

        expect($pesan)->toBe(['Pasal 1 merujuk ke Pasal 80, yang tidak ada di naskah ini.']);
    });

    it('pasal yang dihapus tidak dihitung ada', function () {
        $pesan = array_column(PemeriksaPenomoran::periksa(barisPeriksa([
            ['Pasal 1', 'Lihat Pasal 2.'], ['Pasal 2', 'Isi', B::DIHAPUS],
        ])), 'pesan');

        expect($pesan)->toBe(['Pasal 1 merujuk ke Pasal 2, yang tidak ada di naskah ini.']);
    });

    it('pasal sisipan 1A tidak dianggap lompat dan bisa dirujuk', function () {
        expect(PemeriksaPenomoran::periksa(barisPeriksa([
            ['Pasal 1', 'Lihat Pasal 1A.'], ['Pasal 1A', 'Isi'], ['Pasal 2', 'Isi'],
        ])))->toBe([]);
    });
});
