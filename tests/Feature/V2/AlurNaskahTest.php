<?php

use App\Enums\TahapNaskah as T;
use App\Livewire\NotificationDropdown;
use App\Livewire\V2\BarisKerja;
use App\Livewire\V2\BuatNaskah;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\Lonceng;
use App\Livewire\V2\PratinjauNaskah;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\PembacaNaskahWord;
use App\Support\BandingNaskah;
use App\Support\BlokNaskah;
use App\Support\DiffKata;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
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
    $this->pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create())->assignRole('Peneliti');
});

/** Naskah draf lengkap dengan nota dinas, siap diajukan. */
function naskahDraf(User $pd, string $jenis = 'Peraturan Bupati'): Naskah
{
    $naskah = Naskah::create(['jenis' => $jenis, 'tentang' => 'Dana Desa', 'pemrakarsa_id' => $pd->id, 'nomor_nota' => 'ND-'.uniqid()]);

    app(AlurNaskah::class)->isiBaris($naskah, [
        ['bagian' => 'judul', 'label' => null, 'teks' => 'PERATURAN BUPATI'],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa satu'],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa dua'],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => 'Pembayaran paling lambat 7 hari.'],
    ]);

    $naskah->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => 'x.pdf', 'nama_asli' => 'x.pdf', 'diunggah_oleh' => $pd->id]);

    return $naskah;
}

/** Membawa naskah sampai tahap Penelitian. */
function naskahPenelitian(object $uji): Naskah
{
    $n = naskahDraf($uji->pd);
    $uji->alur->jalankan($n, $uji->pd, 'ajukan');
    $uji->alur->jalankan($n, $uji->admin, 'setujui_berkas');

    return $uji->alur->jalankan($n, $uji->verifikator, 'tugaskan_peneliti', null, $uji->peneliti->id);
}

function semuaTetap(object $uji, Naskah $n): void
{
    $n->baris()->get()->each(fn (B $b) => $uji->alur->tetapkan($b, $uji->peneliti));
}

describe('alur lengkap', function () {
    it('berjalan dari draf sampai final dengan putaran perbaikan', function () {
        $n = naskahPenelitian($this);
        expect($n->tahap)->toBe(T::Penelitian)->and($n->peneliti_id)->toBe($this->peneliti->id);

        // Peneliti meminta PD memperbaiki satu baris dan menetapkan sisanya.
        $pasal = $n->baris()->where('label', 'Pasal 1')->first();
        $n->baris()->where('id', '!=', $pasal->id)->get()->each(fn (B $b) => $this->alur->tetapkan($b, $this->peneliti));
        $this->alur->mintaPerbaikan($pasal, $this->peneliti, 'Sesuaikan batas waktu');

        expect(array_keys($this->alur->aksiTersedia($n->fresh(), $this->peneliti)))->toBe(['kirim_ke_pd']);
        $n = $this->alur->jalankan($n, $this->peneliti, 'kirim_ke_pd');
        expect($n->tahap)->toBe(T::PerbaikanPd);

        // PD belum bisa mengirim sebelum baris itu disesuaikan.
        expect(fn () => $this->alur->jalankan($n, $this->pd, 'kembali_ke_peneliti'))->toThrow(ValidationException::class);

        $this->alur->sesuaikan($pasal, $this->pd, 'Pembayaran paling lambat 14 hari kerja.');
        $n = $this->alur->jalankan($n, $this->pd, 'kembali_ke_peneliti');
        expect($n->tahap)->toBe(T::Penelitian);

        // Peneliti meneruskan, Verifikator menolak: kembali ke Peneliti.
        $n = $this->alur->jalankan($n, $this->peneliti, 'teruskan_ke_verifikator');
        $n = $this->alur->jalankan($n, $this->verifikator, 'tolak_rancangan', 'Belum pas');
        expect($n->tahap)->toBe(T::Penelitian)->and($n->catatan_tahap)->toBe('Belum pas');

        $n = $this->alur->jalankan($n, $this->peneliti, 'teruskan_ke_verifikator');
        $n = $this->alur->jalankan($n, $this->verifikator, 'setujui_rancangan');
        $n = $this->alur->jalankan($n, $this->pd, 'ajukan_fasilitasi');
        $n = $this->alur->jalankan($n, $this->peneliti, 'setujui_fasilitasi');

        // Ditolak di fasilitasi: ke PD, lalu Peneliti mengecek, baru Verifikator.
        $n = $this->alur->jalankan($n, $this->verifikator, 'tolak_validasi', 'Perbaiki');
        expect($n->tahap)->toBe(T::PerbaikanFasilitasi);
        $n = $this->alur->jalankan($n, $this->pd, 'perbaikan_ke_peneliti');
        expect($n->tahap)->toBe(T::CekPeneliti);
        $n = $this->alur->jalankan($n, $this->peneliti, 'cek_ke_verifikator');
        expect($n->tahap)->toBe(T::ValidasiFasilitasi);

        $n = $this->alur->jalankan($n, $this->verifikator, 'finalkan');
        expect($n->tahap)->toBe(T::Final)->and($n->terkunci_at)->not->toBeNull();

        expect($pasal->fresh()->teksAkhir())->toBe('Pembayaran paling lambat 14 hari kerja.')
            ->and($pasal->fresh()->rumusan_awal)->toBe('Pembayaran paling lambat 7 hari.')
            ->and($n->versi()->count())->toBeGreaterThan(5);
    });

    it('buka kunci wajib beralasan dan hanya untuk Verifikator', function () {
        $n = naskahPenelitian($this);
        semuaTetap($this, $n);
        $n = $this->alur->jalankan($n, $this->peneliti, 'teruskan_ke_verifikator');
        $n = $this->alur->jalankan($n, $this->verifikator, 'setujui_rancangan');
        $n = $this->alur->jalankan($n, $this->pd, 'ajukan_fasilitasi');
        $n = $this->alur->jalankan($n, $this->peneliti, 'setujui_fasilitasi');
        $n = $this->alur->jalankan($n, $this->verifikator, 'finalkan');

        expect(fn () => $this->alur->jalankan($n, $this->verifikator, 'buka_kunci'))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->jalankan($n, $this->admin, 'buka_kunci', 'x'))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);

        $n = $this->alur->jalankan($n, $this->verifikator, 'buka_kunci', 'Koreksi dari Sekda');
        expect($n->tahap)->toBe(T::ValidasiFasilitasi)->and($n->terkunci_at)->toBeNull();
    });
});

