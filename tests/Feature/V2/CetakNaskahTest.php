<?php

use App\Livewire\V2\PengaturanNaskah;
use App\Models\Naskah;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\CetakNaskah;
use App\Services\PembacaNaskahWord;
use App\Support\BandingNaskah;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    $this->alur = app(AlurNaskah::class);
});

function naskahCetak(User $pd, string $jenis = 'Peraturan Bupati', array $baris = []): Naskah
{
    $naskah = Naskah::create(['jenis' => $jenis, 'tentang' => 'Dana Desa', 'pemrakarsa_id' => $pd->id]);

    app(AlurNaskah::class)->isiBaris($naskah, $baris ?: [
        ['bagian' => 'judul', 'label' => null, 'teks' => "PERATURAN BUPATI HULU SUNGAI TENGAH\nNOMOR ... TAHUN ...\nTENTANG\nDANA DESA"],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa satu & dua <tiga>;'],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa empat;'],
        ['bagian' => 'mengingat', 'label' => null, 'teks' => 'Undang-Undang Nomor 6 Tahun 2014;'],
        ['bagian' => 'diktum', 'label' => null, 'teks' => "MEMUTUSKAN:\nMenetapkan: PERATURAN BUPATI TENTANG DANA DESA."],
        ['bagian' => 'batang_tubuh', 'label' => 'BAB I', 'teks' => 'KETENTUAN UMUM'],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => "Yang dimaksud dengan:\n1. Desa adalah satu.\n2. Dana adalah dua."],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 2', 'teks' => "(1) Ayat satu:\na. huruf a;\nb. huruf b.\n(2) Ayat dua."],
        ['bagian' => 'penutup', 'label' => null, 'teks' => TemplateNaskah::penutup($jenis)],
        ['bagian' => 'lampiran', 'label' => null, 'teks' => ''],
    ]);

    return $naskah;
}

