<?php

use App\Livewire\V2\BarisKerja;
use App\Livewire\V2\LayarKerja;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahBerkas;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\CetakNaskah;
use App\Support\HtmlLampiran;
use App\Support\PemeriksaIsian;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
 * Lampiran: diketik atau ditempel dari Word di editor (disimpan sebagai HTML bersih), atau berupa berkas PDF
 * yang diunggah per versi dan digabung di belakang naskah saat dicetak.
 */

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    $this->alur = app(AlurNaskah::class);
    $this->pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create())->assignRole('Peneliti');
});

function drafBerlampiran(User $pd): Naskah
{
    $n = Naskah::create(['jenis' => 'Keputusan Bupati', 'tentang' => 'Tim Penyusun', 'pemrakarsa_id' => $pd->id, 'nomor_nota' => 'ND-' . uniqid()]);
    app(AlurNaskah::class)->isiBaris($n, TemplateNaskah::kerangka('Keputusan Bupati', 'Tim Penyusun'));
    $n->baris()->where('bagian', 'menimbang')->update(['rumusan_awal' => 'bahwa perlu dibentuk tim;']);
    $n->baris()->where('bagian', 'mengingat')->update(['rumusan_awal' => 'Undang-Undang Nomor 23 Tahun 2014;']);
    $n->baris()->where('bagian', 'diktum')->where(fn ($q) => $q->whereNull('rumusan_awal')->orWhere('rumusan_awal', ''))->update(['rumusan_awal' => 'Membentuk Tim Penyusun.']);
    $n->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => 'x.pdf', 'nama_asli' => 'x.pdf', 'diunggah_oleh' => $pd->id]);

    return $n;
}

function pdfPalsu(string $nama = 'lampiran.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($nama, "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF");
}

$tabelTim = '<p style="text-align:center"><strong>SUSUNAN TIM</strong></p><figure class="table"><table><tbody>'
    . '<tr><td>1.</td><td style="text-align:right">Budi, S.H.</td></tr></tbody></table></figure>'
    . '<script>alert(1)</script><p><img src=x onerror=alert(1)><a href="javascript:x">tautan</a></p>';

it('membersihkan HTML dari editor: rata, tebal, dan tabel tetap; skrip, gambar, dan tautan dibuang', function () use ($tabelTim) {
    $bersih = HtmlLampiran::bersihkan($tabelTim);

    expect($bersih)->toContain('<p style="text-align:center"><strong>SUSUNAN TIM</strong></p>', '<td style="text-align:right">Budi, S.H.</td>')
        ->not->toContain('script', 'img', 'javascript', 'figure', 'href')
        ->and(HtmlLampiran::bersihkan('<p>&nbsp;</p><p style="background:url(http://x)"> </p>'))->toBeNull()
        ->and(HtmlLampiran::teks($bersih))->toContain("SUSUNAN TIM\n| 1. | Budi, S.H. |");
});

it('lampiran baru memakai editor; isinya tersimpan bersih dan tampil di pratinjau, PDF, dan Word', function () use ($tabelTim) {
    $n = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();

    expect($lampiran->format)->toBe(B::FORMAT_HTML);

    Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $lampiran, 'naskah' => $n])
        ->assertSee(['Ambil dari Word / PDF', 'Pakai PDF apa adanya'])
        ->call('simpanSusun', null, $tabelTim)
        ->assertHasNoErrors();

    $lampiran->refresh();
    expect($lampiran->rumusan_awal)->toContain('<strong>SUSUNAN TIM</strong>')->not->toContain('script');

    $blok = collect(TataLetakNaskah::susun($n, $n->baris()->get()));
    expect($blok->firstWhere('tipe', 'html')['html'])->toContain('Budi, S.H.')
        ->and($blok->pluck('tipe')->all())->toContain('lampiran', 'html', 'ttd');

    $html = view('v2.partials.isi-naskah', ['blok' => $blok->all(), 'jenis' => $n->jenis])->render();
    expect($html)->toContain('class="lampiran-html', '<td style="text-align:right">Budi, S.H.</td>');

    $cetak = app(CetakNaskah::class);
    expect(substr($cetak->pdfNaskah($n), 0, 4))->toBe('%PDF');

    $word = $cetak->wordNaskah($n);
    $zip = new ZipArchive();
    $zip->open($word);
    expect($zip->getFromName('word/document.xml'))->toContain('SUSUNAN TIM', 'Budi, S.H.');
    $zip->close();
    @unlink($word);
});

