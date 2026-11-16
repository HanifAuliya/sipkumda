<?php

use App\Enums\TahapNaskah as T;
use App\Livewire\V2\DetailNaskah;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\Lonceng;
use App\Livewire\V2\PantauNaskah;
use App\Livewire\Profile\TandaTanganSaya;
use App\Support\FormatDokumen;
use App\Support\TandaTanganPeneliti;
use App\Livewire\Verifikator\TandaTangan\PengelolaTtd;
use App\Models\DokumentasiProdukHukum;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahBerkas;
use App\Models\PerangkatDaerah;
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
    $alur->jalankan($n, $uji->peneliti, 'setujui_fasilitasi');

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
            ->and($n->notaDinas->nomor_nota)->toBe('100.3/12/ND/KUM/' . now()->year);
    });

    it('pola nomor mengikuti menu Format Dokumen; {kode} menjadi NDSK atau NDPB', function () {
        $format = FormatDokumen::nota();
        FormatDokumen::simpanNota([...$format, 'nomor' => '180/{nomor}/{kode}/KUM/{tahun}']);

        $sk = $this->alur->terbitkanNota(naskahFinal($this, 'Keputusan Bupati'), $this->verifikator, '3', 'Sekda', $this->ttd->id);
        $perbup = $this->alur->terbitkanNota(naskahFinal($this), $this->verifikator, '3', 'Sekda', $this->ttd->id);

        expect($sk->notaDinas->nomor_nota)->toBe('180/3/NDSK/KUM/' . now()->year)
            ->and($perbup->notaDinas->nomor_nota)->toBe('180/3/NDPB/KUM/' . now()->year);
    });

    it('menolak nomor ganda, termasuk "012" dan "12"; tanpa {kode} satu urutan untuk semua jenis', function () {
        $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);

        expect(fn () => $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '012', 'Sekda', $this->ttd->id))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->terbitkanNota(naskahFinal($this, 'Keputusan Bupati'), $this->admin, '12', 'Sekda', $this->ttd->id))->toThrow(ValidationException::class);

        // Dengan {kode} di pola, jenis berbeda boleh memakai nomor yang sama.
        FormatDokumen::simpanNota([...FormatDokumen::nota(), 'nomor' => '180/{nomor}/{kode}/KUM/{tahun}']);
        $sk = $this->alur->terbitkanNota(naskahFinal($this, 'Keputusan Bupati'), $this->admin, '12', 'Sekda', $this->ttd->id);
        expect($sk->tahap)->toBe(T::Penandatanganan);
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

        expect($matriks)->not->toContain('DRAF')->toContain('100.3/12/ND/KUM')->toContain('NAMAKABAG')->toContain('19731002');
    });

    it('nota dinas PDF memuat data naskah dan bisa diunduh yang berhak', function () {
        $n = $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        $teks = teksPdfAkhir(app(CetakNaskah::class)->pdfNotaDinas($n));

        expect($teks)->toContain('100.3/12/ND/KUM')->toContain('DanaDesa')->toContain('DinasUji')->toContain('NAMAKABAG')
            ->toContain('KoreksiatasPeraturanBupatitentangDanaDesa.')->toContain('NOTADINAS');

        $this->actingAs($this->pd)->get(route('naskah.cetak', [$n, 'nota-dinas']))->assertOk()->assertDownload();
        $this->actingAs(tap(User::factory()->create())->assignRole('Perangkat Daerah'))->get(route('naskah.cetak', [$n, 'nota-dinas']))->assertForbidden();
    });

    it('dibekukan saat terbit; perubahan Format Dokumen baru berlaku setelah Admin menerbitkan ulang', function () {
        $n = $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        $cetak = app(CetakNaskah::class);
        $pertama = $n->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS)->sole();

        FormatDokumen::simpanNota([...FormatDokumen::nota(), 'penutup' => 'Atas perhatiannya diucapkan terima kasih.']);

        // Surat yang sudah terbit tidak ikut berubah.
        expect($pertama->aktif)->toBeTrue()
            ->and(teksPdfAkhir($cetak->pdfNotaDinas($n)))->toContain('Demikiandisampaikan')->not->toContain('terimakasih');

        // Yang menerbitkan nota dinas (Admin dan Verifikator) boleh menerbitkan ulang; peran lain tidak.
        expect($this->alur->bolehTerbitUlangNota($n, $this->verifikator))->toBeTrue()
            ->and(fn () => $this->alur->terbitUlangNota($n, $this->pd, 'Kop berganti'))->toThrow(HttpException::class)
            ->and(fn () => $this->alur->terbitUlangNota($n, $this->admin, ''))->toThrow(ValidationException::class);

        $kedua = $this->alur->terbitUlangNota($n, $this->admin, 'Kalimat penutup diperbarui');
        $versi = $n->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS)->reorder('id')->get();

        expect($versi)->toHaveCount(2)
            ->and($versi[0]->aktif)->toBeFalse()
            ->and($versi[1]->is($kedua))->toBeTrue()
            ->and($kedua->keterangan)->toBe('Kalimat penutup diperbarui')
            ->and($kedua->nama_asli)->toContain('versi 2')
            ->and(teksPdfAkhir($cetak->pdfNotaDinas($n)))->toContain('terimakasih')->toContain('100.3/12/ND/KUM')
            // Versi sebelum perubahan tetap bisa diunduh untuk dibandingkan.
            ->and(teksPdfAkhir(Storage::disk('local')->get($versi[0]->path)))->toContain('Demikiandisampaikan')
            ->and($n->riwayat()->where('aksi', 'terbit_ulang_nota')->value('catatan'))->toBe('Kalimat penutup diperbarui');
    });

    it('tanda tangan yang dipakai nota dinas v2 tidak bisa dihapus', function () {
        $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        // Kelola TTD kini menu Admin; Verifikator mengatur tanda tangannya di Profil.
        $this->actingAs($this->admin);

        Livewire::test(PengelolaTtd::class)->call('delete', $this->ttd->id);

        expect(TandaTangan::find($this->ttd->id))->not->toBeNull();
    });
});

