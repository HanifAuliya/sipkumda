<?php

use App\Enums\TahapNaskah as T;
use App\Livewire\V2\BarisKerja;
use App\Livewire\V2\BuatNaskah;
use App\Livewire\V2\LayarKerja;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\PembacaNaskahWord;
use App\Support\DiffKata;
use App\Support\TemplateNaskah;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

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
        $n = $this->alur->jalankan($n, $this->admin, 'setujui_fasilitasi');

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
        $n = $this->alur->jalankan($n, $this->admin, 'setujui_fasilitasi');
        $n = $this->alur->jalankan($n, $this->verifikator, 'finalkan');

        expect(fn () => $this->alur->jalankan($n, $this->verifikator, 'buka_kunci'))->toThrow(ValidationException::class);
        expect(fn () => $this->alur->jalankan($n, $this->admin, 'buka_kunci', 'x'))->toThrow(Symfony\Component\HttpKernel\Exception\HttpException::class);

        $n = $this->alur->jalankan($n, $this->verifikator, 'buka_kunci', 'Koreksi dari Sekda');
        expect($n->tahap)->toBe(T::ValidasiFasilitasi)->and($n->terkunci_at)->toBeNull();
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

        expect($pertama->fresh()->teksAkhir())->toBeNull()
            ->and($penanda[$baris->where('bagian', 'menimbang')->last()->id])->toBe('a.');
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
        $n = naskahDraf($this->pd, 'Surat Keputusan');
        $this->alur->isiBaris($n, [
            ['bagian' => 'diktum', 'label' => null, 'teks' => 'satu'],
            ['bagian' => 'diktum', 'label' => null, 'teks' => 'dua'],
        ]);

        $baris = $n->baris()->where('bagian', 'diktum')->get();

        expect(array_values(TemplateNaskah::penanda($n, $baris)))->toBe(['KESATU', 'KEDUA']);
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

    it('halaman buat naskah hanya untuk PD', function () {
        $this->actingAs($this->peneliti)->get(route('v2.naskah.buat'))->assertForbidden();
        $this->actingAs($this->pd)->get(route('v2.naskah.buat'))->assertOk();
    });

    it('beranda hanya menampilkan naskah yang boleh dilihat', function () {
        $n = naskahDraf($this->pd);
        $lain = tap(User::factory()->create())->assignRole('Perangkat Daerah');

        $this->actingAs($lain)->get(route('v2.beranda'))->assertOk()->assertDontSee($n->nomor);
        $this->actingAs($this->pd)->get(route('v2.beranda'))->assertOk()->assertSee($n->nomor);
    });
});

describe('komponen Livewire', function () {
    it('PD membuat naskah dari Word dan barisnya terisi', function () {
        $this->actingAs($this->pd);

        Livewire::test(BuatNaskah::class)
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

    it('baris dimuat bertahap dan lompat memunculkan baris yang dituju', function () {
        $n = naskahPenelitian($this);
        $this->alur->isiBaris($n, array_fill(0, 70, ['bagian' => 'batang_tubuh', 'label' => 'Pasal X', 'teks' => 'isi']));
        $n->baris()->orderBy('id')->get()->each(fn (B $b, int $i) => $b->update(['urutan' => $i + 1]));
        $terakhir = $n->baris()->get()->last();
        $this->actingAs($this->peneliti);

        $layar = Livewire::test(LayarKerja::class, ['naskah' => $n])
            ->assertViewHas('sisaBaris', 44)
            ->assertViewHas('daftarBaris', fn ($d) => $d->count() === 30);

        $layar->call('muatLagi')->assertViewHas('daftarBaris', fn ($d) => $d->count() === 60);

        $layar->set('filter', 'perlu')->assertSet('batas', 30)
            ->call('lompatKe', $terakhir->id)
            ->assertSet('filter', 'semua')
            ->assertViewHas('daftarBaris', fn ($d) => $d->contains('id', $terakhir->id));
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

    it('memecah Surat Keputusan menjadi diktum', function () {
        $hasil = (new PembacaNaskahWord())->baca(wordDari([
            'KEPUTUSAN BUPATI HULU SUNGAI TENGAH', 'NOMOR 400/ /911/TAHUN 2025', 'TENTANG', 'PENETAPAN HIBAH',
            'BUPATI HULU SUNGAI TENGAH,', 'Menimbang : a. bahwa satu;', 'b. bahwa dua;', 'Mengingat : 1. Undang-Undang satu;',
            'M E M U T U S K A N:', 'Menetapkan:', 'KESATU : Menetapkan penerima.', 'KE DUA : Besaran hibah.', 'Lanjutan kedua.',
            'Ditetapkan di Barabai', 'LAMPIRAN', 'Daftar penerima',
        ]), 'Surat Keputusan');
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