it('lampiran berupa PDF: diunggah per versi, wajib ada sebelum diajukan, dan Peneliti bisa mengunggah versi baru', function () {
    $n = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();

    $komponen = Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $lampiran, 'naskah' => $n])
        ->call('aturFormat', B::FORMAT_BERKAS)
        ->assertHasNoErrors()
        ->assertSee('PDF lampiran belum diunggah.');

    // Belum diunggah: menghalangi pengajuan.
    $kurang = collect(PemeriksaIsian::kurangLengkap($n, $n->baris()->get()));
    expect($kurang->where('kosong', true)->pluck('pesan')->implode(' '))->toContain('PDF lampiran belum diunggah')
        ->and($this->alur->aksiTersedia($n, $this->pd)['ajukan']['hambatan'])->toBe('1 baris masih kosong.');

    $komponen->set('pdfLampiran', pdfPalsu('Susunan Tim.pdf'))->call('unggahLampiran')->assertHasNoErrors();

    $lampiran->refresh();
    $pertama = $lampiran->berkasLampiran($lampiran->rumusan_awal);
    expect($lampiran->format)->toBe(B::FORMAT_BERKAS)
        ->and($pertama?->nama_asli)->toBe('Susunan Tim.pdf')
        ->and($pertama->jenis)->toBe(NaskahBerkas::LAMPIRAN_BARIS)
        ->and($this->alur->aksiTersedia($n, $this->pd)['ajukan']['hambatan'])->toBeNull();

    // Diajukan dan diteliti: Peneliti mengunggah versi baru, versi pertama tetap di rumusan awal.
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
    $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);

    Livewire::actingAs($this->peneliti)->test(BarisKerja::class, ['baris' => $lampiran->fresh(), 'naskah' => $n])
        ->set('pdfLampiran', pdfPalsu('Susunan Tim revisi.pdf'))
        ->call('unggahLampiran')
        ->assertHasNoErrors();

    $lampiran->refresh();
    expect($lampiran->status)->toBe(B::DIUBAH)
        ->and($lampiran->berkasLampiran($lampiran->rumusan_awal)->nama_asli)->toBe('Susunan Tim.pdf')
        ->and($lampiran->berkasLampiran($lampiran->rumusan_penyempurnaan)->nama_asli)->toBe('Susunan Tim revisi.pdf');

    // Naskah: blok berkas; tab Naskah: tombol gabung PDF.
    expect(collect(TataLetakNaskah::susun($n, $n->baris()->get()))->firstWhere('tipe', 'berkas')['berkas']->nama_asli)->toBe('Susunan Tim revisi.pdf')
        ->and(substr(app(CetakNaskah::class)->pdfMatriks($n), 0, 4))->toBe('%PDF');

    Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $n])
        ->set('tab', 'naskah')
        ->assertSee('Naskah + Lampiran PDF');

    // Alamat penampil PDF di kartu dan di isi naskah benar-benar bisa dibuka (naskah memakai slug, bukan id).
    $kartu = Livewire::actingAs($this->peneliti)->test(BarisKerja::class, ['baris' => $lampiran, 'naskah' => $n])->html();
    $isi = view('v2.partials.isi-naskah', ['blok' => TataLetakNaskah::susun($n, $n->baris()->get()), 'jenis' => $n->jenis])->render();

    preg_match_all('/<iframe src="([^"#]+)/', $kartu . $isi, $alamat);
    expect($alamat[1])->toHaveCount(3);

    foreach ($alamat[1] as $url) {
        $this->actingAs($this->peneliti)->get(html_entity_decode($url))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    }
});