describe('tata letak', function () {
    it('menyusun Peraturan Bupati sesuai bentuk naskah', function () {
        $n = naskahCetak($this->pd);
        $blok = collect(TataLetakNaskah::susun($n, $n->baris()->get()));
        $tengah = $blok->where('tipe', 'tengah')->pluck('baris')->flatten();

        expect($tengah)->toContain('DENGAN RAHMAT TUHAN YANG MAHA ESA', 'BUPATI HULU SUNGAI TENGAH,', 'MEMUTUSKAN:', 'BAB I', 'KETENTUAN UMUM', 'Pasal 1')
            ->and($blok->firstWhere('label', 'Menimbang')['butir'][1])->toBe(['nomor' => 'b.', 'isi' => 'bahwa empat;'])
            ->and($blok->firstWhere('label', 'Menetapkan')['butir'][0]['isi'])->toBe('PERATURAN BUPATI TENTANG DANA DESA.');
    });

    it('mengenali tingkat ayat dan butir', function () {
        expect(TataLetakNaskah::barisTeks("(1) Ayat:\na. huruf;\n1. angka.\n(2) Dua."))->toBe([
            ['tingkat' => 0, 'nomor' => '(1)', 'isi' => 'Ayat:'],
            ['tingkat' => 1, 'nomor' => 'a.', 'isi' => 'huruf;'],
            ['tingkat' => 2, 'nomor' => '1.', 'isi' => 'angka.'],
            ['tingkat' => 0, 'nomor' => '(2)', 'isi' => 'Dua.'],
        ])->and(TataLetakNaskah::barisTeks("Yang dimaksud:\n1. Desa."))->toBe([
            ['tingkat' => 0, 'nomor' => null, 'isi' => 'Yang dimaksud:'],
            ['tingkat' => 0, 'nomor' => '1.', 'isi' => 'Desa.'],
        ]);
    });

    it('Keputusan Bupati memakai KESATU dan tanpa kalimat pembuka', function () {
        $n = naskahCetak($this->pd, 'Keputusan Bupati', [
            ['bagian' => 'judul', 'label' => null, 'teks' => "KEPUTUSAN BUPATI\nTENTANG\nHIBAH"],
            ['bagian' => 'diktum', 'label' => null, 'teks' => 'Menetapkan penerima.'],
            ['bagian' => 'diktum', 'label' => null, 'teks' => 'Berlaku sejak ditetapkan.'],
            ['bagian' => 'penutup', 'label' => null, 'teks' => TemplateNaskah::penutup('Keputusan Bupati')],
        ]);
        $blok = collect(TataLetakNaskah::susun($n, $n->baris()->get()));

        expect($blok->where('tipe', 'tengah')->pluck('baris')->flatten())->toContain('MEMUTUSKAN :')->not->toContain('DENGAN RAHMAT TUHAN YANG MAHA ESA')
            ->and($blok->firstWhere('tipe', 'diktum')['pembuka'])->toBe('Menetapkan')
            ->and($blok->firstWhere('tipe', 'diktum')['butir'][1]['nomor'])->toBe('KEDUA')
            ->and($blok->firstWhere('tipe', 'diktum')['butir'][1]['baris'][0]['isi'])->toBe('Berlaku sejak ditetapkan.')
            ->and($blok->firstWhere('tipe', 'penutup')['diundangkan'])->toBe([]);
    });

    it('kop mengikuti produk hukum yang terbit: provinsi hanya pada Peraturan Bupati', function () {
        $perbup = naskahCetak($this->pd);
        $sk = naskahCetak($this->pd, 'Keputusan Bupati', [
            ['bagian' => 'judul', 'label' => null, 'teks' => "KEPUTUSAN BUPATI\nTENTANG\nHIBAH"],
            ['bagian' => 'diktum', 'label' => null, 'teks' => 'Menetapkan penerima.'],
        ]);

        expect(TataLetakNaskah::susun($perbup, $perbup->baris()->get())[0]['baris'][0])->toBe(TataLetakNaskah::PROVINSI)
            ->and(TataLetakNaskah::susun($sk, $sk->baris()->get())[0]['baris'])->not->toContain(TataLetakNaskah::PROVINSI);
    });

    it('rincian di dalam diktum menjadi butir dengan nomornya sendiri', function () {
        $n = naskahCetak($this->pd, 'Keputusan Bupati', [
            ['bagian' => 'diktum', 'label' => null, 'teks' => "Tugas Tim adalah:\na. menilai;\nb. melapor."],
        ]);
        $diktum = collect(TataLetakNaskah::susun($n, $n->baris()->get()))->firstWhere('tipe', 'diktum');

        expect($diktum['butir'][0]['baris'])->toBe([
            ['tingkat' => 0, 'nomor' => null, 'isi' => 'Tugas Tim adalah:'],
            ['tingkat' => 0, 'nomor' => 'a.', 'isi' => 'menilai;'],
            ['tingkat' => 0, 'nomor' => 'b.', 'isi' => 'melapor.'],
        ]);
    });

    it('pertimbangan tunggal ditulis tanpa huruf', function () {
        $n = naskahCetak($this->pd, 'Peraturan Bupati', [
            ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa satu;'],
        ]);
        $baris = $n->baris()->get();

        expect(TemplateNaskah::penanda($n, $baris)[$baris->first()->id])->toBeNull();

        $n->baris()->create(['bagian' => 'menimbang', 'urutan' => 2, 'rumusan_awal' => 'bahwa dua;', 'status' => 'belum']);
        $baris = $n->baris()->get();

        expect(array_values(TemplateNaskah::penanda($n, $baris)))->toBe(['a.', 'b.']);

        // Yang dihapus tidak ikut dihitung: sisanya tetap berurutan dari a.
        $n->baris()->create(['bagian' => 'menimbang', 'urutan' => 3, 'rumusan_awal' => 'bahwa tiga;', 'status' => 'belum']);
        $n->baris()->where('rumusan_awal', 'bahwa satu;')->update(['status' => 'dihapus']);

        expect(array_values(TemplateNaskah::penanda($n, $n->baris()->get())))->toBe([null, 'a.', 'b.']);
    });

    it('perubahan butir diktum tampil di perbandingan tanpa merusak tampilan', function () {
        $n = naskahCetak($this->pd, 'Keputusan Bupati', [
            ['bagian' => 'judul', 'label' => null, 'teks' => "KEPUTUSAN BUPATI\nTENTANG\nHIBAH"],
            ['bagian' => 'diktum', 'label' => null, 'teks' => "Tugas Tim adalah:\na. menilai;\nb. melapor."],
            ['bagian' => 'penutup', 'label' => null, 'teks' => TemplateNaskah::penutup('Keputusan Bupati')."\n\nTembusan :\n1. Camat"],
        ]);
        $n->baris()->where('bagian', 'diktum')->update([
            'rumusan_penyempurnaan' => "Tugas Tim adalah:\na. menilai;\nb. melapor kepada Bupati.",
            'status' => 'diubah',
        ]);

        $html = view('v2.partials.isi-naskah', [
            'blok' => BandingNaskah::susun($n, $n->baris()->get()),
            'tandaiUbahan' => true,
        ])->render();

        expect($html)->toContain('Menetapkan', 'KESATU', '<ins>', 'Tembusan :')
            ->and($html)->not->toContain('&lt;ins&gt;');
    });

    it('Word dan PDF memuat butir diktum, tabel diktum, dan tembusan', function () {
        $n = naskahCetak($this->pd, 'Keputusan Bupati', [
            ['bagian' => 'judul', 'label' => null, 'teks' => "KEPUTUSAN BUPATI\nNOMOR .../.../.../TAHUN ...\nTENTANG\nHIBAH"],
            ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa satu;'],
            ['bagian' => 'diktum', 'label' => null, 'teks' => "Tugas Tim adalah:\na. menilai;\n1. tahap satu;\nb. melapor."],
            ['bagian' => 'diktum', 'label' => null, 'teks' => "Biaya:\n| No | Uraian |\n| 1 | Rapat |"],
            ['bagian' => 'penutup', 'label' => null, 'teks' => TemplateNaskah::penutup('Keputusan Bupati')."\n\nTembusan :\n1. Camat"],
        ]);
        $cetak = app(CetakNaskah::class);

        $path = $cetak->wordNaskah($n);
        $zip = new ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $berkas = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i));
        $zip->close();
        unlink($path);

        expect($xml)->toContain('Menetapkan', 'tahap satu;', 'Rapat', 'Tembusan :')
            // Produk hukum yang terbit tidak bernomor halaman.
            ->and($berkas->filter(fn ($b) => str_contains($b, 'footer')))->toBeEmpty()
            ->and($cetak->pdfNaskah($n))->toStartWith('%PDF');
    });

    it('tembusan dipisahkan dari blok tanda tangan', function () {
        $penutup = TataLetakNaskah::penutup("Ditetapkan di Barabai\npada tanggal ...\nBUPATI HULU SUNGAI TENGAH,\n\nNAMA\n\nTembusan :\n1. Camat\n2. Yang Bersangkutan");

        expect($penutup['ditetapkan'])->toBe(['Ditetapkan di Barabai', 'pada tanggal ...', 'BUPATI HULU SUNGAI TENGAH,', '', 'NAMA'])
            ->and($penutup['tembusan'])->toBe(['Tembusan :', '1. Camat', '2. Yang Bersangkutan']);
    });

    it('tempat dan tanggal rata kiri, jabatan dan nama rata tengah', function () {
        expect(TataLetakNaskah::barisTempatTanggal('Ditetapkan di Barabai'))->toBeTrue()
            ->and(TataLetakNaskah::barisTempatTanggal('pada tanggal ...'))->toBeTrue()
            ->and(TataLetakNaskah::barisTempatTanggal(new Illuminate\Support\HtmlString('<ins>Diundangkan di Barabai</ins>')))->toBeTrue()
            ->and(TataLetakNaskah::barisTempatTanggal('BUPATI HULU SUNGAI TENGAH,'))->toBeFalse();
    });

    it('kerangka memuat nomor bergaris miring pada Keputusan dan kalimat wajib pada keduanya', function () {
        $sk = collect(TemplateNaskah::kerangka('Keputusan Bupati', 'Hibah'));
        $perbup = collect(TemplateNaskah::kerangka('Peraturan Bupati', 'Dana Desa'));

        expect($sk->firstWhere('bagian', 'judul')['teks'])->toContain('NOMOR .../.../.../TAHUN ...')
            ->and($sk->where('bagian', 'diktum')->last()['teks'])->toBe('Keputusan Bupati ini mulai berlaku pada tanggal ditetapkan.')
            ->and($perbup->firstWhere('bagian', 'judul')['teks'])->toContain('NOMOR ... TAHUN ...')
            ->and($perbup->firstWhere('bagian', 'diktum')['teks'])->toStartWith("MEMUTUSKAN:\nMenetapkan:")
            ->and($perbup->firstWhere('label', 'BAB II')['teks'])->toBe('KETENTUAN PENUTUP')
            ->and($perbup->firstWhere('label', 'Pasal 2')['teks'])->toContain('Agar setiap orang mengetahuinya');
    });

    it('baris provinsi pada file unggahan tidak masuk judul', function () {
        $word = new PhpOffice\PhpWord\PhpWord();
        $bagian = $word->addSection();
        foreach (['BUPATI HULU SUNGAI TENGAH', 'PROVINSI KALIMANTAN SELATAN', 'PERATURAN BUPATI HULU SUNGAI TENGAH', 'TENTANG', 'DANA DESA', 'Menimbang: bahwa satu;'] as $teks) {
            $bagian->addText($teks);
        }
        $path = tempnam(sys_get_temp_dir(), 'unggah');
        PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($path);

        $judul = collect(app(PembacaNaskahWord::class)->baca($path, 'Peraturan Bupati')['baris'])->firstWhere('bagian', 'judul')['teks'];
        unlink($path);

        expect($judul)->not->toContain('PROVINSI')->toContain('PERATURAN BUPATI HULU SUNGAI TENGAH');
    });

    it('rumusan penyempurnaan dipakai dan baris dihapus hilang dari naskah', function () {
        $n = naskahCetak($this->pd);
        $n->baris()->where('label', 'Pasal 1')->update(['rumusan_penyempurnaan' => 'Rumusan baru.', 'status' => 'diubah']);
        $n->baris()->where('label', 'Pasal 2')->update(['status' => 'dihapus']);

        $blok = collect(TataLetakNaskah::susun($n, $n->baris()->get()));
        $teks = $blok->where('tipe', 'teks')->pluck('baris')->flatten(1)->pluck('isi');
        $tengah = $blok->where('tipe', 'tengah')->pluck('baris')->flatten();

        expect($teks)->toContain('Rumusan baru.')->not->toContain('Ayat dua.')
            ->and($tengah)->toContain('Pasal 1')->not->toContain('Pasal 2');
    });

    it('penutup memakai nama dari pengaturan, memisahkan blok, dan menyisakan ruang tanda tangan', function () {
        Pengaturan::simpan(Pengaturan::NAMA_BUPATI, 'NAMA BUPATI UJI');
        Pengaturan::simpan(Pengaturan::NAMA_SEKDA, 'NAMA SEKDA UJI');

        $n = naskahCetak($this->pd);
        $penutup = collect(TataLetakNaskah::susun($n, $n->baris()->get()))->firstWhere('tipe', 'penutup');

        expect($penutup['ditetapkan'])->toBe(['Ditetapkan di Barabai', 'pada tanggal ...', '', 'BUPATI HULU SUNGAI TENGAH,', '', '', '', 'NAMA BUPATI UJI'])
            ->and($penutup['diundangkan'])->toContain('NAMA SEKDA UJI')
            ->and($penutup['berita'])->toStartWith('BERITA DAERAH');
    });

    it('penutup hasil pembacaan Word diganti dengan nama terbaru, tepat sebelum lampiran', function () {
        Pengaturan::simpan(Pengaturan::NAMA_BUPATI, 'BUPATI BARU');

        $hasil = TemplateNaskah::denganPenutup([
            ['bagian' => 'judul', 'label' => null, 'teks' => 'x'],
            ['bagian' => 'penutup', 'label' => null, 'teks' => 'BUPATI LAMA'],
            ['bagian' => 'lampiran', 'label' => null, 'teks' => ''],
        ], 'Keputusan Bupati');

        expect(array_column($hasil, 'bagian'))->toBe(['judul', 'penutup', 'lampiran'])
            ->and($hasil[1]['teks'])->toContain('BUPATI BARU')->not->toContain('BUPATI LAMA');
    });
});

