<?php

use App\Livewire\V2\PengaturanFormat;
use App\Models\Naskah;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\CetakNaskah;
use App\Support\FormatDokumen;
use App\Support\TemplateNaskah;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
});

/** Isi document.xml dan footer dari Word template satu jenis naskah. */
function wordFormat(string $jenis): array
{
    $path = app(CetakNaskah::class)->wordTemplate($jenis);
    $zip = new ZipArchive();
    $zip->open($path);
    $xml = $zip->getFromName('word/document.xml');
    $footer = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => $zip->getNameIndex($i))
        ->filter(fn ($b) => str_contains($b, 'footer'))->map(fn ($b) => $zip->getFromName($b))->implode('');
    $zip->close();
    unlink($path);

    return [$xml, $footer];
}

function teksPdfFormat(string $pdf): string
{
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, $pdf);
    $teks = (new Smalot\PdfParser\Parser())->parseFile($path)->getText();
    unlink($path);

    return preg_replace('/\s+/u', '', $teks);
}

describe('bawaan', function () {
    it('Keputusan Bupati mengikuti pedoman SK: F4, tepi 2,5 cm, atas 3 cm, "Menimbang:" rapat, kotak paraf', function () {
        [$xml, $footer] = wordFormat(FormatDokumen::KEPBUP);

        // 21,5 x 33 cm; atas 3 cm, kiri, kanan, dan bawah 2,5 cm (twip).
        expect($xml)->toContain('w:w="12189" w:h="18709"')
            ->toMatch('/w:top="1701"[^>]*w:right="1417"[^>]*w:bottom="1417"[^>]*w:left="1417"|w:left="1417"[^>]*w:right="1417"[^>]*w:top="1701"/')
            ->toContain('Menimbang:', 'MEMUTUSKAN:', 'Menetapkan:', 'BUPATI HULU SUNGAI TENGAH')
            ->toContain('Paraf Hierarki', 'Asisten Pemerintahan dan Kesra')
            ->and($footer)->toContain('Paraf Hierarki', 'Sekretaris Daerah');

        expect(teksPdfFormat(app(CetakNaskah::class)->pdfContohNaskah(FormatDokumen::KEPBUP, FormatDokumen::naskah(FormatDokumen::KEPBUP))))
            ->toContain('Menimbang:', 'MEMUTUSKAN:', 'ParafHierarki', 'KepalaBagianHukum');
    });

    it('Peraturan Bupati tetap seperti sebelumnya: titik dua berkolom, tanpa kotak paraf', function () {
        [$xml, $footer] = wordFormat(FormatDokumen::PERBUP);

        expect($xml)->toContain('w:w="12240" w:h="18720"')->not->toContain('Paraf Hierarki', 'Menimbang:')
            ->and($footer)->toBe('');
    });

    it('nota dinas mengikuti contoh Nota Dinas 100.3/437/ND/KUM/2026', function () {
        $teks = teksPdfFormat(app(CetakNaskah::class)->pdfContohNota(FormatDokumen::nota()));

        expect($teks)->toContain('PEMERINTAHKABUPATENHULUSUNGAITENGAH', 'SEKRETARIATDAERAH', 'NOTADINAS', '100.3/123/ND/KUM/')
            ->toContain('SehubungandenganNotaDinasKepalaDinasContoh', 'KoreksiatasKeputusanBupatitentangContohJudul')
            ->toContain('KertasF4', 'Demikiandisampaikan', 'KepalaBagianHukum,');
    });
});

describe('pengaturan mengubah hasil cetak', function () {
    it('kop, margin, paraf, dan tempat penetapan Keputusan Bupati', function () {
        $format = FormatDokumen::naskah(FormatDokumen::KEPBUP);
        $format['kop']['teks'] = 'KOP UJI';
        $format['margin']['kiri'] = 3.0;
        $format['paraf']['tampil'] = false;
        $format['penutup']['tempat'] = 'Pagat';
        $format['konsiderans']['titik_dua'] = 'kolom';
        FormatDokumen::simpanNaskah(FormatDokumen::KEPBUP, $format);

        [$xml, $footer] = wordFormat(FormatDokumen::KEPBUP);

        expect($xml)->toContain('KOP UJI', 'w:left="1701"')->not->toContain('Paraf Hierarki', 'Menimbang:')
            ->and($footer)->toBe('')
            ->and(TemplateNaskah::penutup(FormatDokumen::KEPBUP))->toStartWith('Ditetapkan di Pagat')
            ->and(TemplateNaskah::penutup(FormatDokumen::PERBUP))->toStartWith('Ditetapkan di Barabai');
    });

    it('isi nota dinas memakai isian otomatis', function () {
        $format = FormatDokumen::nota();
        $format['perihal'] = 'Hasil fasilitasi {jenis} tentang {tentang}';
        $format['butir'] = ['Butir pertama untuk {perangkat_daerah}.'];
        $format['ketentuan'] = ['Ketentuan khusus uji.'];
        $format['kop']['baris'] = [['teks' => 'KOP NOTA UJI', 'ukuran' => 12.0, 'tebal' => true]];

        $teks = teksPdfFormat(app(CetakNaskah::class)->pdfContohNota($format));

        expect($teks)->toContain('HasilfasilitasiKeputusanBupatitentangContohJudul', 'ButirpertamauntukDinasContoh.', 'Ketentuankhususuji.', 'KOPNOTAUJI')
            ->not->toContain('SEKRETARIATDAERAH', '{jenis}');
    });

    it('nilai lama yang belum punya kunci baru tetap memakai bawaan', function () {
        App\Models\Pengaturan::simpan('format_naskah_kepbup', json_encode(['margin' => ['kiri' => 4], 'huruf' => ['nama' => 'Comic"};body{x']]));

        $format = FormatDokumen::naskah(FormatDokumen::KEPBUP);

        expect($format['margin']['kiri'])->toBe(4.0)
            ->and($format['margin']['atas_pertama'])->toBe(8.0)
            ->and($format['huruf']['nama'])->toBe('Bookman Old Style')
            ->and($format['paraf']['jabatan'])->toHaveCount(3);
    });
});