it('lampiran PDF hanya boleh menunjuk berkas lampiran naskah yang sama', function () {
    $n = drafBerlampiran($this->pd);
    $lain = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();
    $this->alur->aturFormatLampiran($lampiran, $this->pd, B::FORMAT_BERKAS);

    $asing = $lain->berkas()->create(['jenis' => NaskahBerkas::LAMPIRAN_BARIS, 'path' => 'y.pdf', 'nama_asli' => 'y.pdf', 'diunggah_oleh' => $this->pd->id]);

    expect(fn () => $this->alur->susun($lampiran->fresh(), $this->pd, null, (string) $asing->id))
        ->toThrow(Illuminate\Validation\ValidationException::class, 'Berkas lampiran tidak ditemukan');
});

it('keluaran editor Tiptap: sel berparagraf, lebar kolom, dan tabel tanpa garis tetap benar di cetakan', function () {
    $html = '<p style="text-align: center;"><strong>TIM</strong></p>'
        . '<table style="width: 600px;"><colgroup><col style="width: 60px;"><col style="width: 340px;"><col style="width: 200px;"></colgroup><tbody>'
        . '<tr><td colwidth="60"><p>NO</p></td><td colwidth="340"><p>NAMA</p><p>JABATAN</p></td><td colwidth="200"><p>KET|X</p></td></tr>'
        . '<tr><td rowspan="2" colwidth="60"><p>1.</p></td><td colspan="2" colwidth="340,200"><p>Gabungan</p></td></tr>'
        . '<tr><td colwidth="340"><p>Budi</p></td><td colwidth="200" style="border-style: none;"><p><span>Ketua</span></p></td></tr>'
        . '</tbody></table><ol style="list-style-type: lower-alpha;"><li><p>huruf a</p></li></ol><p></p><p>akhir</p><p></p>';
    $bersih = HtmlLampiran::bersihkan($html);

    expect($bersih)->toContain('colwidth="340,200"', 'border-style:none', 'list-style-type:lower-alpha')
        ->not->toContain('<span>')
        ->toEndWith('<p>akhir</p>')
        // Dibersihkan lagi hasilnya sama, jadi menyimpan tanpa perubahan tidak dianggap Diubah.
        ->and(HtmlLampiran::bersihkan($bersih))->toBe($bersih)
        ->and(HtmlLampiran::bersihkan('<table><tbody><tr><td colwidth="1;x">a</td></tr></tbody></table>'))->not->toContain('colwidth');

    // Bentuk teksnya (perbandingan, riwayat): satu baris tabel tetap satu baris "| a | b |".
    $teks = explode("\n", HtmlLampiran::teks($bersih));
    expect($teks)->toBe(['TIM', '| NO | NAMA JABATAN | KET/X |', '| 1. | Gabungan | |', '| | Budi | Ketua |', 'a. huruf a', 'akhir'])
        ->and(HtmlLampiran::teks('<ol start="2"><li><p>dua</p><ul><li>poin</li></ul></li><li>tiga</li></ol><ol style="list-style-type:lower-roman"><li>x</li><li>y</li></ol>'))
        ->toBe("2. dua\n- poin\n3. tiga\ni. x\nii. y")
        ->and(HtmlLampiran::teks('<p>a <strong>b</strong> c<br>d</p>'))->toBe("a b c\nd");

    // Tampilan: 60/600 = 10%, sel gabungan 90%, paragraf kosong tidak hilang, colgroup piksel dibuang.
    $tampil = HtmlLampiran::untukTampil($bersih);
    expect($tampil)->toContain('width:10%', 'width:56.67%', 'width:33.33%', 'width:90%', 'border-style:none;width:33.33%')
        ->toContain('<table style="width:95.69%">') // 600 dari 627 px lebar isi kertas
        ->not->toContain('<colgroup', '<p></p>')
        // Tabel tanpa lebar kolom dibiarkan.
        ->and(HtmlLampiran::untukTampil('<table><tbody><tr><td>a</td></tr></tbody></table>'))->toBe('<table><tbody><tr><td>a</td></tr></tbody></table>');

    // Word: gabungan ke samping dan ke bawah, garis, dan lebar kolom.
    $n = drafBerlampiran($this->pd);
    $n->baris()->where('bagian', 'lampiran')->update(['rumusan_awal' => $bersih]);
    $berkas = app(CetakNaskah::class)->wordNaskah($n);
    $zip = new ZipArchive();
    $zip->open($berkas);
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($berkas);
    $xml = substr($xml, strpos($xml, '>TIM<'));

    expect($xml)->toContain('<w:gridSpan w:val="2"/>', '<w:vMerge w:val="restart"/>', '<w:vMerge w:val="continue"/>', 'Gabungan', 'JABATAN')
        ->and(substr_count($xml, '<w:tcBorders>'))->toBe(7); // 8 sel termasuk lanjutan gabungan, satu tanpa garis
});