describe('persetujuan fasilitasi', function () {
    beforeEach(function () {
        $alur = $this->alur;
        $this->n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Dana Desa', 'pemrakarsa_id' => $this->pd->id, 'nomor_nota' => 'ND-'.uniqid(), 'tanggal_nota' => now()]);
        $alur->isiBaris($this->n, [['bagian' => 'judul', 'label' => null, 'teks' => 'JUDUL']]);
        $this->n->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => 'x.pdf', 'nama_asli' => 'x.pdf', 'diunggah_oleh' => $this->pd->id]);
        $alur->jalankan($this->n, $this->pd, 'ajukan');
        $alur->jalankan($this->n, $this->admin, 'setujui_berkas');
        $alur->jalankan($this->n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);
        $this->n->baris()->get()->each(fn (B $b) => $alur->tetapkan($b, $this->peneliti));
        $alur->jalankan($this->n, $this->peneliti, 'teruskan_ke_verifikator');
        $alur->jalankan($this->n, $this->verifikator, 'setujui_rancangan');
        $this->n = $alur->jalankan($this->n, $this->pd, 'ajukan_fasilitasi');
    });

    it('dipegang Peneliti yang ditugaskan, bukan Admin', function () {
        $penelitiLain = tap(User::factory()->create())->assignRole('Peneliti');

        expect($this->n->tahap)->toBe(T::PersetujuanFasilitasi)
            ->and($this->alur->aksiTersedia($this->n, $this->peneliti))->toHaveKeys(['setujui_fasilitasi', 'tolak_fasilitasi'])
            ->and($this->alur->aksiTersedia($this->n, $this->admin))->toBe([])
            ->and($this->alur->aksiTersedia($this->n, $penelitiLain))->toBe([])
            ->and($this->alur->modeBaris($this->n, $this->peneliti))->toBeNull()
            ->and($this->peneliti->notifications()->where('data->slug', $this->n->slug)->where('data->tahap', 'persetujuan_fasilitasi')->exists())->toBeTrue();

        expect(fn () => $this->alur->jalankan($this->n, $this->admin, 'setujui_fasilitasi'))->toThrow(HttpException::class);

        $this->actingAs($this->peneliti)->get(route('daftar', 'persetujuan-fasilitasi'))->assertOk()->assertSee('Dana Desa');

        expect($this->alur->jalankan($this->n, $this->peneliti, 'setujui_fasilitasi')->tahap)->toBe(T::ValidasiFasilitasi);
    });

    it('Peneliti bisa mengembalikan ke PD dengan alasan', function () {
        expect(fn () => $this->alur->jalankan($this->n, $this->peneliti, 'tolak_fasilitasi'))->toThrow(ValidationException::class);
        expect($this->alur->jalankan($this->n, $this->peneliti, 'tolak_fasilitasi', 'Berkas kurang')->tahap)->toBe(T::Disetujui);
    });
});