describe('nomor rancangan', function () {
    it('diberikan saat diajukan, berurutan, dan draf yang dibuang tidak memakai nomor', function () {
        $tahun = now()->year;
        $pertama = naskahDraf($this->pd);
        $dibuang = naskahDraf($this->pd);
        $kedua = naskahDraf($this->pd);

        expect($pertama->nomor)->toBeNull()->and($pertama->nomor_tampil)->toBe('Draf');

        $this->alur->buangDraf($dibuang, $this->pd);
        $this->alur->jalankan($kedua, $this->pd, 'ajukan');
        $this->alur->jalankan($pertama, $this->pd, 'ajukan');

        // Urutan nomor mengikuti urutan pengajuan, bukan urutan pembuatan draf.
        expect($kedua->fresh()->nomor)->toBe("PB-{$tahun}-001")
            ->and($pertama->fresh()->nomor)->toBe("PB-{$tahun}-002")
            ->and($pertama->nomor)->toBe("PB-{$tahun}-002");
    });

    it('tetap sama saat dikembalikan dan diajukan ulang, dan hanya Admin yang bisa menghapusnya', function () {
        $n = naskahDraf($this->pd);
        $this->alur->jalankan($n, $this->pd, 'ajukan');
        $nomor = $n->fresh()->nomor;

        $this->alur->jalankan($n, $this->admin, 'tolak_berkas', 'Nota dinas belum ditandatangani.');
        $this->alur->jalankan($n, $this->pd, 'ajukan');

        expect($n->fresh()->nomor)->toBe($nomor)
            ->and($this->alur->bolehBuang($n->fresh(), $this->pd))->toBeFalse()
            ->and($this->alur->bolehBuang($n->fresh(), $this->admin))->toBeTrue();

        $this->alur->jalankan($n, $this->admin, 'tolak_berkas', 'Lengkapi lagi.');
        expect(fn () => $this->alur->buangDraf($n->fresh(), $this->pd))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);

        $this->alur->buangDraf($n->fresh(), $this->admin);
        $baru = naskahDraf($this->pd);
        $this->alur->jalankan($baru, $this->pd, 'ajukan');

        // Nomor yang dihapus Admin tidak dipakai lagi.
        expect(Naskah::find($n->id))->toBeNull()
            ->and($baru->fresh()->nomor)->not->toBe($nomor);
    });
});