it('halaman lampiran: satu lampiran per halaman di kertas, dengan tab dan Tambah Lampiran', function () {
    $n = drafBerlampiran($this->pd);
    $pertama = $n->baris()->where('bagian', 'lampiran')->first();
    $this->alur->susun($pertama, $this->pd, null, '<p>DAFTAR PERTAMA</p>');

    $layar = Livewire::actingAs($this->pd)->test(LayarKerja::class, ['naskah' => $n, 'blok' => 'lampiran'])
        ->assertSet('lampiran', $pertama->id)
        ->assertSee('Tambah Lampiran')
        ->assertSeeHtml('id="baris-' . $pertama->id . '"')
        ->call('tambahLampiran')
        ->assertHasNoErrors();

    $kedua = $n->baris()->where('bagian', 'lampiran')->get()->last();
    expect($kedua->id)->not->toBe($pertama->id)->and($kedua->format)->toBe(B::FORMAT_HTML);

    // Lampiran baru langsung dibuka; yang lain tidak ikut dimuat.
    $layar->assertSet('lampiran', $kedua->id)
        ->assertSeeHtml('id="baris-' . $kedua->id . '"')
        ->assertDontSeeHtml('id="baris-' . $pertama->id . '"')
        ->assertSee('kosong');

    $this->alur->susun($kedua, $this->pd, null, '<p>DAFTAR KEDUA</p>');
    $layar->call('pilihLampiran', $pertama->id)->assertSee(['Lampiran I', 'Lampiran II'])->assertSeeHtml('id="baris-' . $pertama->id . '"');

    // Menuju baris dari perbandingan atau daftar galat membuka lampirannya; id yang tidak dikenal kembali ke yang pertama.
    $layar->call('pilihBlok', 'judul')->assertSet('tab', 'kerja')
        ->call('bukaBaris', $kedua->id)->assertSet('tab', 'naskah')->assertSet('tampilNaskah', 'lampiran')->assertSet('lampiran', $kedua->id)
        ->call('pilihLampiran', 999999)->assertSet('lampiran', $pertama->id);

    // Kertasnya memuat kepala dan tanda tangan otomatis, sama dengan cetakan.
    Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $kedua->fresh(), 'naskah' => $n])
        ->assertSee(['LAMPIRAN II', 'TIM PENYUSUN', 'BUPATI HULU SUNGAI TENGAH,'])
        ->assertSeeHtml(['class="kertas-naskah kertas-lampiran', '--k-tinggi: 33cm', 'data-wadah', 'aria-label="Hapus lampiran ini"']);
});

it('lampiran hanya ada di tab Naskah: tidak di Matriks dan tidak di Matriks PDF, tetapi tetap wajib diperiksa', function () {
    $n = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();
    $this->alur->susun($lampiran, $this->pd, null, '<p>PENANDALAMPIRAN</p>');
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
    $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);

    $pdf = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($pdf, app(CetakNaskah::class)->pdfMatriks($n));
    expect((new Smalot\PdfParser\Parser())->parseFile($pdf)->getText())->not->toContain('PENANDALAMPIRAN');
    unlink($pdf);

    $layar = Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $n])
        ->assertViewHas('daftarBlok', fn ($blok) => ! $blok->contains('bagian', 'lampiran'))
        ->assertSee('Di tab Naskah')
        ->assertDontSee('PENANDALAMPIRAN');

    // Tautan lama ke bagian Lampiran tetap sampai.
    Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $n, 'blok' => 'lampiran'])
        ->assertSet('tab', 'naskah')->assertSee('PENANDALAMPIRAN');

    // Baris matriks selesai: yang tersisa lampirannya, dan disebut tempatnya.
    $n->baris()->where('bagian', '!=', 'lampiran')->get()->each(fn (B $b) => $this->alur->tetapkan($b, $this->peneliti));
    $hambatan = fn () => collect($this->alur->aksiTersedia($n->fresh(), $this->peneliti))->pluck('hambatan')->filter()->first();
    expect($hambatan())->toBe('1 lampiran (tab Naskah) belum diperiksa.');
    $layar->call('$refresh')->assertSee('Buka Lampiran');

    $this->alur->tetapkan($lampiran->fresh(), $this->peneliti);
    expect($hambatan())->toBeNull();
    $layar->call('$refresh')->assertDontSee('Buka Lampiran');
});

