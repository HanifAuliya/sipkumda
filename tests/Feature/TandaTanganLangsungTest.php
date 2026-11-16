<?php

use App\Livewire\Profile\TandaTanganKabag;
use App\Livewire\Profile\TandaTanganSaya;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\PantauNaskah;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\PerangkatDaerah;
use App\Models\TandaTangan;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\CetakNaskah;
use App\Support\TandaTanganLangsung;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

/*
 * Tanda tangan yang digambar langsung di layar: di Profil, saat Kepala Bagian Hukum menerbitkan nota
 * dinas, dan saat Peneliti menyelesaikan fasilitasi (tanda tangan matriks).
 */

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');

    $this->alur = app(AlurNaskah::class);
    $this->pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Uji'])->id]))->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->kabag = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create())->assignRole('Peneliti');
    $this->ttdKabag = TandaTangan::create([
        'user_id' => $this->kabag->id, 'nama_ttd' => 'KABAG', 'nip_ttd' => '1975', 'jabatan' => 'Pembina',
        'file_ttd' => 'tanda_tangan/ttd/tersimpan.png', 'dibuat_oleh' => $this->kabag->id, 'status' => 'Aktif',
    ]);
});

/** Data URL PNG seperti yang dikirim kotak tanda tangan: latar transparan, kosong bila $goresan false. */
function coretanTtd(bool $goresan = true): string
{
    $g = imagecreatetruecolor(500, 180);
    imagealphablending($g, false);
    imagesavealpha($g, true);
    imagefill($g, 0, 0, imagecolorallocatealpha($g, 0, 0, 0, 127));
    if ($goresan) {
        imagesetthickness($g, 3);
        imageline($g, 60, 120, 420, 60, imagecolorallocatealpha($g, 20, 32, 107, 0));
    }
    ob_start();
    imagepng($g);

    return 'data:image/png;base64,' . base64_encode(ob_get_clean());
}

/** Naskah di Persetujuan Fasilitasi, giliran peneliti. */
function naskahFasilitasi(object $uji): Naskah
{
    $alur = $uji->alur;
    $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Dana Desa', 'pemrakarsa_id' => $uji->pd->id, 'nomor_nota' => 'ND-' . uniqid(), 'tanggal_nota' => now()]);
    $alur->isiBaris($n, [['bagian' => 'judul', 'label' => null, 'teks' => 'JUDUL'], ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => 'Isi.']]);
    $n->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => 'x.pdf', 'nama_asli' => 'x.pdf', 'diunggah_oleh' => $uji->pd->id]);
    $alur->jalankan($n, $uji->pd, 'ajukan');
    $alur->jalankan($n, $uji->admin, 'setujui_berkas');
    $alur->jalankan($n, $uji->kabag, 'tugaskan_peneliti', null, $uji->peneliti->id);
    $n->baris()->get()->each(fn (B $b) => $alur->tetapkan($b, $uji->peneliti));
    $alur->jalankan($n, $uji->peneliti, 'teruskan_ke_verifikator');
    $alur->jalankan($n, $uji->kabag, 'setujui_rancangan');

    $alur->simpanNaskahFasilitasi($n, $uji->pd, 'isi', 'naskah-perbaikan.docx');

    return $alur->jalankan($n, $uji->pd, 'ajukan_fasilitasi');
}

it('menolak tanda tangan yang kosong atau bukan PNG', function () {
    expect(fn () => TandaTanganLangsung::fileSementara('', 'ttd'))->toThrow(ValidationException::class, 'masih kosong')
        ->and(fn () => TandaTanganLangsung::fileSementara(coretanTtd(false), 'ttd'))->toThrow(ValidationException::class, 'masih kosong')
        ->and(fn () => TandaTanganLangsung::fileSementara('data:image/png;base64,bukan-gambar', 'ttd'))->toThrow(ValidationException::class, 'tidak bisa dibaca')
        ->and(fn () => TandaTanganLangsung::fileSementara('data:text/html;base64,' . base64_encode('<b>x</b>'), 'ttd'))->toThrow(ValidationException::class, 'tidak bisa dibaca');

    $path = TandaTanganLangsung::fileSementara(coretanTtd(), 'ttd');
    expect(getimagesize($path)[2])->toBe(IMAGETYPE_PNG);
    @unlink($path);
});

