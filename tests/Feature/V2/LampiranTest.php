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

it('lampiran baru memakai editor; isinya tersimpan bersih dan tampil di pratinjau, PDF, Word, dan matriks', function () use ($tabelTim) {
    $n = drafBerlampiran($this->pd);
    $lampiran = $n->baris()->where('bagian', 'lampiran')->first();

    expect($lampiran->format)->toBe(B::FORMAT_HTML);

    Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $lampiran, 'naskah' => $n])
        ->assertSee('Lampiran terlalu rumit? Unggah PDF saja')
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

    // Naskah: blok berkas; matriks: nama berkasnya; tab Naskah: tombol gabung PDF.
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

it('keluaran editor Tiptap: sel berparagraf, lebar kolom, dan tabel tanpa garis tetap benar di cetakan dan matriks', function () {
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

    // Matriks: satu baris tabel tetap satu baris "| a | b |".
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
    $layar->call('pilihBlok', 'judul')->call('bukaBaris', $kedua->id)->assertSet('blok', 'lampiran')->assertSet('lampiran', $kedua->id)
        ->call('pilihLampiran', 999999)->assertSet('lampiran', $pertama->id);

    // Kertasnya memuat kepala dan tanda tangan otomatis, sama dengan cetakan.
    Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $kedua->fresh(), 'naskah' => $n])
        ->assertSee(['LAMPIRAN II', 'TIM PENYUSUN', 'BUPATI HULU SUNGAI TENGAH,'])
        ->assertSeeHtml(['class="kertas-naskah kertas-lampiran', '--k-tinggi: 33cm', 'data-wadah', 'aria-label="Hapus lampiran ini"']);
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