it('Peneliti memeriksa lampiran di kertas: versi terbaru, rumusan awal, dan perubahannya', function () {
    $n = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();
    $this->alur->susun($lampiran, $this->pd, null, '<p>Ketua Budi</p>');
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
    $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);

    $uji = fn (User $u) => Livewire::actingAs($u)->test(BarisKerja::class, ['baris' => $lampiran->fresh(), 'naskah' => $n]);

    $uji($this->peneliti)->assertSee(['Ketua Budi', 'Tetap', 'Minta PD Perbaiki'])->assertDontSee('Rumusan Awal')
        ->call('simpanPerbaikan', '<p>Ketua Ani</p>', null)->assertHasNoErrors();

    $uji($this->peneliti)->assertSee(['Versi Terbaru', 'Rumusan Awal', 'Perubahan'])->assertSeeHtml(['<del>Budi</del>', '<ins>Ani</ins>']);

    // Yang hanya membaca tidak dimuati editornya.
    $uji($this->pd)->assertSee('Ketua Ani')->assertDontSeeHtml('data-wadah');
});

it('tabel seperti di Word: garis per sisi, arsiran, tinggi baris, letak, dan baris judul tetap di cetakan', function () {
    $html = '<table style="margin-left: auto; margin-right: auto;"><colgroup><col style="width: 100px;"><col style="width: 200px;"></colgroup><tbody>'
        . '<tr style="height: 42.5pt;"><th colwidth="100" style="background-color: rgb(217, 217, 217); vertical-align: middle;" data-tg="kiri"><p>NO</p></th><th colwidth="200"><p>NAMA</p></th></tr>'
        . '<tr><td colwidth="100" style="border-bottom: 2.25pt solid; border-left: none;"><p>1.</p></td><td colwidth="200" style="border-top: 3pt double;"><p>Budi</p></td></tr></tbody></table>';
    $bersih = HtmlLampiran::bersihkan($html);

    expect($bersih)->toContain('margin-left:auto;margin-right:auto', 'height:42.5pt', 'background-color:rgb(217, 217, 217)', 'border-bottom:2.25pt solid;border-left:none', 'border-top:3pt double')
        ->not->toContain('data-tg')
        ->and(HtmlLampiran::bersihkan($bersih))->toBe($bersih)
        ->and(HtmlLampiran::garisSel('border-bottom:2.25pt solid;border-left:none'))->toBe(['top' => ['solid', 0.5], 'right' => ['solid', 0.5], 'bottom' => ['solid', 2.25], 'left' => ['none', 0.5]])
        ->and(HtmlLampiran::garisSel('border-style:none')['top'][0])->toBe('none')
        ->and(HtmlLampiran::garisSel('border-style:none solid;border-width:2px')['right'])->toBe(['solid', 1.5]);

    // Letak menjadi jarak kiri, baris judul menjadi thead, tinggi baris ditulis juga di selnya.
    $tampil = HtmlLampiran::untukTampil($bersih, 600);
    expect($tampil)->toContain('<table style="width:50%;margin-left:25%">', '<thead><tr style="height:42.5pt"><th', 'height:38.5pt', '</thead><tbody><tr><td');

    $n = drafBerlampiran($this->pd);
    $n->baris()->where('bagian', 'lampiran')->update(['rumusan_awal' => $bersih]);
    expect(substr(app(CetakNaskah::class)->pdfNaskah($n), 0, 4))->toBe('%PDF');

    $berkas = app(CetakNaskah::class)->wordNaskah($n);
    $zip = new ZipArchive();
    $zip->open($berkas);
    $xml = $zip->getFromName('word/document.xml');
    $zip->close();
    @unlink($berkas);
    expect($xml)->toContain('<w:trHeight w:val="850"', '<w:tblHeader', 'w:fill="D9D9D9"', '<w:bottom w:val="single" w:sz="18"', '<w:top w:val="double" w:sz="24"', '<w:tblInd ');
});