describe('penandatanganan', function () {
    beforeEach(function () {
        $this->n = $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
    });

    it('urutan tanda tangan diatur Admin dari Pengaturan Naskah dan langsung berlaku', function () {
        $verifikator = $this->verifikator;

        // Verifikator boleh melihat, tidak boleh mengubah.
        Livewire::actingAs($verifikator)->test(\App\Livewire\V2\PengaturanNaskah::class)
            ->assertSee('Paraf Koordinasi')->assertSee('Hanya Admin yang bisa mengubah urutan ini.')
            ->call('mulaiUbahTahapan')->assertForbidden();

        $pengaturan = Livewire::actingAs($this->admin)->test(\App\Livewire\V2\PengaturanNaskah::class)
            ->call('mulaiUbahTahapan')
            ->assertSee('Ada 1 naskah yang sedang dalam tahap tanda tangan')
            ->call('hapusTahapan', 1)                     // Asisten dihapus
            ->call('tambahTahapan')
            ->set('tahapan.3.nama', 'Paraf Wakil Bupati')
            ->call('geserTahapan', 3, -1)                  // Wakil Bupati sebelum Bupati
            ->call('simpanTahapan')->assertHasNoErrors();

        expect(AlurNaskah::tahapanTtd())->toBe([
            'paraf_koordinasi' => 'Paraf Koordinasi', 'sekda' => 'Sekda', 'paraf_wakil_bupati' => 'Paraf Wakil Bupati', 'bupati' => 'Bupati',
        ]);

        // Nama ganda dan daftar kosong ditolak.
        $pengaturan->call('mulaiUbahTahapan')->set('tahapan.1.nama', 'paraf koordinasi')->call('simpanTahapan')->assertHasErrors('tahapan.1.nama');

        // Urutan baru langsung berlaku: empat tahapan, lalu naskah menunggu arsip.
        $n = $this->n;
        foreach (['paraf_koordinasi', 'sekda', 'paraf_wakil_bupati'] as $kunci) {
            expect($this->alur->tahapanBerikutnya($n))->toBe($kunci);
            $n = $this->alur->tandaiTahapan($n, $this->admin);
        }
        expect($n->tahap)->toBe(T::Penandatanganan);
        $n = $this->alur->tandaiTahapan($n, $this->admin);
        expect($n->tahap)->toBe(T::Arsip);

        // Kembali ke bawaan.
        Livewire::actingAs($this->admin)->test(\App\Livewire\V2\PengaturanNaskah::class)
            ->call('mulaiUbahTahapan')->call('bawaanTahapan')->call('simpanTahapan')->assertHasNoErrors();
        expect(AlurNaskah::tahapanTtd())->toBe(AlurNaskah::TAHAPAN_TTD);
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

    it('Tamu bisa mencatat tahapan seperti Admin dan diberi tahu', function () {
        $tamu = tap(User::factory()->create())->assignRole('Tamu');
        $baru = $this->alur->terbitkanNota(naskahFinal($this, tentang: 'Hibah'), $this->admin, '13', 'Sekda', $this->ttd->id);

        expect($this->alur->aksiTersedia($this->n, $tamu))->toHaveKey('tandai_tahapan')
            ->not->toHaveKey('buka_kunci_ttd')
            ->and($tamu->notifications()->where('data->slug', $baru->slug)->exists())->toBeTrue();

        $n = $this->alur->tandaiTahapan($this->n, $tamu);

        expect($n->tahapan_ttd)->toHaveKey('paraf_koordinasi');

        // Tamu memantau dari halaman pantau, bukan layar kerja matriks.
        $this->actingAs($tamu)->get(route('naskah.kerja', $n))->assertRedirect(route('naskah.pantau', $n));

        Livewire::actingAs($tamu)->test(PantauNaskah::class, ['naskah' => $n])
            ->assertSee('Pantau Tanda Tangan')
            ->assertSee('Berikutnya')
            ->call('bukaTahapan', 'asisten', 'tandai')
            ->assertSet('tanggalTtd', now()->toDateString())
            ->call('simpanTahapan')
            ->assertHasNoErrors();

        expect($n->fresh()->tahapan_ttd)->toHaveKey('asisten');
    });

    it('Tamu hanya melihat naskah sejak penandatanganan dan tidak bisa menjalankan tahap lain', function () {
        $tamu = tap(User::factory()->create())->assignRole('Tamu');
        $draf = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Masih Draf', 'pemrakarsa_id' => $this->pd->id]);
        $final = naskahFinal($this, tentang: 'Belum Ada Nota');

        expect($tamu->can('view', $this->n))->toBeTrue()
            ->and($tamu->can('view', $draf))->toBeFalse()
            ->and($tamu->can('view', $final))->toBeFalse()
            ->and($tamu->can('create', Naskah::class))->toBeFalse()
            ->and(Naskah::terlihatOleh($tamu)->pluck('id')->all())->toBe([$this->n->id])
            ->and(Naskah::menunggu($tamu)->pluck('id')->all())->toBe([$this->n->id])
            ->and($this->alur->modeBaris($this->n, $tamu))->toBeNull()
            ->and($this->alur->aksiTersedia($final, $tamu))->toBe([]);

        expect(fn () => $this->alur->terbitkanNota($final, $tamu, '77', 'Sekda', $this->ttd->id))->toThrow(HttpException::class);

        $this->actingAs($tamu)->get(route('naskah.kerja', $draf))->assertForbidden();
        $this->actingAs($tamu)->get(route('daftar', 'penandatanganan'))->assertOk()->assertSee($this->n->tentang)->assertDontSee('Belum Ada Nota');

        foreach (['paraf_koordinasi', 'asisten', 'sekda', 'bupati'] as $_) {
            $this->n = $this->alur->tandaiTahapan($this->n, $tamu);
        }

        expect($this->n->tahap)->toBe(T::Arsip)
            ->and($tamu->can('view', $this->n))->toBeTrue()
            ->and($this->alur->aksiTersedia($this->n, $tamu))->toBe([]);
    });

    it('Tamu hanya melihat: tidak bisa mengunduh berkas dan hasil cetak, juga tidak ada tombolnya', function () {
        $tamu = tap(User::factory()->create())->assignRole('Tamu');
        $berkas = $this->n->berkas()->first();
        Storage::disk('local')->put($berkas->path, 'isi');

        foreach (['naskah-word', 'naskah-pdf', 'matriks-pdf', 'nota-dinas'] as $jenis) {
            $this->actingAs($tamu)->get(route('naskah.cetak', [$this->n, $jenis]))->assertForbidden();
        }
        $this->actingAs($tamu)->get(route('naskah.berkas', [$this->n, $berkas]))->assertForbidden();
        $this->actingAs($this->admin)->get(route('naskah.berkas', [$this->n, $berkas]))->assertOk();

        $this->actingAs($tamu);
        $cetak = route('naskah.cetak', [$this->n, 'naskah-pdf']);

        Livewire::test(PantauNaskah::class, ['naskah' => $this->n])
            ->set('lihatIsi', true)
            ->assertSee('Isi pasal.')
            ->assertDontSeeHtml($cetak)
            ->assertDontSeeHtml('/cetak/')
            ->assertDontSeeHtml('Naskah Word');

        Livewire::test(DetailNaskah::class)
            ->call('buka', $this->n->slug)
            ->assertSee($this->n->notaDinas->nomor_nota)
            ->assertDontSeeHtml($cetak)
            ->assertDontSeeHtml('/cetak/');

        Livewire::actingAs($this->admin)->test(LayarKerja::class, ['naskah' => $this->n])
            ->set('tab', 'naskah')
            ->assertSeeHtml($cetak);
    });

    it('buka kunci ada di halaman Nota Dinas & Tanda Tangan dengan nama dan akibat yang jelas', function () {
        $n = $this->alur->tandaiTahapan($this->n, $this->admin);

        // Di layar kerja tidak ada lagi; Admin tidak punya tombolnya.
        Livewire::actingAs($this->verifikator)->test(LayarKerja::class, ['naskah' => $n])->assertDontSee('Kembalikan ke Validasi Fasilitasi');
        Livewire::actingAs($this->admin)->test(PantauNaskah::class, ['naskah' => $n])->assertDontSee('Isi naskah perlu diubah?');

        Livewire::actingAs($this->verifikator)->test(PantauNaskah::class, ['naskah' => $n])
            ->assertSee('Isi naskah perlu diubah?')
            ->assertSee('Kembalikan ke Validasi Fasilitasi')
            ->assertSee('harus dicatat ulang')
            ->call('bukaKunci')
            ->assertHasErrors('alasanBukaKunci')
            ->set('alasanBukaKunci', 'Koreksi dari Sekda')
            ->call('bukaKunci')
            ->assertRedirect(route('naskah.kerja', $n));

        expect($n->fresh()->tahap)->toBe(T::ValidasiFasilitasi)->and($n->fresh()->tahapan_ttd)->toBeNull();

        // Tombol mundur menyebut tahap tujuannya, dan konfirmasinya menyebut asal, tujuan, serta giliran.
        Livewire::actingAs($this->verifikator)->test(LayarKerja::class, ['naskah' => $n->fresh()])
            ->assertSee('Lainnya')
            ->assertSee('Kembalikan ke Perbaikan Fasilitasi (PD)')
            ->call('pilihAksi', 'tolak_validasi')
            ->assertSeeInOrder(['Naskah berpindah dari', 'Validasi Fasilitasi', 'Perbaikan Fasilitasi', 'Giliran berikutnya', 'Perangkat Daerah']);
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

    it('Keputusan Bupati bernomor lengkap mengikuti pola; hanya nomor urut yang wajib diisi', function () {
        $sk = $this->alur->terbitkanNota(naskahFinal($this, 'Keputusan Bupati', 'Tim Uji'), $this->admin, '13', 'Sekda', $this->ttd->id);
        foreach (AlurNaskah::TAHAPAN_TTD as $_) {
            $sk = $this->alur->tandaiTahapan($sk, $this->admin);
        }

        // Bagian selain nomor urut terisi dari pola di Format Dokumen dan bisa diganti per naskah.
        Livewire::actingAs($this->admin)->test(PantauNaskah::class, ['naskah' => $sk])
            ->assertSee('Nomor keputusan')
            ->assertSet('bagianNomorArsip', [0 => '140', 4 => '412.1.6'])
            ->set('nomorArsip', '1')->set('tanggalPenetapan', '2025-03-04')->set('fileArsip', $this->pdf)
            ->set('bagianNomorArsip.0', ' ')->call('arsipkan')->assertHasErrors('nomorArsip')
            ->set('bagianNomorArsip.0', '800')->call('arsipkan')->assertHasNoErrors();

        $d = DokumentasiProdukHukum::findOrFail($sk->fresh()->dokumentasi_id);

        expect($d->nomor)->toBe('1')
            ->and($d->pola_nomor)->toBe('800/{nomor}/412.1.6/TAHUN {tahun}')
            ->and($d->nomor_formatted)->toBe('Nomor 800/1/412.1.6/TAHUN 2025')
            // Daftar Dokumentasi menautkan dokumen ini ke naskah asalnya.
            ->and($d->naskah->is($sk))->toBeTrue();

        // Nomor urut yang dibetulkan lewat Daftar Dokumentasi ikut mengubah nomor lengkapnya.
        $d->update(['nomor' => '9']);
        expect($d->fresh()->nomor_lengkap)->toBe('800/9/412.1.6/TAHUN 2025');

        // Ubah dokumentasi: isian nomor SK terpecah sesuai pola yang tersimpan.
        Livewire::actingAs($this->admin)->test(\App\Livewire\Dokumentasi\Dokumentasi::class)
            ->call('edit', $d->id)
            ->assertSet('bagianNomor', [0 => '800', 4 => '412.1.6'])
            ->assertSee('Nomor Keputusan Bupati');

        // Peraturan Bupati tetap "Nomor 7 Tahun 2026", dan nomor urut tetap unik per tahun.
        $n = $this->alur->arsipkan($this->n, $this->admin, '7', '2026-11-01', null, null, $this->pdf);
        expect(DokumentasiProdukHukum::find($n->dokumentasi_id)->nomor_formatted)->toBe('Nomor 7 Tahun 2026');
    });

    it('antrean arsip menjadi tab di halaman Dokumentasi, khusus Bagian Hukum', function () {
        $this->actingAs($this->admin)->get(route('dokumentasi.main', ['tab' => 'menunggu']))
            ->assertOk()->assertSee('Menunggu Arsip')->assertSeeHtml("\$set('tab', 'menunggu')")->assertSee($this->n->tentang)->assertSee(route('naskah.pantau', $this->n));

        // Perangkat Daerah hanya melihat yang sudah terarsip.
        $this->actingAs($this->pd)->get(route('dokumentasi.main', ['tab' => 'menunggu']))
            // "Menunggu Arsip" bisa muncul di notifikasi sebagai nama tahap; yang diperiksa tombol tabnya.
            ->assertOk()->assertDontSeeHtml("\$set('tab', 'menunggu')")->assertDontSee(route('naskah.pantau', $this->n));
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

    it('arsip dikerjakan di halaman Nota Dinas & Tanda Tangan, dan tombol Kerjakan langsung ke sana', function () {
        $this->actingAs($this->admin);
        $halaman = route('naskah.pantau', $this->n);

        expect($this->n->tautanUntuk($this->admin))->toBe($halaman)
            ->and(T::Arsip->labelKerjakan())->toBe('Arsipkan')
            ->and(T::Final->labelKerjakan())->toBe('Terbitkan Nota Dinas')
            ->and(T::Penelitian->labelKerjakan())->toBe('Kerjakan');

        Livewire::test(LayarKerja::class, ['naskah' => $this->n])->call('pilihAksi', 'arsipkan')->assertRedirect($halaman);

        Livewire::test(PantauNaskah::class, ['naskah' => $this->n])
            ->assertSee('Arsipkan ke JDIH')
            ->set('nomorArsip', '8')
            ->set('tanggalPenetapan', '2026-11-01')
            ->call('arsipkan')
            ->assertHasErrors('fileArsip')
            ->set('fileArsip', UploadedFile::fake()->create('ttd.pdf', 10, 'application/pdf'))
            ->call('arsipkan')
            ->assertHasNoErrors()
            ->assertDontSee('PDF bertanda tangan basah');

        expect($this->n->fresh()->tahap)->toBe(T::Selesai)
            // Sesudah selesai, naskah dibuka lagi sebagai matriks untuk dibaca.
            ->and($this->n->fresh()->tautanUntuk($this->admin))->toBe(route('naskah.kerja', $this->n));
    });

    it('Arsipkan dari Sistem di halaman Dokumentasi memilih naskah, mengisi datanya, dan menyelesaikan naskah', function () {
        $form = fn () => Livewire::actingAs($this->admin)->test(\App\Livewire\Dokumentasi\AjukanDokumentasiProdukHukum::class);

        // Hanya naskah yang tanda tangannya lengkap yang bisa dipilih.
        $draf = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Masih Draf Sekali', 'pemrakarsa_id' => $this->pd->id]);
        $form()->call('open', 'sistem')
            ->assertSee('Arsipkan Naskah dari Sistem')
            ->assertSee($this->n->tentang)
            ->assertDontSee('Masih Draf Sekali');
        $form()->call('open', 'sistem')->set('naskahId', $draf->id)->assertSet('tentang_dokumentasi', null);

        $form()->call('open', 'sistem', $this->n->id)
            ->assertSet('jenis_dokumentasi', 'Peraturan Bupati')
            ->assertSet('tentang_dokumentasi', $this->n->tentang)
            ->assertSet('perangkat_daerah_id', $this->perangkat->id)
            ->set('nomor', '8')->set('nomor_berita', '8')->set('tahun_berita', '2026')
            ->set('tanggal_penetapan', '2026-10-01')->set('file_produk_hukum', $this->pdf)
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('closeModal', 'modalTambahDokumentasi');

        $n = $this->n->fresh();
        $d = DokumentasiProdukHukum::findOrFail($n->dokumentasi_id);

        expect($n->tahap)->toBe(T::Selesai)
            ->and($d->nomor_formatted)->toBe('Nomor 8 Tahun 2026')
            ->and($d->naskah->is($n))->toBeTrue();

        // Naskah yang sudah diarsipkan tidak bisa diarsipkan dua kali.
        $form()->call('open', 'sistem', $n->id)->assertSet('naskahId', $n->id)->assertSet('tentang_dokumentasi', null);
    });

    it('Tambah Arsip Lama tetap diisi manual tanpa naskah', function () {
        Livewire::actingAs($this->admin)->test(\App\Livewire\Dokumentasi\AjukanDokumentasiProdukHukum::class)
            ->call('open', 'lama')
            ->assertSee('Tambah Arsip Lama')
            ->assertSee('Jenis Produk Hukum')
            ->assertSet('sumber', 'lama');

        $this->actingAs($this->admin)->get(route('dokumentasi.main'))
            ->assertSee('Arsipkan dari Sistem')->assertSee('Tambah Arsip Lama');
    });
});

it('halaman Nota Dinas & Tanda Tangan menerbitkan nota dinas dan mencatat tahapan', function () {
    $n = naskahFinal($this);
    $this->actingAs($this->admin);
    $pd = $n->pemrakarsa->perangkatDaerah->nama_perangkat_daerah;

    // Dari layar kerja, tombol aksinya membawa ke halaman tersendiri; matriks tidak lagi memuat formulirnya.
    Livewire::test(LayarKerja::class, ['naskah' => $n])
        ->assertSeeHtml(route('naskah.pantau', $n))
        ->assertDontSee('Tahapan Tanda Tangan')
        ->call('pilihAksi', 'terbitkan_nota')
        ->assertRedirect(route('naskah.pantau', $n));

    $halaman = Livewire::test(PantauNaskah::class, ['naskah' => $n])
        ->assertSee('Nota Dinas & Tanda Tangan')
        // Tujuan diisi dari Perangkat Daerah pengaju; bagian nomor selain nomor urut tampil terkunci.
        ->assertSet('kepada', "Kepala {$pd}")
        ->assertSeeInOrder(['100.3', 'id="nomorInputan"', 'ND', 'KUM', (string) now()->year], false)
        ->call('terbitkanNota')
        ->assertHasErrors('nomorInputan')
        ->set('nomorInputan', '15')->set('tandaTanganId', $this->ttd->id)
        ->call('terbitkanNota')
        ->assertHasNoErrors()
        ->assertSee('100.3/15/ND/KUM/' . now()->year)
        ->assertSee('Versi 1')
        ->assertSee('Berlaku');

    expect($n->fresh()->tahap)->toBe(T::Penandatanganan)
        ->and($n->notaDinas()->first()->kepada)->toBe("Kepala {$pd}");

    $halaman->call('bukaTahapan', 'paraf_koordinasi', 'tandai')->assertSet('tanggalTtd', now()->toDateString())->call('simpanTahapan')->assertHasNoErrors();

    expect($n->fresh()->tahapan_ttd)->toHaveKey('paraf_koordinasi');

    // Terbit ulang: kedua versi tampil dan bisa diunduh.
    $halaman->call('terbitUlangNota')->assertHasErrors('alasanTerbitUlang')
        ->set('alasanTerbitUlang', 'Kop diperbarui')->call('terbitUlangNota')->assertHasNoErrors()
        ->assertSee('Versi 2')->assertSee('Diganti')->assertSee('Alasan: Kop diperbarui');

    // Salah pilih tanda tangan atau tujuan dibetulkan lewat terbit ulang; nomor tetap, perubahannya tercatat.
    $lain = TandaTangan::create([
        'nama_ttd' => 'NAMA SEKDA', 'nip_ttd' => '19700101', 'jabatan' => 'Pembina Utama',
        'file_ttd' => 'tanda_tangan/ttd/y.png', 'dibuat_oleh' => $this->verifikator->id, 'status' => 'Aktif',
    ]);

    $halaman->assertSet('ttdUlangId', $this->ttd->id)->assertSet('kepadaUlang', "Kepala {$pd}")
        ->set('ttdUlangId', $lain->id)->set('kepadaUlang', 'Kepala Dinas Lain')->set('alasanTerbitUlang', 'Salah pilih tanda tangan')
        ->call('terbitUlangNota')->assertHasNoErrors()
        ->assertSee('Versi 3')
        ->assertSee('penandatangan: NAMA KABAG → NAMA SEKDA')
        ->assertSee('kepada: Kepala Dinas Uji → Kepala Dinas Lain');

    $nota = $n->notaDinas()->first();

    expect($nota->tanda_tangan_id)->toBe($lain->id)
        ->and($nota->kepada)->toBe('Kepala Dinas Lain')
        ->and($nota->nomor_nota)->toBe('100.3/15/ND/KUM/' . now()->year)
        ->and(teksPdfAkhir(app(CetakNaskah::class)->pdfNotaDinas($n)))->toContain('NAMASEKDA');

    foreach ($n->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS)->get() as $versi) {
        $halaman->assertSeeHtml(route('naskah.berkas', [$n, $versi]));
        $this->get(route('naskah.berkas', [$n, $versi]))->assertOk();
    }
});

describe('kelengkapan layar', function () {
    it('lonceng menampilkan notifikasi sendiri dan membuka detail naskahnya', function () {
        $n = naskahFinal($this);
        $this->actingAs($this->pd);
        $notif = $this->pd->notifications()->latest()->first();

        Livewire::test(Lonceng::class)->assertSee($n->nomor)->call('buka', $notif->id)
            ->assertNoRedirect()
            ->assertDispatched('bukaDetailNaskah', slug: $n->slug);
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
        $this->actingAs($this->pd)->get(route('template', 'peraturan-bupati'))->assertOk()->assertDownload('Template Peraturan Bupati.docx');
        $this->actingAs($this->pd)->get(route('template', 'keputusan-bupati'))->assertOk();
        $this->actingAs($this->pd)->get('/template/lain')->assertNotFound();

        foreach (['Peraturan Bupati', 'Keputusan Bupati'] as $jenis) {
            $path = app(CetakNaskah::class)->wordTemplate($jenis);
            rename($path, $salinan = $path . '.docx');
            $hasil = (new PembacaNaskahWord())->baca($salinan, $jenis);
            unlink($salinan);

            expect($hasil['ringkasan'])->toHaveKeys(['judul', 'menimbang', 'mengingat', 'diktum', 'penutup', 'lampiran']);
        }
    });

});

describe('penomoran dan tabel', function () {
    it('mengenali tabel di isi pasal dan membuangnya dari pemisah', function () {
        $baris = TataLetakNaskah::barisTeks("Besaran:\n| Jabatan | Nilai |\n| --- | --- |\n| Ketua | Rp1 |\n(1) Lanjut.");

        expect($baris[1]['tabel'])->toBe([['Jabatan', 'Nilai'], ['Ketua', 'Rp1']])
            ->and($baris[2]['nomor'])->toBe('(1)');
    });

    it('membaca rata kolom dan baris judul dari baris pemisah', function () {
        $judul = TataLetakNaskah::barisTeks("| NO | NAMA | BESARAN |\n| :-: | --- | --: |\n| 1 | A | 150.000 |")[0];
        $tanpaJudul = TataLetakNaskah::barisTeks("| :-: | --- |\n| 1 | A |")[0];
        $susun = TataLetakNaskah::susunTabel($judul);

        expect($judul['judul'])->toBeTrue()
            ->and($judul['rata'])->toBe(['center', 'left', 'right'])
            ->and($tanpaJudul['judul'])->toBeFalse()
            ->and($tanpaJudul['tabel'])->toBe([['1', 'A']])
            ->and($susun[0]['sel'][2]['rata'])->toBe('center')
            ->and($susun[1]['sel'][2]['rata'])->toBe('right')
            ->and(TataLetakNaskah::selTabel('| Nama | : | Budi |'))->toBe(['Nama', ':', 'Budi']);
    });

    it('menyusun Memperhatikan sesudah Mengingat dengan nomor urut pada Keputusan Bupati', function () {
        $sk = new Naskah(['jenis' => 'Keputusan Bupati']);
        $baris = collect([
            ['mengingat', 'Undang-Undang Nomor 23 Tahun 2014;'],
            ['memperhatikan', 'Surat Rekomendasi Kesra;'],
            ['memperhatikan', 'Berita Acara Evaluasi;'],
            ['diktum', 'Menetapkan penerima hibah.'],
        ])->map(fn ($b, $i) => (new B(['bagian' => $b[0], 'rumusan_awal' => $b[1]]))->forceFill(['id' => $i + 1, 'status' => B::BELUM]));

        $daftar = array_values(array_filter(TataLetakNaskah::susun($sk, $baris), fn ($b) => $b['tipe'] === 'daftar'));
        $tunggal = App\Support\TemplateNaskah::penanda($sk, $baris->reject(fn ($b) => $b->id === 3));

        expect(array_column($daftar, 'label'))->toBe(['Mengingat', 'Memperhatikan'])
            ->and(array_column($daftar[1]['butir'], 'nomor'))->toBe(['1.', '2.'])
            ->and($tunggal[2])->toBeNull()
            ->and(App\Support\TemplateNaskah::bagian('Peraturan Bupati'))->not->toHaveKey('memperhatikan');
    });

    it('mencetak lampiran sesudah tanda tangan walaupun barisnya di atas penutup', function () {
        $baris = collect([
            ['judul', 'KEPUTUSAN BUPATI HULU SUNGAI TENGAH'],
            ['lampiran', "DAFTAR PENERIMA\n| NO | NAMA |\n| 1 | A |"],
            ['penutup', "Ditetapkan di Barabai\nBUPATI HULU SUNGAI TENGAH,\n\nNAMA"],
        ])->map(fn ($b, $i) => (new B(['bagian' => $b[0], 'rumusan_awal' => $b[1]]))->forceFill(['id' => $i + 1, 'status' => B::BELUM]));

        $tipe = array_column(TataLetakNaskah::susun(new Naskah(['jenis' => 'Keputusan Bupati']), $baris), 'tipe');

        expect(array_search('lampiran', $tipe))->toBeGreaterThan(array_search('penutup', $tipe))
            ->and(end($tipe))->toBe('ttd');
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

    it('butir bertingkat di Word memakai kolom gabungan, lebarnya pas dan sesuai jumlah kolom', function () {
        $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'x', 'pemrakarsa_id' => $this->pd->id]);
        $this->alur->isiBaris($n, [
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => "(1) Ayat satu:
a. huruf a:
1. angka satu;
2. angka dua;
b. huruf b.
(2) Ayat dua."],
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 2', 'teks' => "(1) Ayat:
a. huruf.
(2) Akhir."],
            ['bagian' => 'batang_tubuh', 'label' => 'Pasal 3', 'teks' => "(1) Hanya ayat.
(2) Tanpa butir."],
        ]);

        $path = app(CetakNaskah::class)->wordNaskah($n);
        $zip = new ZipArchive();
        $zip->open($path);
        $xml = $zip->getFromName('word/document.xml');
        $zip->close();
        unlink($path);

        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $x = new DOMXPath($dom);
        $x->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
        $jumlahKolom = [];

        foreach ($x->query('//w:tbl') as $tabel) {
            $grid = $x->query('w:tblGrid/w:gridCol', $tabel)->length;
            $lebarGrid = 0;
            foreach ($x->query('w:tblGrid/w:gridCol/@w:w', $tabel) as $w) {
                $lebarGrid += (int) $w->value;
            }
            $jumlahKolom[] = $grid;

            foreach ($x->query('w:tr', $tabel) as $tr) {
                $kolom = $lebar = 0;
                foreach ($x->query('w:tc', $tr) as $tc) {
                    $span = $x->query('w:tcPr/w:gridSpan/@w:val', $tc)->item(0)?->value;
                    $kolom += (int) ($span ?? 1);
                    $lebar += (int) $x->query('w:tcPr/w:tcW/@w:w', $tc)->item(0)->value;
                }

                expect($kolom)->toBe($grid)->and($lebar)->toBe($lebarGrid);
            }
        }

        expect($jumlahKolom)->toBe([4, 3, 2]);
    });

    it('tabel tercetak sebagai tabel di matriks, tanpa tanda garis', function () {
        $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'x', 'pemrakarsa_id' => $this->pd->id]);
        $this->alur->isiBaris($n, [['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => "Besaran:\n| Jabatan | Nilai |\n| Ketua & Wakil | Rp1 |\nSelesai."]]);

        $teks = teksPdfAkhir(app(CetakNaskah::class)->pdfMatriks($n));

        expect($teks)->toContain('Besaran:')->toContain('Ketua&Wakil')->toContain('Selesai.')->not->toContain('|');
    });

    it('tabel panjang dan sel yang sangat panjang di matriks tidak ada yang hilang', function () {
        $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'x', 'pemrakarsa_id' => $this->pd->id]);
        $sel = collect(range(1, 400))->map(fn (int $i) => "kata{$i}")->implode(' ');
        $baris = collect(range(1, 60))->map(fn (int $i) => "| baris{$i} | nilai{$i} | keterangan{$i} |")->implode("\n");
        $this->alur->isiBaris($n, [['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => "{$baris}\n| satu | {$sel} | tiga |\n| baris akhir | x | y |"]]);

        $teks = teksPdfAkhir(app(CetakNaskah::class)->pdfMatriks($n));

        foreach (['baris1', 'nilai30', 'keterangan60', 'kata1', 'kata200', 'kata400', 'barisakhir'] as $cari) {
            expect($teks)->toContain($cari);
        }
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

    it('pembaca matriks membaca nomor butir dari kolom tanpa judul di kiri Rumusan', function () {
        $word = new PhpWord();
        $tabel = $word->addSection()->addTable();

        // Judul kolom nomor butir hanya berisi spasi tak-putus, seperti pada matriks Ranperda.
        foreach ([
            ['No.', 'Materi', "\u{00A0}", 'Rumusan Ranperbup', 'Rumusan Penyempurnaan', 'Keterangan'],
            ['1', 'Batang Tubuh', '', ['Pasal 1', 'Dalam Peraturan Bupati ini yang dimaksud dengan:'], '', ''],
            ['', '', '1', 'Daerah adalah Kabupaten Hulu Sungai Tengah.', '', ''],
            ['', '', '2', 'Bupati adalah Bupati Hulu Sungai Tengah.', '', ''],
        ] as $sel) {
            $r = $tabel->addRow();
            foreach ($sel as $isi) {
                $c = $r->addCell(1000);
                foreach ((array) $isi as $paragraf) {
                    $c->addText($paragraf);
                }
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'matriks') . '.docx';
        IOFactory::createWriter($word, 'Word2007')->save($path);
        $pembaca = fn (string $bentuk) => collect((new PembacaNaskahWord())->baca($path, 'Peraturan Bupati', $bentuk)['baris']);
        $pasal = $pembaca(PembacaNaskahWord::BENTUK_MATRIKS)->firstWhere('label', 'Pasal 1')['teks'];
        $naskah = new PhpWord();
        $naskah->addSection()->addText('Menimbang: bahwa satu;');
        $pathNaskah = tempnam(sys_get_temp_dir(), 'naskah') . '.docx';
        IOFactory::createWriter($naskah, 'Word2007')->save($pathNaskah);

        expect($pasal)->toBe("Dalam Peraturan Bupati ini yang dimaksud dengan:\n1. Daerah adalah Kabupaten Hulu Sungai Tengah.\n2. Bupati adalah Bupati Hulu Sungai Tengah.")
            ->and(fn () => (new PembacaNaskahWord())->baca($pathNaskah, 'Peraturan Bupati', PembacaNaskahWord::BENTUK_MATRIKS))
            ->toThrow(RuntimeException::class, 'Tabel matriks');

        unlink($path);
        unlink($pathNaskah);
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

describe('tahapan tanda tangan bisa dibetulkan', function () {
    beforeEach(function () {
        $this->n = $this->alur->terbitkanNota(naskahFinal($this), $this->admin, '12', 'Sekda', $this->ttd->id);
        $this->tamu = tap(User::factory()->create())->assignRole('Tamu');
    });

    it('boleh tidak berurutan: dicatat di riwayat dan disimpan menurut urutan resmi', function () {
        $n = $this->alur->tandaiTahapan($this->n, $this->tamu, now()->subDay()->toDateString(), 'sekda');

        expect($n->tahapan_ttd)->toBe(['sekda' => now()->subDay()->toDateString()])
            ->and($n->riwayat()->first()->catatan)->toBe('Tidak berurutan: Paraf Koordinasi belum ditandai.');

        $n = $this->alur->tandaiTahapan($n, $this->tamu);

        expect(array_keys($n->tahapan_ttd))->toBe(['paraf_koordinasi', 'sekda'])
            ->and($n->riwayat()->first()->catatan)->toBeNull()
            ->and($this->alur->aksiTersedia($n, $this->admin)['tandai_tahapan']['label'])->toBe('Tandai Asisten selesai');

        expect(fn () => $this->alur->tandaiTahapan($n, $this->tamu, null, 'sekda'))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->tandaiTahapan($n, $this->tamu, null, 'camat'))->toThrow(HttpException::class);
    });

    it('tanggal bisa dibetulkan oleh Admin atau Tamu, tetapi tidak ke masa depan', function () {
        $n = $this->alur->tandaiTahapan($this->n, $this->tamu, now()->subDay()->toDateString());
        $n = $this->alur->ubahTanggalTahapan($n, $this->tamu, 'paraf_koordinasi', now()->subDays(3)->toDateString());

        expect($n->tahapan_ttd['paraf_koordinasi'])->toBe(now()->subDays(3)->toDateString());

        $riwayat = $n->riwayat()->first();
        expect([$riwayat->aksi, $riwayat->catatan, $riwayat->sebelum, $riwayat->sesudah])
            ->toBe(['tahapan_tanggal', 'Paraf Koordinasi', now()->subDay()->toDateString(), now()->subDays(3)->toDateString()]);

        expect(fn () => $this->alur->ubahTanggalTahapan($n, $this->tamu, 'paraf_koordinasi', now()->addDay()->toDateString()))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->ubahTanggalTahapan($n, $this->tamu, 'asisten', now()->toDateString()))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->ubahTanggalTahapan($n, $this->pd, 'paraf_koordinasi', now()->toDateString()))->toThrow(HttpException::class);
        expect(fn () => $this->alur->ubahTanggalTahapan($n, $this->verifikator, 'paraf_koordinasi', now()->toDateString()))->toThrow(HttpException::class);
    });

    it('tanda bisa dibatalkan; dari Menunggu Arsip naskah kembali ke Penandatanganan', function () {
        $n = $this->n;
        foreach (array_keys(AlurNaskah::TAHAPAN_TTD) as $_) {
            $n = $this->alur->tandaiTahapan($n, $this->tamu);
        }
        expect($n->tahap)->toBe(T::Arsip);

        // Selama menunggu arsip, tanggal masih bisa dibetulkan.
        $n = $this->alur->ubahTanggalTahapan($n, $this->tamu, 'bupati', now()->subDay()->toDateString());
        expect($n->tahap)->toBe(T::Arsip);

        $n = $this->alur->batalkanTahapan($n, $this->tamu, 'bupati');
        expect($n->tahap)->toBe(T::Penandatanganan)
            ->and($n->tahapan_ttd)->not->toHaveKey('bupati')
            ->and($n->catatan_tahap)->toBe('Tanda Bupati dibatalkan.')
            ->and($this->admin->notifikasiNaskah()->where('data->slug', $n->slug)->whereNull('read_at')->exists())->toBeTrue();

        foreach (['paraf_koordinasi', 'asisten', 'sekda'] as $kunci) {
            $n = $this->alur->batalkanTahapan($n, $this->admin, $kunci);
        }
        expect($n->tahapan_ttd)->toBeNull()->and($n->tahap)->toBe(T::Penandatanganan);

        expect(fn () => $this->alur->batalkanTahapan($n, $this->tamu, 'sekda'))->toThrow(ValidationException::class);

        $n->forceFill(['tahap' => T::Selesai, 'tahapan_ttd' => ['sekda' => now()->toDateString()]])->save();
        expect(fn () => $this->alur->batalkanTahapan($n, $this->admin, 'sekda'))->toThrow(HttpException::class);
        expect($this->alur->bolehKelolaTtd($n, $this->admin))->toBeFalse();
    });

    it('Admin mengubah tanggal dan membatalkan dari halaman Nota Dinas & Tanda Tangan', function () {
        $n = $this->alur->tandaiTahapan($this->n, $this->admin, now()->subDay()->toDateString());

        Livewire::actingAs($this->admin)->test(PantauNaskah::class, ['naskah' => $n])
            ->assertSee('Tahapan Tanda Tangan')
            ->assertSee('Ubah Tanggal')
            ->call('bukaTahapan', 'paraf_koordinasi', 'ubah')
            ->assertSet('tanggalTtd', now()->subDay()->toDateString())
            ->set('tanggalTtd', now()->addDay()->toDateString())
            ->call('simpanTahapan')
            ->assertHasErrors('tanggalTahapan')
            ->set('tanggalTtd', now()->subDays(2)->toDateString())
            ->call('simpanTahapan')
            ->assertHasNoErrors();

        expect($n->fresh()->tahapan_ttd['paraf_koordinasi'])->toBe(now()->subDays(2)->toDateString());

        Livewire::actingAs($this->admin)->test(PantauNaskah::class, ['naskah' => $n])->call('batalkanTahapan', 'paraf_koordinasi');

        expect($n->fresh()->tahapan_ttd)->toBeNull();
    });

    it('halaman pantau Tamu: tombol hilang setelah naskah diarsipkan', function () {
        $this->actingAs($this->tamu)->get(route('naskah.pantau', $this->n))->assertOk()->assertSee('Pantau Tanda Tangan');

        $this->n->forceFill(['tahap' => T::Selesai])->save();

        Livewire::actingAs($this->tamu)->test(PantauNaskah::class, ['naskah' => $this->n])
            ->assertSee('Naskah sudah diarsipkan')
            ->assertDontSee('Tandai Selesai')
            ->assertDontSee('Ubah Tanggal');

        // Akun dengan peran kerja tetap dibawa ke layar kerja.
        expect($this->n->tautanUntuk($this->admin))->toBe(route('naskah.kerja', $this->n))
            ->and($this->n->tautanUntuk($this->tamu))->toBe(route('naskah.pantau', $this->n));
    });
});

describe('ganti peneliti', function () {
    beforeEach(function () {
        $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Ganti Peneliti', 'pemrakarsa_id' => $this->pd->id, 'nomor_nota' => 'ND-'.uniqid(), 'tanggal_nota' => now()]);
        $this->alur->isiBaris($n, [['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => 'Isi pasal.']]);
        $n->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => 'x.pdf', 'nama_asli' => 'x.pdf', 'diunggah_oleh' => $this->pd->id]);
        $this->alur->jalankan($n, $this->pd, 'ajukan');
        $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
        $this->n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);
        $this->lain = tap(User::factory()->create())->assignRole('Peneliti');
    });

    it('Verifikator mengganti peneliti tanpa mengubah tahap; peneliti lama kehilangan akses', function () {
        expect($this->alur->aksiTersedia($this->n, $this->verifikator))->toHaveKey('ganti_peneliti')
            ->and($this->alur->aksiTersedia($this->n, $this->admin))->not->toHaveKey('ganti_peneliti')
            ->and($this->alur->aksiTersedia($this->n, $this->peneliti))->not->toHaveKey('ganti_peneliti');

        $n = $this->alur->gantiPeneliti($this->n, $this->verifikator, $this->lain->id, 'Peneliti lama cuti');

        expect($n->tahap)->toBe(T::Penelitian)
            ->and($n->peneliti_id)->toBe($this->lain->id)
            ->and($this->peneliti->can('view', $n))->toBeFalse()
            ->and($this->lain->can('view', $n))->toBeTrue()
            ->and($this->alur->modeBaris($n, $this->lain))->toBe(AlurNaskah::MODE_TELITI);

        $riwayat = $n->riwayat()->first();
        expect([$riwayat->aksi, $riwayat->sebelum, $riwayat->sesudah, $riwayat->catatan])
            ->toBe(['ganti_peneliti', $this->peneliti->nama_user, $this->lain->nama_user, 'Peneliti lama cuti']);

        $baru = $this->lain->notifikasiNaskah()->latest()->first()->data;
        expect($baru['kategori'])->toBe('tugas')->and($baru['title'])->toBe('Anda ditugaskan meneliti rancangan');

        expect($this->peneliti->notifikasiNaskah()->whereNull('read_at')->where('data->kategori', 'tugas')->exists())->toBeFalse()
            ->and($this->peneliti->notifikasiNaskah()->where('data->title', 'Anda tidak lagi menjadi peneliti naskah ini')->exists())->toBeTrue();
    });

    it('di luar giliran peneliti, peneliti baru hanya diberi kabar', function () {
        $this->n->baris()->get()->each(fn (B $b) => $this->alur->tetapkan($b, $this->peneliti));
        $n = $this->alur->jalankan($this->n, $this->peneliti, 'teruskan_ke_verifikator');

        $this->alur->gantiPeneliti($n, $this->verifikator, $this->lain->id);

        $data = $this->lain->notifikasiNaskah()->latest()->first()->data;
        expect($data['kategori'])->toBe('kabar')->and($data['title'])->toBe('Anda ditugaskan sebagai peneliti naskah ini');
    });

    it('menolak peneliti yang sama, bukan peneliti, pengguna tanpa hak, dan naskah terkunci', function () {
        expect(fn () => $this->alur->gantiPeneliti($this->n, $this->verifikator, $this->peneliti->id))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->gantiPeneliti($this->n, $this->verifikator, $this->pd->id))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->gantiPeneliti($this->n, $this->verifikator, null))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->gantiPeneliti($this->n, $this->admin, $this->lain->id))->toThrow(HttpException::class);

        $final = naskahFinal($this, tentang: 'Sudah Final');
        expect($this->alur->aksiTersedia($final, $this->verifikator))->not->toHaveKey('ganti_peneliti');
        expect(fn () => $this->alur->gantiPeneliti($final, $this->verifikator, $this->lain->id))->toThrow(HttpException::class);
    });

    it('dari layar kerja: peneliti yang sedang bertugas tidak ditawarkan', function () {
        Livewire::actingAs($this->verifikator)->test(LayarKerja::class, ['naskah' => $this->n])
            ->assertSee('Ganti Peneliti')
            ->call('pilihAksi', 'ganti_peneliti')
            ->assertSee('Peneliti sekarang')
            ->assertViewHas('peneliti', fn ($daftar) => ! $daftar->contains('id', $this->peneliti->id) && $daftar->contains('id', $this->lain->id))
            ->set('penelitiId', $this->lain->id)
            ->call('jalankanAksi')
            ->assertHasNoErrors();

        expect($this->n->fresh()->peneliti_id)->toBe($this->lain->id);
    });
});

/** PNG transparan 1000x400 dengan coretan berbentuk tangga di tengah. */
function ttdContoh(): UploadedFile
{
    $g = imagecreatetruecolor(1000, 400);
    imagealphablending($g, false);
    imagesavealpha($g, true);
    imagefill($g, 0, 0, imagecolorallocatealpha($g, 255, 255, 255, 127));
    $hitam = imagecolorallocatealpha($g, 0, 0, 0, 0);
    imagefilledrectangle($g, 300, 150, 499, 199, $hitam);
    imagefilledrectangle($g, 500, 200, 699, 249, $hitam);
    ob_start();
    imagepng($g);

    return UploadedFile::fake()->createWithContent('ttd.png', ob_get_clean());
}

describe('tanda tangan peneliti', function () {
    it('digambar ulang menjadi PNG transparan yang tepinya dipangkas', function () {
        // Tanda tangan tercetak di matriks resmi, jadi penggantiannya meminta kata sandi.
        Livewire::actingAs($this->peneliti)->test(TandaTanganSaya::class)
            ->set('gambar', ttdContoh())
            ->assertHasNoErrors()
            ->assertSee('Simpan Tanda Tangan')
            ->call('simpan')
            ->assertHasErrors(['sandiSekarang' => 'required'])
            ->set('sandiSekarang', 'salah')
            ->call('simpan')
            ->assertHasErrors(['sandiSekarang' => 'current_password'])
            ->set('sandiSekarang', 'password')
            ->call('simpan')
            ->assertHasNoErrors();

        $path = $this->peneliti->refresh()->file_ttd;
        $g = imagecreatefromstring(Storage::disk('local')->get($path));

        expect($path)->toStartWith('tanda_tangan/peneliti/')
            ->and([imagesx($g), imagesy($g)])->toBe([400, 100])
            ->and(imagecolorsforindex($g, imagecolorat($g, 399, 0))['alpha'])->toBe(127)
            ->and(imagecolorsforindex($g, imagecolorat($g, 0, 0))['alpha'])->toBe(0);

        $this->actingAs($this->peneliti)->get($this->peneliti->ttdUrl())->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs($this->peneliti)->get('/view-private/tanda_tangan/peneliti/' . basename($path))->assertForbidden();
    });

    it('hanya untuk Peneliti, dan menolak berkas yang bukan gambar', function () {
        Livewire::actingAs($this->pd)->test(TandaTanganSaya::class)->assertForbidden();

        Livewire::actingAs($this->peneliti)->test(TandaTanganSaya::class)
            ->set('gambar', UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'))
            ->assertHasErrors('gambar')
            ->assertSet('gambar', null);
    });

    it('masuk ke matriks di atas nama peneliti setelah nota dinas terbit', function () {
        $n = naskahFinal($this);
        $cetak = app(CetakNaskah::class);
        // Disimpan ke variabel: file sementaranya terhapus begitu objeknya dibuang.
        $gambar = ttdContoh();
        TandaTanganPeneliti::simpan($this->peneliti, $gambar->getRealPath());
        $this->peneliti->forceFill(['nama_user' => 'NAMA PENELITI', 'NIP' => '19880101'])->save();

        $draf = $cetak->pdfMatriks($n->fresh());
        expect(teksPdfAkhir($draf))->toContain('PENELITI')->toContain('NAMAPENELITI')->toContain('19880101')
            ->and(substr_count($draf, '/Subtype /Image'))->toBe(0);

        $n = $this->alur->terbitkanNota($n, $this->admin, '12', 'Sekda', $this->ttd->id);

        // Gambar Kabag di uji ini tidak punya file, jadi gambar yang ada adalah tanda tangan peneliti
        // (PNG transparan tersimpan sebagai gambar dan topeng transparansinya).
        expect(substr_count($cetak->pdfMatriks($n->fresh()), '/Subtype /Image'))->toBeGreaterThan(0);
    });
});