it('Peneliti dan Kepala Bagian Hukum bisa menggambar tanda tangannya di Profil', function () {
    Livewire::actingAs($this->peneliti)->test(TandaTanganSaya::class)
        ->call('mulaiGambar')
        ->assertSee('Kotak tanda tangan', false)
        ->set('sandiSekarang', 'password')
        ->call('simpan')
        ->assertHasErrors('gambar')
        ->set('ttdLangsung', coretanTtd())
        ->call('simpan')
        ->assertHasNoErrors()
        ->assertSet('menggambar', false);
    expect($this->peneliti->refresh()->file_ttd)->toStartWith('tanda_tangan/peneliti/');

    $lama = $this->ttdKabag->file_ttd;
    Livewire::actingAs($this->kabag)->test(TandaTanganKabag::class)
        ->call('mulaiUbah')
        ->call('gantiCara', true)
        ->set('ttdLangsung', coretanTtd())
        ->set('sandiSekarang', 'password')
        ->call('simpan')
        ->assertHasNoErrors();
    expect($this->ttdKabag->refresh()->file_ttd)->not->toBe($lama);
    Storage::disk('local')->assertExists($this->ttdKabag->file_ttd);
});

describe('nota dinas', function () {
    beforeEach(function () {
        $n = naskahFasilitasi($this);
        $this->alur->jalankan($n, $this->peneliti, 'setujui_fasilitasi');
        $this->n = $this->alur->jalankan($n, $this->kabag, 'finalkan');
    });

    it('Kepala Bagian Hukum bisa menandatangani langsung saat menerbitkan sendiri', function () {
        Livewire::actingAs($this->kabag)->test(PantauNaskah::class, ['naskah' => $this->n])
            ->assertSet('tandaTanganId', $this->ttdKabag->id)
            ->assertSee('Tanda tangan sekarang')
            ->set('nomorInputan', '7')
            ->set('sumberTtd', 'langsung')
            ->call('terbitkanNota')
            ->assertHasErrors('ttd')
            ->set('ttdLangsung', coretanTtd())
            ->call('terbitkanNota')
            ->assertHasNoErrors();

        $nota = $this->n->refresh()->notaDinas;
        expect($nota->tanda_tangan_id)->toBe($this->ttdKabag->id)
            ->and($nota->file_ttd)->toStartWith('tanda_tangan/ttd/nota_')
            ->and($nota->pathTandaTangan())->toEndWith(basename($nota->file_ttd))
            // Tanpa "simpan juga", tanda tangan tersimpannya tidak berubah.
            ->and($this->ttdKabag->refresh()->file_ttd)->toBe('tanda_tangan/ttd/tersimpan.png');
    });

    it('bisa sekaligus menyimpannya sebagai tanda tangan tersimpan', function () {
        Livewire::actingAs($this->kabag)->test(PantauNaskah::class, ['naskah' => $this->n])
            ->set('nomorInputan', '8')->set('sumberTtd', 'langsung')->set('ttdLangsung', coretanTtd())->set('simpanTtd', true)
            ->call('terbitkanNota')->assertHasNoErrors();

        expect($this->ttdKabag->refresh()->file_ttd)->not->toBe('tanda_tangan/ttd/tersimpan.png');
    });

    it('Admin hanya memilih tanda tangan tersimpan, tidak menggambar atas nama orang lain', function () {
        Livewire::actingAs($this->admin)->test(PantauNaskah::class, ['naskah' => $this->n])
            ->assertDontSee('Tanda tangan sekarang')
            ->set('nomorInputan', '9')->set('sumberTtd', 'langsung')->set('ttdLangsung', coretanTtd())
            ->call('terbitkanNota')
            ->assertForbidden();

        $gambar = TandaTanganLangsung::fileSementara(coretanTtd(), 'ttd');
        expect(fn () => $this->alur->terbitkanNota($this->n, $this->admin, '9', 'Kepala Dinas', $this->ttdKabag->id, $gambar))
            ->toThrow(ValidationException::class, 'hanya untuk tanda tangan Anda sendiri');
        @unlink($gambar);
    });

    it('terbit ulang dengan penanda tangan lain membuang tanda tangan langsung yang lama', function () {
        Livewire::actingAs($this->kabag)->test(PantauNaskah::class, ['naskah' => $this->n])
            ->set('nomorInputan', '10')->set('sumberTtd', 'langsung')->set('ttdLangsung', coretanTtd())->call('terbitkanNota');
        $plt = TandaTangan::create(['nama_ttd' => 'PLT', 'nip_ttd' => '1980', 'jabatan' => 'Penata', 'file_ttd' => 'tanda_tangan/ttd/plt.png', 'dibuat_oleh' => $this->admin->id, 'status' => 'Aktif']);

        $this->alur->terbitUlangNota($this->n->refresh(), $this->admin, 'Kabag cuti', $plt->id, null);

        expect($this->n->refresh()->notaDinas->file_ttd)->toBeNull();
    });
});