it('lampiran sangat panjang tidak hilang saat disimpan', function () {
    $html = str_repeat('<p style="margin-left:36pt">Isi yang panjang sekali.</p><p></p><table><tbody><tr><td><p></p></td></tr></tbody></table>', 2000) . '<p>akhir</p><p>&nbsp;</p><p><br></p>';

    expect(HtmlLampiran::bersihkan($html))->toEndWith('<p>akhir</p>')
        ->and(strlen(HtmlLampiran::bersihkan($html)))->toBeGreaterThan(200000);
});

/** File Word lampiran: kepala, judul, daftar, tabel bergabung dengan garis dan arsiran, lalu tanda tangan. */
function wordLampiran(array $awal = []): UploadedFile
{
    $word = new PhpOffice\PhpWord\PhpWord();
    PhpOffice\PhpWord\Settings::setOutputEscapingEnabled(true);
    $word->setDefaultFontSize(12);
    $word->addNumberingStyle('angka', ['type' => 'multilevel', 'levels' => [['format' => 'decimal', 'text' => '%1.', 'start' => 1]]]);
    $s = $word->addSection();
    foreach ([...$awal, 'LAMPIRAN', 'KEPUTUSAN BUPATI HULU SUNGAI TENGAH', 'NOMOR ... TAHUN 2026', 'TENTANG', 'TIM', 'PENYUSUN'] as $teks) {
        $s->addText($teks);
    }
    $s->addText('SUSUNAN <TIM>', ['bold' => true, 'size' => 14], ['alignment' => 'center']);
    $s->addListItem('angka satu', 0, null, 'angka');
    $s->addListItem('angka dua', 0, null, 'angka');
    $t = $s->addTable(['borderSize' => 4, 'borderColor' => '000000']);
    $t->addRow(850, ['tblHeader' => true]);
    $t->addCell(750, ['bgColor' => 'D9D9D9', 'valign' => 'center'])->addText('NO');
    $t->addCell(3000)->addText('NAMA');
    $t->addCell(1500)->addText('KET');
    $t->addRow();
    $t->addCell(750, ['vMerge' => 'restart'])->addText('1.');
    $t->addCell(4500, ['gridSpan' => 2])->addText('Gabungan');
    $t->addRow();
    $t->addCell(750, ['vMerge' => 'continue']);
    $t->addCell(3000)->addText('Budi', ['italic' => true]);
    $t->addCell(1500, ['borderBottomSize' => 24, 'borderBottomColor' => '000000', 'borderBottomStyle' => 'single'])->addText('Ketua');
    $s->addText('BUPATI HULU SUNGAI TENGAH,');
    $s->addTextBreak(2);
    $s->addText('SAMSUL RIZAL');

    $berkas = tempnam(sys_get_temp_dir(), 'lamp') . '.docx';
    PhpOffice\PhpWord\IOFactory::createWriter($word, 'Word2007')->save($berkas);

    return UploadedFile::fake()->createWithContent('lampiran.docx', file_get_contents($berkas));
}

it('isi file Word masuk ke editor lampiran tanpa kepala dan tanda tangan, lengkap dengan tabelnya', function () {
    $n = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();

    $komponen = Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $lampiran, 'naskah' => $n])
        ->set('wordLampiran', wordLampiran())
        ->call('bacaWordLampiran')
        ->assertHasNoErrors();
    $html = $komponen->effects['returns'][0]['html'];

    expect($html)->toStartWith('<p style="text-align:center"><span style="font-size:14pt"><strong>SUSUNAN &lt;TIM&gt;</strong></span></p><ol><li><p>angka satu</p></li><li><p>angka dua</p></li></ol><table')
        ->toContain('<tr style="height:42.5pt"><th colwidth="50" style="background-color:#d9d9d9;vertical-align:middle">', 'rowspan="2"', 'colspan="2" colwidth="200,100"', '<em>Budi</em>', 'border-bottom:3pt solid')
        ->not->toContain('LAMPIRAN', 'PENYUSUN', 'BUPATI', 'SAMSUL')
        // Belum tersimpan sampai pengguna menyimpannya dari editor.
        ->and($lampiran->fresh()->rumusan_awal)->toBeNull();

    $this->alur->susun($lampiran->fresh(), $this->pd, null, $html);
    expect($lampiran->fresh()->rumusan_awal)->toBe($html);

    // Bukan Word, dan yang tidak sedang bertugas.
    expect(Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $lampiran, 'naskah' => $n])
        ->set('wordLampiran', UploadedFile::fake()->createWithContent('rusak.docx', 'bukan word'))->call('bacaWordLampiran')->effects['returns'][0])->toHaveKey('galat');
    Livewire::actingAs($this->peneliti)->test(BarisKerja::class, ['baris' => $lampiran, 'naskah' => $n])->call('rapikanLampiran', '<p>x</p>')->assertForbidden();
});