describe('berkas hasil cetak', function () {
    it('Word bisa dibuka, memuat tanda khusus sebagai teks, dan berukuran F4', function () {
        $n = naskahCetak($this->pd);
        $path = app(CetakNaskah::class)->wordNaskah($n);

        $zip = new ZipArchive();
        expect($zip->open($path))->toBeTrue();
        $xml = $zip->getFromName('word/document.xml');
        $gaya = $zip->getFromName('word/styles.xml');
        $zip->close();

        expect((new DOMDocument())->loadXML($xml))->toBeTrue()
            ->and($xml)->toContain('satu &amp; dua &lt;tiga&gt;')
            ->and($xml)->toContain('w:w="12240"')->toContain('w:h="18720"')
            ->and($gaya)->toContain('Bookman Old Style');

        unlink($path);
    });

    it('Word hasil cetak terbaca kembali dengan jumlah pasal yang sama', function () {
        $n = naskahCetak($this->pd);
        $path = app(CetakNaskah::class)->wordNaskah($n);
        $salinan = $path . '.docx';
        rename($path, $salinan);

        $hasil = (new PembacaNaskahWord())->baca($salinan, 'Peraturan Bupati');

        expect($hasil['ringkasan']['pasal'])->toBe(2);

        unlink($salinan);
    });

    it('PDF naskah dan matriks dihasilkan', function () {
        $n = naskahCetak($this->pd);
        $cetak = app(CetakNaskah::class);

        expect($cetak->pdfNaskah($n))->toStartWith('%PDF')
            ->and($cetak->pdfMatriks($n))->toStartWith('%PDF');
    });

    it('PDF naskah memakai Bookman Old Style sesuai ketentuan', function () {
        $pdf = app(CetakNaskah::class)->pdfNaskah(naskahCetak($this->pd));

        expect($pdf)->toContain('BookmanOldStyle')
            ->and($pdf)->not->toContain('Times-Roman');
    })->skip(fn () => ! is_file(resource_path('fonts/BOOKOS.TTF')), 'File font belum disalin ke resources/fonts.');
});