describe('tanda tangan matriks peneliti', function () {
    beforeEach(function () {
        $this->n = naskahFasilitasi($this);
    });

    it('dibubuhkan saat Peneliti menyelesaikan fasilitasi', function () {
        $layar = Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $this->n])
            ->call('pilihAksi', 'setujui_fasilitasi')
            ->assertSee('Tanda Tangan Matriks')
            // Belum punya tanda tangan tersimpan: langsung diarahkan menggambar.
            ->assertSet('sumberTtd', 'langsung')
            ->call('jalankanAksi')
            ->assertHasErrors('ttd');

        $layar->set('sumberTtd', 'tersimpan')->call('jalankanAksi')->assertHasErrors('ttd');

        $layar->set('sumberTtd', 'langsung')->set('ttdLangsung', coretanTtd())->set('simpanTtd', true)
            ->call('jalankanAksi')->assertHasNoErrors();

        $n = $this->n->refresh();
        expect($n->tahap->value)->toBe('validasi_fasilitasi')
            ->and($n->ttd_peneliti)->toStartWith('tanda_tangan/naskah/')
            // "Simpan juga": tanda tangan di Profil ikut terisi.
            ->and($this->peneliti->refresh()->file_ttd)->not->toBeNull();
        Storage::disk('local')->assertExists($n->ttd_peneliti);
    });

    it('memakai yang di matriks walau tanda tangan di Profil dihapus, dan diganti saat diperiksa ulang', function () {
        Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $this->n])
            ->call('pilihAksi', 'setujui_fasilitasi')->set('ttdLangsung', coretanTtd())->call('jalankanAksi')->assertHasNoErrors();
        $pertama = $this->n->refresh()->ttd_peneliti;

        // Verifikator meminta perbaikan; PD memperbaiki; peneliti memeriksa ulang dan menandatangani lagi.
        $this->alur->jalankan($this->n, $this->kabag, 'tolak_validasi', 'Perbaiki pasal 1');
        $this->alur->jalankan($this->n, $this->pd, 'perbaikan_ke_peneliti');
        Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $this->n->refresh()])
            ->call('pilihAksi', 'cek_ke_verifikator')->assertSee('Tanda Tangan Matriks')
            ->set('ttdLangsung', coretanTtd())->call('jalankanAksi')->assertHasNoErrors();

        $kedua = $this->n->refresh()->ttd_peneliti;
        expect($kedua)->not->toBe($pertama);
        Storage::disk('local')->assertMissing($pertama);

        // Setelah nota dinas terbit, matriks memuat gambar walau peneliti tidak punya tanda tangan di Profil.
        $this->alur->jalankan($this->n, $this->kabag, 'finalkan');
        $n = $this->alur->terbitkanNota($this->n->refresh(), $this->admin, '11', 'Kepala Dinas', $this->ttdKabag->id);
        expect($this->peneliti->refresh()->file_ttd)->toBeNull()
            ->and(substr_count(app(CetakNaskah::class)->pdfMatriks($n->fresh()), '/Subtype /Image'))->toBeGreaterThan(0);
    });

    it('dibuang saat peneliti diganti', function () {
        Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $this->n])
            ->call('pilihAksi', 'setujui_fasilitasi')->set('ttdLangsung', coretanTtd())->call('jalankanAksi');
        $path = $this->n->refresh()->ttd_peneliti;
        $baru = tap(User::factory()->create())->assignRole('Peneliti');

        $this->alur->gantiPeneliti($this->n, $this->kabag, $baru->id, null);

        expect($this->n->refresh()->ttd_peneliti)->toBeNull();
        Storage::disk('local')->assertMissing($path);
    });
});