it('hasil baca PDF dibersihkan, dan lampiran PDF bisa diubah menjadi isi editor', function () {
    $n = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();
    $uji = fn () => Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $lampiran->fresh(), 'naskah' => $n]);

    $hasil = $uji()->call('rapikanLampiran', '<p style="margin-left:227pt">LAMPIRAN I</p><p style="margin-left:227pt">KEPUTUSAN BUPATI HULU SUNGAI TENGAH NOMOR 1 TAHUN 2026 TENTANG TIM PENYUSUN</p><p>isi<script>x</script></p>')->effects['returns'][0];
    expect($hasil['html'])->toBe('<p>isi</p>')->and($hasil['catatan'])->toContain('Cocokkan');

    $this->alur->aturFormatLampiran($lampiran, $this->pd, B::FORMAT_BERKAS);
    $uji()->set('pdfLampiran', pdfPalsu())->call('unggahLampiran')->assertSee('Ubah jadi bisa diedit');
    $uji()->call('pdfMenjadiEditor', '<p>dari PDF</p>')->assertHasNoErrors()->assertSee('Ambil dari Word / PDF');
    expect($lampiran->fresh()->format)->toBe(B::FORMAT_HTML)->and($lampiran->fresh()->rumusan_awal)->toBe('<p>dari PDF</p>');
});

it('kepala lampiran yang judulnya berbeda dari judul naskah tetap dikenali dari letaknya', function () {
    $kepala = fn (string $judul) => '<p style="text-indent:200pt">LAMPIRAN</p><p style="text-indent:200pt">KEPUTUSAN BUPATI HULU SUNGAI TENGAH</p><p>NOMOR 1 TAHUN 2026</p><p>TENTANG</p><p style="text-indent:200pt">' . $judul . '</p><p></p><p>isi</p>';

    expect(HtmlLampiran::tanpaKepalaTtd($kepala('TIM LAIN'), 'Tim Penyusun'))->toBe('<p>isi</p>')
        ->and(HtmlLampiran::tanpaKepalaTtd($kepala('TIM PENYUSUN'), 'Tim Penyusun'))->toBe('<p>isi</p>')
        ->and(HtmlLampiran::tanpaKepalaTtd('<p>LAMPIRAN II</p><p>isi</p>', 'x'))->toBe('<p>isi</p>')
        // Bukan kepala dan bukan tanda tangan: dibiarkan.
        ->and(HtmlLampiran::tanpaKepalaTtd('<p>Daftar lampiran permohonan</p><p>isi</p>', 'x'))->toBe('<p>Daftar lampiran permohonan</p><p>isi</p>');
});

it('ketikan selama menyimpan tidak tertimpa: isi dari server tidak ada di x-data', function () {
    $n = drafBerlampiran($this->pd);
    $baris = $n->baris()->where('bagian', 'menimbang')->first();
    $komponen = Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $baris, 'naskah' => $n]);
    $xData = fn () => preg_match('/<article[^>]*?x-data="([^"]*)"/s', $komponen->html(), $m) ? $m[1] : null;

    $awal = $xData();
    $komponen->call('simpanSusun', null, 'bahwa teks baru;')->assertSeeHtml('data-teks="bahwa teks baru;"');
    expect($xData())->toBe($awal)->not->toBeNull();
});