it('pratinjau di samping formulir dibuat dari isian yang belum disimpan', function () {
    Storage::fake('local');
    $admin = tap(User::factory()->create())->assignRole('Admin');
    $lain = tap(User::factory()->create())->assignRole('Verifikator');

    // Sebelum dibuat belum ada berkasnya.
    $this->actingAs($admin)->get(route('format.pratinjau', 'nota'))->assertNotFound();

    $halaman = Livewire::actingAs($admin)->test(PengaturanFormat::class)
        ->set('nota.penutup', 'Kalimat penutup yang belum disimpan.')
        ->call('pratinjau', 'nota')
        ->assertSet('versiPratinjau', 1)
        ->assertSet('pratinjauGagal', false);

    $pdf = $this->actingAs($admin)->get(route('format.pratinjau', 'nota'))->assertOk()->assertHeader('Content-Type', 'application/pdf')->getContent();
    $teks = preg_replace('/\s+/', '', (new Smalot\PdfParser\Parser())->parseContent($pdf)->getText());

    expect($teks)->toContain('Kalimatpenutupyangbelumdisimpan')
        ->and(FormatDokumen::nota()['penutup'])->not->toContain('belum disimpan');

    // Pratinjau milik pengguna lain tidak terbuka.
    $this->actingAs($lain)->get(route('format.pratinjau', 'nota'))->assertNotFound();

    // Isian yang belum sah tidak memunculkan galat; pratinjau lama dibiarkan.
    $this->actingAs($admin);
    $halaman->set('nota.nomor', 'tanpa pola')->call('pratinjau', 'nota')
        ->assertHasNoErrors()->assertSet('pratinjauGagal', true)->assertSet('versiPratinjau', 1);

    $halaman->call('pratinjau', 'kepbup')->assertSet('versiPratinjau', 2)->assertSet('sumberPratinjau', '');
    $this->actingAs($admin)->get(route('format.pratinjau', 'kepbup'))->assertOk();
});

it('pratinjau memakai naskah sungguhan, yang sudah selesai lebih dulu', function () {
    Storage::fake('local');
    $admin = tap(User::factory()->create())->assignRole('Admin');
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    $alur = app(AlurNaskah::class);

    $buat = function (string $tentang, string $tahap) use ($pd, $alur) {
        $n = Naskah::create(['jenis' => 'Keputusan Bupati', 'tentang' => $tentang, 'pemrakarsa_id' => $pd->id]);
        $alur->isiBaris($n, [
            ['bagian' => 'judul', 'label' => null, 'teks' => 'JUDUL'],
            ['bagian' => 'diktum', 'label' => 'KESATU', 'teks' => "Isi diktum {$tentang}."],
        ]);
        $n->forceFill(['tahap' => $tahap])->save();

        return $n;
    };

    // Draf tidak dipakai: tanpa naskah lain, pratinjau tetap contoh titik-titik.
    $buat('Naskah Draf', 'draf');
    expect(app(CetakNaskah::class)->naskahUntukPratinjau('Keputusan Bupati'))->toBeNull();

    $buat('Naskah Berjalan', 'penelitian');
    $selesai = $buat('Naskah Selesai', 'selesai');
    $buat('Naskah Berjalan Terbaru', 'penelitian');

    Livewire::actingAs($admin)->test(PengaturanFormat::class)
        ->call('pratinjau', 'kepbup')
        ->assertSet('sumberPratinjau', "{$selesai->fresh()->nomor_tampil} · Naskah Selesai (Selesai)")
        ->assertSee('Memakai naskah');

    $teks = preg_replace('/\s+/', '', (new Smalot\PdfParser\Parser())->parseContent(
        $this->actingAs($admin)->get(route('format.pratinjau', 'kepbup'))->getContent()
    )->getText());

    expect($teks)->toContain('IsidiktumNaskahSelesai');
});