describe('isi lengkap di PDF', function () {
    /** Teks PDF tanpa spasi, karena pemenggalan baris di kolom sempit bisa memecah kalimat. */
    function teksPdf(string $pdf): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pdf');
        file_put_contents($path, $pdf);
        $teks = (new Smalot\PdfParser\Parser())->parseFile($path)->getText();
        unlink($path);

        return preg_replace('/\s+/u', '', $teks);
    }

    it('pasal yang lebih panjang dari satu halaman tidak terpotong', function () {
        // Penanda di akhir kalimat: Smalot tidak bisa membaca baris rata kanan-kiri pada font tertanam, sedangkan baris terakhir paragraf rata kiri.
        $butir = collect(range(1, 90))->map(fn ($i) => "{$i}. Definisi yang cukup panjang untuk mengisi lebih dari satu baris pada kolom rumusan di matriks penanda{$i}x.")->implode("\n");

        $n = naskahCetak($this->pd, 'Peraturan Bupati', [
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => "Yang dimaksud:\n{$butir}"],
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 2', 'teks' => 'Pasal berikutnya penandaakhir.'],
        ]);
        $n->baris()->first()->update(['rumusan_penyempurnaan' => $butir, 'status' => 'diubah', 'keterangan' => str_repeat('alasan ', 200) . 'ketakhir']);

        $cetak = app(CetakNaskah::class);
        $matriks = teksPdf($cetak->pdfMatriks($n));
        $naskah = teksPdf($cetak->pdfNaskah($n));

        foreach ([1, 45, 90] as $i) {
            expect(substr_count($matriks, "penanda{$i}x"))->toBe(2);
            expect($naskah)->toContain("penanda{$i}x");
        }

        expect($matriks)->toContain('penandaakhir')->toContain('ketakhir');
    });

    it('satu paragraf yang sangat panjang tidak terpotong', function () {
        $n = naskahCetak($this->pd, 'Peraturan Bupati', [
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => str_repeat('kata ', 3000) . 'penandaujung'],
        ]);

        expect(teksPdf(app(CetakNaskah::class)->pdfMatriks($n)))->toContain('penandaujung');
    });
});

