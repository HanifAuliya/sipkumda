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
        ->and(HtmlLampiran::teks($bersih))->toContain("SUSUNAN TIM\n|1. |Budi, S.H. |");
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