describe('menu Format Dokumen', function () {
    it('hanya untuk Admin, Verifikator, dan Super Admin', function () {
        $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');

        $this->actingAs($pd)->get(route('format'))->assertForbidden();
        $this->actingAs($pd)->get(route('format.gambar', 'kepbup'))->assertForbidden();
        $this->actingAs($this->verifikator)->get(route('format'))->assertOk()->assertSee('Format Dokumen')->assertSee('Paraf Hierarki');
        $this->actingAs($this->verifikator)->get(route('format.gambar', 'nota'))->assertOk();
    });

    it('menyimpan isian dengan tipe yang benar dan menolak isian tidak sah', function () {
        Livewire::actingAs($this->verifikator)->test(PengaturanFormat::class)
            ->set('kepbup.margin.kiri', '-1')
            ->set('kepbup.huruf.nama', 'Comic Sans')
            ->call('simpan', 'kepbup')
            ->assertHasErrors(['kepbup.margin.kiri', 'kepbup.huruf.nama'])
            ->set('kepbup.margin.kiri', '3')
            ->set('kepbup.huruf.nama', 'Times New Roman')
            ->call('tambahBaris', 'kepbup.paraf.jabatan')
            ->set('kepbup.paraf.jabatan.3', 'Asisten Administrasi Umum')
            ->call('hapusBaris', 'kepbup.paraf.jabatan', 0)
            ->call('simpan', 'kepbup')
            ->assertHasNoErrors();

        $format = FormatDokumen::naskah(FormatDokumen::KEPBUP);
        expect($format['margin']['kiri'])->toBe(3.0)
            ->and($format['huruf']['nama'])->toBe('Times New Roman')
            ->and($format['paraf']['jabatan'])->toBe(['Asisten Pemerintahan dan Kesra', 'Kepala Bagian Hukum', 'Asisten Administrasi Umum']);
    });

    it('pola nomor nota wajib memuat {nomor} dan langsung dipakai saat menerbitkan', function () {
        Livewire::actingAs($this->verifikator)->test(PengaturanFormat::class)
            ->set('tab', 'nota')
            ->set('nota.nomor', '100.3/ND/{tahun}')
            ->call('simpan', 'nota')
            ->assertHasErrors('nota.nomor')
            ->set('nota.nomor', '100.3/{nomor}/ND-HUK/{tahun}')
            ->assertSee('100.3/437/ND-HUK/' . now()->year)
            ->call('simpan', 'nota')
            ->assertHasNoErrors();

        expect(FormatDokumen::nomorNota(FormatDokumen::nota()['nomor'], '9', FormatDokumen::KEPBUP, 2026))->toBe('100.3/9/ND-HUK/2026');
    });

    it('lambang unggahan dipakai, path dari browser diabaikan, dan bisa kembali ke bawaan', function () {
        $komponen = Livewire::actingAs($this->verifikator)->test(PengaturanFormat::class)
            ->set('gambar.kepbup', UploadedFile::fake()->image('garuda.png', 200, 200))
            ->set('kepbup.kop.lambang', '../../.env')
            ->call('simpan', 'kepbup')
            ->assertHasNoErrors();

        $lambang = FormatDokumen::naskah(FormatDokumen::KEPBUP)['kop']['lambang'];
        expect($lambang)->toStartWith(FormatDokumen::FOLDER_GAMBAR . '/kepbup-');
        Storage::disk('local')->assertExists($lambang);

        $komponen->call('hapusGambar', 'kepbup');
        Storage::disk('local')->assertMissing($lambang);
        expect(FormatDokumen::naskah(FormatDokumen::KEPBUP)['kop']['lambang'])->toBeNull();
    });

    it('contoh diunduh dari isian yang belum disimpan, lalu kembali ke bawaan', function () {
        Livewire::actingAs($this->verifikator)->test(PengaturanFormat::class)
            ->set('kepbup.kop.teks', 'BELUM DISIMPAN')
            ->call('contoh', 'kepbup', 'pdf')
            ->assertFileDownloaded('Contoh Keputusan Bupati.pdf')
            ->call('contoh', 'kepbup', 'word')
            ->assertFileDownloaded('Contoh Keputusan Bupati.docx')
            ->call('contoh', 'nota')
            ->assertFileDownloaded('Contoh Nota Dinas.pdf');

        expect(FormatDokumen::naskah(FormatDokumen::KEPBUP)['kop']['teks'])->toBe('BUPATI HULU SUNGAI TENGAH');

        FormatDokumen::simpanNaskah(FormatDokumen::KEPBUP, [...FormatDokumen::naskah(FormatDokumen::KEPBUP), 'penutup' => ['tempat' => 'Pagat', 'kiri' => 8.0]]);
        Livewire::actingAs($this->verifikator)->test(PengaturanFormat::class)->call('kembalikan', 'kepbup');

        expect(FormatDokumen::naskah(FormatDokumen::KEPBUP)['penutup']['tempat'])->toBe('Barabai');
    });
});