describe('akses', function () {
    it('hanya yang berhak bisa mencetak', function () {
        $n = naskahCetak($this->pd);
        $lain = tap(User::factory()->create())->assignRole('Perangkat Daerah');

        foreach (['naskah-word', 'naskah-pdf', 'matriks-pdf'] as $berkas) {
            $this->actingAs($lain)->get(route('v2.naskah.cetak', [$n, $berkas]))->assertForbidden();
            $this->actingAs($this->pd)->get(route('v2.naskah.cetak', [$n, $berkas]))->assertOk()->assertDownload();
        }

        $this->actingAs($this->pd)->get("/v2/naskah/{$n->slug}/cetak/lainnya")->assertNotFound();
    });

    it('pengaturan hanya untuk Bagian Hukum dan tersimpan', function () {
        $this->actingAs($this->pd)->get(route('v2.pengaturan'))->assertForbidden();

        $admin = tap(User::factory()->create())->assignRole('Admin');
        $this->actingAs($admin)->get(route('v2.pengaturan'))->assertOk();

        Livewire::test(PengaturanNaskah::class)
            ->set('namaBupati', '')->call('simpan')->assertHasErrors('namaBupati')
            ->set('namaBupati', 'A')->set('namaSekda', 'B')->call('simpan')->assertHasNoErrors();

        expect(Pengaturan::ambil(Pengaturan::NAMA_BUPATI))->toBe('A');
    });

    it('pengaturan tampil sebagai bacaan dan batal tidak menyimpan', function () {
        $admin = tap(User::factory()->create())->assignRole('Admin');
        Pengaturan::simpan(Pengaturan::NAMA_BUPATI, 'Bupati Lama');

        Livewire::actingAs($admin)->test(PengaturanNaskah::class)
            ->assertSee('Bupati Lama')
            ->assertDontSeeHtml('wire:model="namaBupati"')
            ->call('mulaiUbah')
            ->assertSeeHtml('wire:model="namaBupati"')
            ->set('namaBupati', 'Bupati Baru')
            ->call('batal')
            ->assertSet('mengubah', false)
            ->assertSet('namaBupati', 'Bupati Lama');

        expect(Pengaturan::ambil(Pengaturan::NAMA_BUPATI))->toBe('Bupati Lama');
    });

    it('pengaturan kembali ke bacaan sesudah tersimpan', function () {
        $admin = tap(User::factory()->create())->assignRole('Admin');

        Livewire::actingAs($admin)->test(PengaturanNaskah::class)
            ->call('mulaiUbah')
            ->set('namaBupati', 'A')->set('namaSekda', 'B')
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSet('mengubah', false)
            ->assertDispatched('swal:toast');
    });
});