describe('aturan giliran', function () {
    it('menolak aksi di luar giliran', function () {
        $n = naskahDraf($this->pd);

        expect(fn () => $this->alur->jalankan($n, $this->admin, 'ajukan'))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
        expect(fn () => $this->alur->jalankan($n, $this->pd, 'setujui_berkas'))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('menolak klik ganda perpindahan tahap', function () {
        $n = naskahDraf($this->pd);
        $this->alur->jalankan($n, $this->pd, 'ajukan');

        expect(fn () => $this->alur->jalankan($n, $this->pd, 'ajukan'))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('hanya peneliti yang ditugaskan yang bisa mengubah baris', function () {
        $n = naskahPenelitian($this);
        $lain = tap(User::factory()->create())->assignRole('Peneliti');

        expect(fn () => $this->alur->tetapkan($n->baris()->first(), $lain))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
        expect(fn () => $this->alur->tetapkan($n->baris()->first(), $this->pd))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('PD tidak bisa mengajukan tanpa nota dinas', function () {
        $n = naskahDraf($this->pd);
        $n->berkas()->delete();

        expect(fn () => $this->alur->jalankan($n, $this->pd, 'ajukan'))->toThrow(ValidationException::class);
    });

    it('peneliti tidak bisa meneruskan selama ada baris belum diperiksa', function () {
        $n = naskahPenelitian($this);

        expect(fn () => $this->alur->jalankan($n, $this->peneliti, 'teruskan_ke_verifikator'))->toThrow(ValidationException::class);
    });

    it('naskah final tidak bisa diubah', function () {
        $n = naskahPenelitian($this);
        $n->update(['tahap' => T::Final]);

        expect(fn () => $this->alur->tetapkan($n->baris()->first(), $this->verifikator))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });
});

describe('baris matriks', function () {
    it('kolom 3 beku setelah diajukan', function () {
        $n = naskahPenelitian($this);

        expect(fn () => $this->alur->susun($n->baris()->first(), $this->pd, null, 'ubah'))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
        expect(fn () => $this->alur->susun($n->baris()->first(), $this->peneliti, null, 'ubah'))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);
    });

    it('teks yang sama dengan rumusan awal dianggap tetap', function () {
        $n = naskahPenelitian($this);
        $b = $this->alur->perbaiki($n->baris()->first(), $this->peneliti, 'PERATURAN   BUPATI');

        expect($b->status)->toBe(B::TETAP)->and($b->rumusan_penyempurnaan)->toBeNull();
    });

    it('baris dihapus tidak masuk naskah dan tidak ikut penomoran', function () {
        $n = naskahPenelitian($this);
        $pertama = $n->baris()->where('bagian', 'menimbang')->first();
        $this->alur->hapus($pertama, $this->peneliti, 'Tidak perlu');

        $baris = $n->baris()->get();
        $penanda = TemplateNaskah::penanda($n, $baris);

        // Tersisa satu pertimbangan, jadi tanpa huruf; bila yang dihapus ikut terhitung, hasilnya "b.".
        expect($pertama->fresh()->teksAkhir())->toBeNull()
            ->and($penanda[$baris->where('bagian', 'menimbang')->last()->id])->toBeNull();
    });

    it('meminta PD perbaiki wajib menulis arahan', function () {
        $n = naskahPenelitian($this);

        expect(fn () => $this->alur->mintaPerbaikan($n->baris()->first(), $this->peneliti, ''))->toThrow(ValidationException::class);
    });

    it('menyisipkan baris menjaga urutan dan isinya masuk kolom 4', function () {
        $n = naskahPenelitian($this);
        $acuan = $n->baris()->where('label', 'Pasal 1')->first();

        $baru = $this->alur->tambahSetelah($acuan, $this->peneliti, 'Pasal 1A');
        $this->alur->perbaiki($baru, $this->peneliti, 'Isi pasal baru');

        expect($baru->fresh()->urutan)->toBe($acuan->urutan + 1)
            ->and($baru->fresh()->rumusan_awal)->toBeNull()
            ->and($baru->fresh()->teksAkhir())->toBe('Isi pasal baru');
    });

    it('baris sisipan bisa diberi penanda Pasal dan tidak boleh disimpan kosong', function () {
        $n = naskahPenelitian($this);
        $pasal = $n->baris()->where('label', 'Pasal 1')->first();
        $baru = $this->alur->tambahSetelah($pasal, $this->peneliti);

        expect(fn () => $this->alur->perbaiki($baru, $this->peneliti, ''))->toThrow(ValidationException::class);

        $this->alur->perbaiki($baru, $this->peneliti, 'Isi pasal baru', null, ' Pasal 2 ');
        $this->alur->perbaiki($pasal, $this->peneliti, 'Rumusan baru', null, 'Pasal 99');

        expect($baru->fresh()->label)->toBe('Pasal 2')
            ->and($pasal->fresh()->label)->toBe('Pasal 1');
    });

    it('Peneliti bisa membuang baris sisipan, tetapi tidak baris usulan PD', function () {
        $n = naskahPenelitian($this);
        $usulan = $n->baris()->where('bagian', 'menimbang')->first();
        $baru = $this->alur->tambahSetelah($usulan, $this->peneliti);

        expect(fn () => $this->alur->buangSisipan($usulan, $this->peneliti))->toThrow(HttpException::class)
            ->and(fn () => $this->alur->buangSisipan($baru, $this->pd))->toThrow(HttpException::class);

        Livewire::actingAs($this->peneliti)
            ->test(BarisKerja::class, ['baris' => $baru, 'naskah' => $n])
            ->assertSee('Hapus Baris')
            ->assertDontSee('Hapus Rumusan')
            ->call('buangSisipan')
            ->assertDispatched('struktur-berubah');

        expect(B::find($baru->id))->toBeNull()
            ->and($n->riwayat()->where('aksi', 'buang_baris')->exists())->toBeTrue();
    });

    it('baris terakhir pada suatu bagian tidak bisa dibuang', function () {
        $n = naskahPenelitian($this);
        $satuSatunya = $n->baris()->where('bagian', 'batang_tubuh')->first();
        $satuSatunya->update(['rumusan_awal' => null]);

        expect(fn () => $this->alur->buangSisipan($satuSatunya, $this->peneliti))->toThrow(ValidationException::class)
            ->and($satuSatunya->fresh())->not->toBeNull();
    });

    it('Sisipkan Baris hanya ada pada bagian yang barisnya bisa lebih dari satu', function () {
        $n = naskahPenelitian($this);
        $tampil = fn (string $bagian) => Livewire::actingAs($this->peneliti)
            ->test(BarisKerja::class, ['baris' => $n->baris()->where('bagian', $bagian)->first(), 'naskah' => $n]);

        $tampil('judul')->assertDontSee('Sisipkan Baris');
        $tampil('menimbang')->assertSee('Sisipkan Baris');
    });

    it('mencatat riwayat dan menggabungkan simpan otomatis', function () {
        $n = naskahPenelitian($this);
        $b = $n->baris()->first();
        $sebelum = $n->riwayat()->count();

        $this->alur->perbaiki($b, $this->peneliti, 'Versi satu');
        $this->alur->perbaiki($b, $this->peneliti, 'Versi dua');

        expect($n->riwayat()->count())->toBe($sebelum + 1)
            ->and($n->riwayat()->first()->sesudah)->toContain('Versi dua');
    });

    it('penomoran diktum SK otomatis', function () {
        $n = naskahDraf($this->pd, 'Keputusan Bupati');
        $this->alur->isiBaris($n, [
            ['bagian' => 'diktum', 'label' => null, 'teks' => 'satu'],
            ['bagian' => 'diktum', 'label' => null, 'teks' => 'dua'],
        ]);

        $baris = $n->baris()->where('bagian', 'diktum')->get();

        expect(array_values(TemplateNaskah::penanda($n, $baris)))->toBe(['KESATU', 'KEDUA']);
    });
});

describe('pratinjau dan perbandingan', function () {
    it('pratinjau baru memuat naskah saat panelnya dibuka dan mengikuti perubahan', function () {
        $n = naskahPenelitian($this);
        $pasal = $n->baris()->where('label', 'Pasal 1')->first();

        $panel = Livewire::actingAs($this->peneliti)
            ->test(PratinjauNaskah::class, ['naskah' => $n])
            ->assertDontSee('Pembayaran paling lambat 7 hari.')
            ->call('aktifkan')
            ->assertSee('Pembayaran paling lambat 7 hari.')
            ->assertSeeHtml('data-baris="'.$pasal->id.'"');

        $this->alur->perbaiki($pasal, $this->peneliti, 'Pembayaran paling lambat 14 hari.');

        $panel->dispatch('baris-berubah')->assertSee('Pembayaran paling lambat 14 hari.');
    });

    it('pratinjau tidak bisa dibuka oleh pengguna yang tidak berhak', function () {
        $n = naskahPenelitian($this);
        $lain = tap(User::factory()->create())->assignRole('Perangkat Daerah');

        Livewire::actingAs($lain)->test(PratinjauNaskah::class, ['naskah' => $n])->assertForbidden();
    });

    it('perbandingan berbentuk naskah dengan tanda hapus dan tambah, dan tombolnya mengarah ke barisnya', function () {
        $n = naskahPenelitian($this);
        $pasal = $n->baris()->where('label', 'Pasal 1')->first();
        $this->alur->perbaiki($pasal, $this->peneliti, 'Pembayaran paling lambat 14 hari.');

        $layar = Livewire::actingAs($this->peneliti)
            ->test(LayarKerja::class, ['naskah' => $n])
            ->set('tab', 'naskah')
            ->assertDontSeeHtml('<del>7</del>')
            ->set('tampilNaskah', 'banding')
            ->assertSeeHtml('<del>7</del>')
            ->assertSeeHtml('<ins>14</ins>')
            ->assertSee(['1 baris berubah dari 4 baris', 'bahwa satu', 'Pasal 1'])
            ->assertSeeHtml('wire:click="bukaBaris('.$pasal->id.')"');

        $layar->call('bukaBaris', $pasal->id)
            ->assertSet('tab', 'kerja')
            ->assertSet('filter', 'semua')
            ->assertDispatched('fokus-baris', id: $pasal->id);
    });

    it('perbandingan menandai baris dihapus dan baris sisipan, dan tidak mengubah hasil cetak', function () {
        $n = naskahPenelitian($this);
        $menimbang = $n->baris()->where('bagian', 'menimbang')->get();
        $this->alur->hapus($menimbang->first(), $this->peneliti);
        $sisipan = $this->alur->tambahSetelah($menimbang->last(), $this->peneliti);
        $this->alur->perbaiki($sisipan, $this->peneliti, 'bahwa tiga');

        $baris = $n->baris()->get();
        $daftar = collect(BandingNaskah::susun($n, $baris))->firstWhere('label', 'Menimbang');
        $tampil = fn (array $b) => [(string) $b['nomor'], (string) $b['isi']];

        expect(array_map($tampil, $daftar['butir']))->toBe([
            ['<del>a.</del>', '<del>bahwa satu</del>'],
            ['a.', 'bahwa dua'],
            ['<ins>b.</ins>', '<ins>bahwa tiga</ins>'],
        ])->and($daftar['ids'])->toBe([$menimbang->first()->id, $menimbang->last()->id, $sisipan->id])
            ->and($daftar['diubahButir'])->toBe([true, false, true]);

        $cetak = collect(TataLetakNaskah::susun($n, $baris))->firstWhere('label', 'Menimbang');

        expect($cetak['butir'])->toBe([
            ['nomor' => 'a.', 'isi' => 'bahwa dua'],
            ['nomor' => 'b.', 'isi' => 'bahwa tiga'],
        ]);
    });

    it('perbandingan mempertahankan ayat dan butir, serta hanya menandai yang berubah', function () {
        $n = naskahPenelitian($this);
        $pasal = $n->baris()->where('label', 'Pasal 1')->first();
        $pasal->update(['rumusan_awal' => "(1) Ayat satu:\na. huruf a;\nb. huruf b.\n(2) Ayat dua yang panjang sekali isinya."]);
        $this->alur->perbaiki($pasal, $this->peneliti, "(1) Ayat satu:\na. huruf a;\nb. huruf baru;\nc. huruf c.\n(2) Ayat dua yang panjang sekali rumusannya.");

        $isi = collect(BandingNaskah::susun($n, $n->baris()->get()))->where('id', $pasal->id)->firstWhere('tipe', 'teks')['baris'];
        $tampil = array_map(fn (array $b) => [$b['tingkat'], (string) $b['nomor'], (string) $b['isi']], $isi);

        expect($tampil[0])->toBe([0, '(1)', 'Ayat satu:'])
            ->and($tampil[1])->toBe([1, 'a.', 'huruf a;'])
            ->and($tampil)->toContain([1, '<ins>c.</ins>', '<ins>huruf c.</ins>'])
            ->and($tampil[array_key_last($tampil)])->toBe([0, '(2)', 'Ayat dua yang panjang sekali <del>isinya.</del><ins>rumusannya.</ins>']);
    });

    it('perbandingan menandai baris tabel yang berubah dan menjaga keamanan teks', function () {
        $n = naskahPenelitian($this);
        $pasal = $n->baris()->where('label', 'Pasal 1')->first();
        $pasal->update(['rumusan_awal' => "| Nama | Nilai |\n| Ketua | Rp1 |"]);
        $this->alur->perbaiki($pasal, $this->peneliti, "| Nama | Nilai |\n| Ketua | Rp2 |\n| <b>Anggota</b> | Rp3 |");

        $tabel = collect(BandingNaskah::susun($n, $n->baris()->get()))->where('id', $pasal->id)->firstWhere('tipe', 'teks')['baris'][0]['tabel'];
        $isi = array_map(fn (array $sel) => array_map('strval', $sel), $tabel);

        expect($isi)->toBe([
            ['Nama', 'Nilai'],
            ['Ketua', '<del>Rp1</del><ins>Rp2</ins>'],
            ['<ins>&lt;b&gt;Anggota&lt;/b&gt;</ins>', '<ins>Rp3</ins>'],
        ]);
    });

    it('pratinjau bisa beralih ke perbandingan dan kertas memuat lambang Garuda', function () {
        $n = naskahPenelitian($this);
        $this->alur->perbaiki($n->baris()->where('label', 'Pasal 1')->first(), $this->peneliti, 'Pembayaran paling lambat 14 hari.');

        Livewire::actingAs($this->peneliti)
            ->test(PratinjauNaskah::class, ['naskah' => $n])
            ->call('aktifkan')
            ->assertSeeHtml('garuda.png')
            ->assertDontSeeHtml('<del>')
            ->dispatch('pratinjau-banding', aktif: true)
            ->assertSeeHtml('<del>7</del>')
            ->assertSeeHtml('<ins>14</ins>')
            ->assertDontSeeHtml('wire:click="bukaBaris')
            ->dispatch('pratinjau-banding', aktif: false)
            ->assertDontSeeHtml('<del>');
    });

    it('draf tidak punya perbandingan', function () {
        $n = naskahDraf($this->pd);

        Livewire::actingAs($this->pd)
            ->test(LayarKerja::class, ['naskah' => $n])
            ->set('tab', 'naskah')
            ->assertDontSee('Perbandingan')
            ->set('tampilNaskah', 'banding')
            ->assertDontSeeHtml('wire:click="bukaBaris');
    });

    it('menolak membuka baris milik naskah lain dari perbandingan', function () {
        $n = naskahPenelitian($this);
        $lain = naskahPenelitian($this)->baris()->first();

        Livewire::actingAs($this->peneliti)
            ->test(LayarKerja::class, ['naskah' => $n])
            ->call('bukaBaris', $lain->id)
            ->assertNotFound();
    });

    it('rumusan bertabel dipisah dari teks biasa untuk tampilan layar', function () {
        // Baris pemisah sesudah baris pertama menandai baris judul; rata kolomnya kiri.
        expect(TataLetakNaskah::potongan("Besaran:\n| Jabatan | Nilai |\n| --- | --- |\n| Ketua | Rp1 |\nSelesai."))->toBe([
            ['teks' => 'Besaran:'],
            ['tabel' => [['Jabatan', 'Nilai'], ['Ketua', 'Rp1']], 'rata' => ['left', 'left'], 'judul' => true],
            ['teks' => 'Selesai.'],
        ]);
    });
});

describe('hak akses tampilan', function () {
    it('PD lain dan peneliti lain tidak bisa membuka naskah', function () {
        $n = naskahPenelitian($this);

        $this->actingAs(tap(User::factory()->create())->assignRole('Perangkat Daerah'))
            ->get(route('v2.naskah.kerja', $n))->assertForbidden();
        $this->actingAs(tap(User::factory()->create())->assignRole('Peneliti'))
            ->get(route('v2.naskah.kerja', $n))->assertForbidden();

        $this->actingAs($this->pd)->get(route('v2.naskah.kerja', $n))->assertOk();
        $this->actingAs($this->peneliti)->get(route('v2.naskah.kerja', $n))->assertOk();
        $this->actingAs($this->admin)->get(route('v2.naskah.kerja', $n))->assertOk();
    });

    it('berkas hanya bisa diunduh yang berhak dan milik naskah itu', function () {
        $n = naskahDraf($this->pd);
        $lain = naskahDraf($this->pd);
        Storage::disk('local')->put('x.pdf', 'isi');
        $berkas = $n->berkas()->first();

        $this->actingAs(tap(User::factory()->create())->assignRole('Perangkat Daerah'))
            ->get(route('v2.naskah.berkas', [$n, $berkas]))->assertForbidden();
        $this->actingAs($this->pd)->get(route('v2.naskah.berkas', [$lain, $berkas]))->assertNotFound();
        $this->actingAs($this->pd)->get(route('v2.naskah.berkas', [$n, $berkas]))->assertOk();
    });

    it('ajukan rancangan baru hanya untuk PD, lewat modal di Rancanganku', function () {
        $this->actingAs($this->peneliti);
        Livewire::test(BuatNaskah::class)->assertForbidden();

        $this->actingAs($this->pd)->get(route('v2.daftar', 'rancanganku'))
            ->assertOk()->assertSeeLivewire(BuatNaskah::class)->assertSee('Ajukan Rancangan Baru');
        Livewire::test(BuatNaskah::class)->assertOk();

        $this->actingAs($this->admin)->get(route('v2.daftar', 'rancangan'))->assertOk()->assertDontSeeLivewire(BuatNaskah::class);
    });

    it('modal ajukan bisa dikosongkan dan filenya bisa dibuang', function () {
        $this->actingAs($this->pd);

        Livewire::test(BuatNaskah::class)
            ->set('tentang', 'Dana Desa')
            ->set('notaDinas', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
            ->call('removeFile', 'notaDinas')->assertSet('notaDinas', null)
            ->call('removeFile', 'tentang')->assertSet('tentang', 'Dana Desa')
            ->call('resetForm')->assertSet('tentang', '');
    });

    it('judul ditempel dari Word tersimpan satu baris tanpa spasi berlebih', function () {
        $this->actingAs($this->pd);

        Livewire::test(BuatNaskah::class)
            ->set('jenis', 'Peraturan Bupati')
            ->set('tentang', "  Perubahan Atas Peraturan Bupati\r\n  Tentang   Dana Desa ")
            ->set('nomorNota', '180/9/2026')->set('tanggalNota', '2026-11-04')
            ->set('notaDinas', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
            ->call('simpan')->assertHasNoErrors();

        expect(Naskah::latest('id')->first()->tentang)->toBe('Perubahan Atas Peraturan Bupati Tentang Dana Desa');
    });

    it('judul panjang diterima sampai batas kolom arsip dan yang melewatinya ditolak dengan pesan', function () {
        $this->actingAs($this->pd);

        $form = fn (string $tentang, string $nomor) => Livewire::test(BuatNaskah::class)
            ->set('jenis', 'Peraturan Bupati')
            ->set('tentang', $tentang)
            ->set('nomorNota', $nomor)->set('tanggalNota', '2026-11-04')
            ->set('notaDinas', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
            ->call('simpan');

        $form(str_repeat('a', 1001), '180/10/2026')->assertHasErrors(['tentang' => 'max']);
        expect(Naskah::count())->toBe(0);

        $form(str_repeat('a', 1000), '180/11/2026')->assertHasNoErrors();
        expect(mb_strlen(Naskah::first()->tentang))->toBe(1000);
    });

    it('modal ajukan memakai kolom judul lebar', function () {
        $this->actingAs($this->pd);

        Livewire::test(BuatNaskah::class)
            ->assertSeeHtml('<textarea rows="3"')
            ->assertSeeHtml('sm:max-w-4xl');
    });

    it('jenis rancangan wajib dipilih', function () {
        $this->actingAs($this->pd);

        Livewire::test(BuatNaskah::class)
            ->set('tentang', 'Dana Desa')->set('nomorNota', '180/2/2026')->set('tanggalNota', '2026-11-04')
            ->set('notaDinas', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
            ->call('simpan')->assertHasErrors('jenis');

        expect(Naskah::count())->toBe(0);
    });

    it('beranda hanya menampilkan naskah yang boleh dilihat', function () {
        $n = naskahDraf($this->pd);
        $lain = tap(User::factory()->create())->assignRole('Perangkat Daerah');

        // Draf belum bernomor, jadi dikenali dari judulnya.
        $this->actingAs($lain)->get(route('v2.beranda'))->assertOk()->assertDontSee($n->tentang);
        $this->actingAs($this->pd)->get(route('v2.beranda'))->assertOk()->assertSee($n->tentang);
    });
});

describe('komponen Livewire', function () {
    it('PD membuat naskah dari Word dan barisnya terisi', function () {
        $this->actingAs($this->pd);

        Livewire::test(BuatNaskah::class)
            ->set('jenis', 'Peraturan Bupati')
            ->set('tentang', 'Dana Desa')
            ->set('nomorNota', '180/1/2026')
            ->set('tanggalNota', '2026-11-04')
            ->set('notaDinas', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
            ->set('word', UploadedFile::fake()->createWithContent('naskah.docx', file_get_contents(wordContoh())))
            ->call('simpan')
            ->assertHasNoErrors();

        $n = Naskah::firstOrFail();
        expect($n->tahap)->toBe(T::Draf)
            ->and($n->baris()->where('label', 'Pasal 2')->exists())->toBeTrue()
            ->and($n->berkas()->count())->toBe(2);
    });

    it('Peneliti menekan Tetap lewat layar', function () {
        $n = naskahPenelitian($this);
        $b = $n->baris()->first();

        $this->actingAs($this->peneliti);
        Livewire::test(BarisKerja::class, ['baris' => $b, 'naskah' => $n])
            ->call('tetap', null)
            ->assertDispatched('baris-berubah');

        expect($b->fresh()->status)->toBe(B::TETAP);
    });

    it('layar kerja merender semua tab tanpa galat', function () {
        $n = naskahPenelitian($this);
        $this->actingAs($this->peneliti);

        $layar = Livewire::test(LayarKerja::class, ['naskah' => $n]);
        foreach (['kerja', 'naskah', 'berkas', 'riwayat'] as $tab) {
            $layar->set('tab', $tab)->assertOk();
        }
    });

    it('naskah panjang dibuka per bagian dan lompat memunculkan baris yang dituju', function () {
        $n = naskahPenelitian($this);
        $this->alur->isiBaris($n, array_fill(0, 70, ['bagian' => 'batang_tubuh', 'label' => 'Pasal X', 'teks' => 'isi']));
        $n->baris()->orderBy('id')->get()->each(fn (B $b, int $i) => $b->update(['urutan' => $i + 1]));
        $terakhir = $n->baris()->get()->last();
        $this->actingAs($this->peneliti);

        $layar = Livewire::test(LayarKerja::class, ['naskah' => $n])
            ->assertViewHas('daftarBaris', fn ($d) => $d->count() <= BlokNaskah::MAKS_BARIS)
            ->assertViewHas('blokSebelumnya', null);

        $layar->set('filter', 'perlu')->assertSet('blok', '')
            ->call('lompatKe', $terakhir->id)
            ->assertSet('filter', 'semua')
            ->assertViewHas('daftarBaris', fn ($d) => $d->contains('id', $terakhir->id));
    });

    it('berpindah bagian lewat tombol berikutnya dan sebelumnya', function () {
        $n = naskahPenelitian($this);
        $this->actingAs($this->peneliti);

        $layar = Livewire::test(LayarKerja::class, ['naskah' => $n])->set('filter', 'semua')->call('pilihBlok', 'judul');
        $layar->assertViewHas('blokSebelumnya', null)
            ->assertViewHas('blokBerikutnya', fn ($b) => $b['kunci'] === 'menimbang')
            ->assertDispatched('blok-berubah');

        $layar->call('pilihBlok', 'menimbang')->assertViewHas('blokSebelumnya', fn ($b) => $b['kunci'] === 'judul');
    });

    it('bagian yang sedang dikerjakan tidak melompat saat barisnya selesai', function () {
        $n = naskahPenelitian($this);
        $this->actingAs($this->peneliti);

        $layar = Livewire::test(LayarKerja::class, ['naskah' => $n])->call('pilihBlok', 'menimbang');
        $n->baris()->where('bagian', 'menimbang')->get()->each(fn (B $b) => $this->alur->tetapkan($b, $this->peneliti, null));

        $layar->dispatch('baris-selesai', id: 1)->assertSet('blok', 'menimbang');
    });

    it('layar menolak aksi yang bukan haknya', function () {
        $n = naskahPenelitian($this);
        $this->actingAs($this->pd);

        Livewire::test(LayarKerja::class, ['naskah' => $n])
            ->call('pilihAksi', 'teruskan_ke_verifikator')
            ->assertForbidden();
    });

    it('mode baca tidak bisa memanggil aksi baris', function () {
        $n = naskahPenelitian($this);
        $b = $n->baris()->first();
        $this->actingAs($this->pd);

        Livewire::test(BarisKerja::class, ['baris' => $b, 'naskah' => $n])
            ->call('tetap', null)
            ->assertForbidden();
    });

    it('rumusan dengan tag HTML ditampilkan sebagai teks', function () {
        $n = naskahPenelitian($this);
        $b = $n->baris()->first();
        $b->update(['rumusan_awal' => '<script>alert(1)</script>', 'rumusan_penyempurnaan' => '<img src=x onerror=alert(1)>', 'status' => B::DIUBAH]);
        $this->actingAs($this->admin);

        Livewire::test(BarisKerja::class, ['baris' => $b->fresh(), 'naskah' => $n])
            ->assertDontSeeHtml('<script>alert(1)</script>')
            ->assertDontSeeHtml('<img src=x');
    });
});

describe('pembaca Word', function () {
    it('memecah Peraturan Bupati menjadi baris', function () {
        $hasil = (new PembacaNaskahWord())->baca(wordContoh(), 'Peraturan Bupati');
        $baris = collect($hasil['baris']);

        expect($baris->firstWhere('bagian', 'judul')['teks'])->toContain('TENTANG')->not->toContain('DENGAN RAHMAT')
            ->and($baris->where('bagian', 'menimbang')->count())->toBe(2)
            ->and($baris->where('bagian', 'menimbang')->first()['teks'])->toStartWith('bahwa')
            ->and($baris->where('bagian', 'mengingat')->count())->toBe(2)
            ->and($baris->firstWhere('label', 'BAB I')['teks'])->toBe('KETENTUAN UMUM')
            ->and($baris->firstWhere('label', 'Pasal 2')['teks'])->toContain("(1) Ayat satu sebagaimana dimaksud dalam Pasal 1.\n(2) Ayat dua.")
            ->and($baris->firstWhere('bagian', 'penutup')['teks'])->toContain('Ditetapkan di Barabai')
            ->and($baris->last()['bagian'])->toBe('lampiran')
            ->and($hasil['ringkasan']['pasal'])->toBe(2);
    });

    it('memecah Keputusan Bupati menjadi diktum', function () {
        $hasil = (new PembacaNaskahWord())->baca(wordDari([
            'KEPUTUSAN BUPATI HULU SUNGAI TENGAH', 'NOMOR 400/ /911/TAHUN 2025', 'TENTANG', 'PENETAPAN HIBAH',
            'BUPATI HULU SUNGAI TENGAH,', 'Menimbang : a. bahwa satu;', 'b. bahwa dua;', 'Mengingat : 1. Undang-Undang satu;',
            'M E M U T U S K A N:', 'Menetapkan:', 'KESATU : Menetapkan penerima.', 'KE DUA : Besaran hibah.', 'Lanjutan kedua.',
            'Ditetapkan di Barabai', 'LAMPIRAN', 'Daftar penerima',
        ]), 'Keputusan Bupati');
        $baris = collect($hasil['baris']);

        expect($baris->where('bagian', 'diktum')->pluck('teks')->all())->toBe(['Menetapkan penerima.', "Besaran hibah.\nLanjutan kedua."])
            ->and($baris->where('bagian', 'menimbang')->pluck('teks')->all())->toBe(['bahwa satu;', 'bahwa dua;'])
            ->and($baris->pluck('teks')->implode(' '))->not->toContain('Daftar penerima')
            ->and($hasil['peringatan'])->not->toBeEmpty();
    });

    it('menolak file yang bukan docx', function () {
        $palsu = tempnam(sys_get_temp_dir(), 'x');
        file_put_contents($palsu, 'bukan word');

        expect(fn () => (new PembacaNaskahWord())->baca($palsu, 'Peraturan Bupati'))->toThrow(RuntimeException::class);
    });
});

it('menandai perubahan kata dan meloloskan HTML', function () {
    $html = DiffKata::html('paling lambat 7 hari <b>', 'paling lambat 14 hari kerja <b>');

    expect($html)->toContain('<del>7</del>')->toContain('<ins>14</ins>')->toContain('&lt;b&gt;')->not->toContain('<b>');
});

function wordContoh(): string
{
    return wordDari([
        'BUPATI HULU SUNGAI TENGAH', 'PERATURAN BUPATI HULU SUNGAI TENGAH', 'NOMOR ... TAHUN ...', 'TENTANG', 'DANA DESA',
        'DENGAN RAHMAT TUHAN YANG MAHA ESA', 'BUPATI HULU SUNGAI TENGAH,',
        'Menimbang : a. bahwa satu;', 'b. bahwa dua;', 'Mengingat : 1. Undang-Undang satu;', '2. Undang-Undang dua;',
        'MEMUTUSKAN:', 'Menetapkan: PERATURAN BUPATI TENTANG DANA DESA.',
        'BAB I', 'KETENTUAN UMUM', 'Pasal 1', 'Dalam Peraturan Bupati ini yang dimaksud dengan:',
        'Pasal 2', '(1) Ayat satu sebagaimana dimaksud dalam Pasal 1.', '(2) Ayat dua.',
        'Ditetapkan di Barabai', 'pada tanggal ...',
    ]);
}

function wordDari(array $paragraf): string
{
    $word = new PhpWord();
    $bagian = $word->addSection();
    foreach ($paragraf as $teks) {
        $bagian->addText($teks);
    }

    $path = tempnam(sys_get_temp_dir(), 'naskah') . '.docx';
    IOFactory::createWriter($word, 'Word2007')->save($path);

    return $path;
}

describe('bagian wajib dan lembar kerja', function () {
    it('Judul, Penutup, dan Lampiran tidak bisa dihapus Peneliti', function () {
        $n = naskahPenelitian($this);
        $judul = $n->baris()->where('bagian', 'judul')->first();
        $penutup = B::create(['naskah_id' => $n->id, 'urutan' => 90, 'bagian' => 'penutup', 'rumusan_awal' => 'Ditetapkan di Barabai']);
        $lampiran = B::create(['naskah_id' => $n->id, 'urutan' => 91, 'bagian' => 'lampiran', 'rumusan_awal' => 'Lampiran']);

        foreach ([$judul, $penutup, $lampiran] as $baris) {
            expect(fn () => $this->alur->hapus($baris, $this->peneliti))->toThrow(HttpException::class);

            Livewire::actingAs($this->peneliti)
                ->test(BarisKerja::class, ['baris' => $baris, 'naskah' => $n])
                ->assertDontSee('Hapus Rumusan')
                ->assertSee('Tetap');
        }

        Livewire::actingAs($this->peneliti)
            ->test(BarisKerja::class, ['baris' => $n->baris()->where('bagian', 'menimbang')->first(), 'naskah' => $n])
            ->assertSee('Hapus Rumusan');
    });

    it('lampiran bisa ditambah menjadi Lampiran I dan II, tetapi lampiran terakhir tidak bisa dihapus', function () {
        $n = naskahDraf($this->pd);
        $pertama = B::create(['naskah_id' => $n->id, 'urutan' => 90, 'bagian' => 'lampiran', 'rumusan_awal' => 'DAFTAR A']);

        Livewire::actingAs($this->pd)
            ->test(BarisKerja::class, ['baris' => $pertama, 'naskah' => $n])
            ->assertSee('Tambah Lampiran')
            ->assertDontSeeHtml('aria-label="Hapus baris ini"');
        expect(fn () => $this->alur->buangSaatMenyusun($pertama, $this->pd))->toThrow(HttpException::class);

        $kedua = $this->alur->tambahSetelah($pertama, $this->pd);
        $kedua->update(['rumusan_awal' => 'DAFTAR B']);
        $kepala = collect(TataLetakNaskah::susun($n, $n->baris()->get()))->where('tipe', 'lampiran')->pluck('kepala.0')->all();

        expect($kepala)->toBe(['LAMPIRAN I', 'LAMPIRAN II']);
        Livewire::actingAs($this->pd)
            ->test(BarisKerja::class, ['baris' => $kedua, 'naskah' => $n])
            ->assertSeeHtml('aria-label="Hapus baris ini"');

        $this->alur->buangSaatMenyusun($kedua, $this->pd);
        expect(B::find($kedua->id))->toBeNull()
            ->and(collect(TataLetakNaskah::susun($n, $n->baris()->get()))->where('tipe', 'lampiran')->pluck('kepala.0')->all())->toBe(['LAMPIRAN']);
    });

    it('lembar kerja memakai header jalur dengan badge jenis dokumen', function () {
        $n = naskahPenelitian($this);

        $this->actingAs($this->peneliti)->get(route('v2.naskah.kerja', $n))->assertOk()
            ->assertDontSeeHtml('sticky top-0 z-30')
            ->assertSeeInOrder([$n->nomor, 'Dana Desa', 'Peraturan Bupati', 'Penelitian'])
            ->assertSee(['Ganti tema', 'Notifikasi', 'Pindah Aplikasi', 'Keluar', 'Buka daftar bagian'], false);

        $this->actingAs($this->peneliti)->get(route('v2.beranda'))->assertOk()->assertSeeHtml('sticky top-0 z-30');
    });

    it('lonceng lembar kerja tetap lonceng v2, juga saat halaman dirender ulang', function () {
        $n = naskahPenelitian($this);

        Livewire::actingAs($this->peneliti)
            ->test(LayarKerja::class, ['naskah' => $n])
            ->assertSeeLivewire(Lonceng::class)
            ->assertDontSeeLivewire(NotificationDropdown::class)
            ->call('pilihBlok', 'menimbang')
            ->assertDontSeeLivewire(NotificationDropdown::class);
    });

    it('badge Keputusan Bupati berbeda warna dari Peraturan Bupati', function () {
        $sk = naskahDraf($this->pd, 'Keputusan Bupati');

        $this->actingAs($this->pd)->get(route('v2.naskah.kerja', $sk))->assertOk()
            ->assertSee('Keputusan Bupati')
            ->assertSeeHtml('bg-accent-500');
    });
});

describe('minta PD perbaiki', function () {
    it('tombolnya membuka panel berisi penjelasan, dan bagian memuat panduan tiga tombol', function () {
        $n = naskahPenelitian($this);
        $baris = $n->baris()->where('bagian', 'menimbang')->first();

        Livewire::actingAs($this->peneliti)
            ->test(BarisKerja::class, ['baris' => $baris, 'naskah' => $n])
            ->assertSee(['Apa yang perlu diperbaiki PD?', 'hanya PD yang tahu isinya', 'Belum langsung terkirim', 'Tandai untuk PD']);

        Livewire::actingAs($this->peneliti)
            ->test(LayarKerja::class, ['naskah' => $n, 'blok' => 'menimbang'])
            ->assertSee('Minta PD Perbaiki bila hanya PD yang tahu isinya');
    });

    it('arahan kosong ditolak dan melaporkan gagal, arahan terisi menandai baris', function () {
        $n = naskahPenelitian($this);
        $baris = $n->baris()->where('bagian', 'menimbang')->first();

        $komponen = Livewire::actingAs($this->peneliti)->test(BarisKerja::class, ['baris' => $baris, 'naskah' => $n]);

        $komponen->call('mintaPd', null, '')->assertReturned(false)->assertHasErrors('keterangan');
        expect($baris->fresh()->status)->toBe(B::BELUM);

        $komponen->call('mintaPd', null, 'Lengkapi besaran')->assertReturned(true)->assertHasNoErrors()
            ->assertSee(['Menunggu perbaikan PD', 'Ubah Arahan ke PD']);

        expect($baris->fresh()->status)->toBe(B::PERLU_PD)
            ->and($baris->fresh()->keterangan)->toBe('Lengkapi besaran');
    });

    it('panel tidak ada untuk Verifikator yang mengoreksi', function () {
        $n = naskahPenelitian($this);
        $baris = $n->baris()->where('bagian', 'menimbang')->first();
        $n->update(['tahap' => T::ValidasiFasilitasi]);

        Livewire::actingAs($this->verifikator)
            ->test(BarisKerja::class, ['baris' => $baris, 'naskah' => $n->fresh()])
            ->assertSee('Ubah')
            ->assertDontSee('Tandai untuk PD')
            ->assertDontSee('Minta PD Perbaiki');
    });
});
