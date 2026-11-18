<?php

use App\Livewire\V2\BarisKerja;
use App\Livewire\V2\BuatNaskah;
use App\Livewire\V2\LayarKerja;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Services\CetakNaskah;
use App\Support\PemeriksaIsian;
use App\Support\TemplateNaskah;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;

/*
 * Mutu isi naskah: isian "…" dari template, judul yang nyambung dengan judul pengajuan, dan
 * perbaikan PD yang harus diterima Peneliti.
 */

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    $this->alur = app(AlurNaskah::class);
    $this->pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Uji'])->id]))->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create())->assignRole('Peneliti');
});

/** Draf dari kerangka bawaan; $isi mengganti teks per bagian. */
function drafKerangka(object $uji, array $isi = []): Naskah
{
    $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Air Bersih Desa', 'pemrakarsa_id' => $uji->pd->id, 'nomor_nota' => 'ND-' . uniqid()]);
    $baris = collect(TemplateNaskah::kerangka('Peraturan Bupati', 'Air Bersih Desa'))
        ->map(fn ($b) => isset($isi[$b['label'] ?? $b['bagian']]) ? [...$b, 'teks' => $isi[$b['label'] ?? $b['bagian']]] : $b)
        ->all();
    $uji->alur->isiBaris($n, $baris);
    $n->berkas()->create(['jenis' => 'nota_dinas_pd', 'path' => 'x.pdf', 'nama_asli' => 'x.pdf', 'diunggah_oleh' => $uji->pd->id]);

    return $n;
}

function drafLengkap(object $uji): Naskah
{
    return drafKerangka($uji, [
        'menimbang' => 'bahwa air bersih perlu dikelola;',
        'mengingat' => 'Undang-Undang Nomor 6 Tahun 2014 tentang Desa;',
        'Pasal 1' => 'Desa adalah desa di Kabupaten Hulu Sungai Tengah.',
    ]);
}

it('mengenali isian "…" dari template, kecuali nomor judul, penutup, dan lampiran', function () {
    $n = drafKerangka($this);
    $isian = PemeriksaIsian::barisBerisian($n->baris()->get());

    expect($isian->all())->toBe(['Konsiderans Menimbang'])
        ->and(PemeriksaIsian::adaIsian($n->baris()->where('bagian', 'judul')->first()))->toBeFalse()
        ->and(PemeriksaIsian::adaIsian($n->baris()->where('bagian', 'penutup')->first()))->toBeFalse();
});

it('rancangan yang masih berisi isian template tidak bisa diajukan', function () {
    $n = drafKerangka($this, ['mengingat' => 'Undang-Undang ...;', 'Pasal 1' => '(1) ...']);

    expect(fn () => $this->alur->jalankan($n, $this->pd, 'ajukan'))
        ->toThrow(ValidationException::class, 'Masih ada isian “…” dari template di Konsiderans Menimbang, Dasar Hukum Mengingat, Pasal 1');

    expect($this->alur->jalankan(drafLengkap($this), $this->pd, 'ajukan')->tahap->value)->toBe('persetujuan_berkas');
});

it('merinci baris yang menghalangi pengajuan: kosong, berisi spasi saja, atau masih bertitik-titik', function () {
    $n = drafLengkap($this);
    $mengingat = $n->baris()->where('bagian', 'mengingat')->first();
    $n->baris()->create(['bagian' => 'mengingat', 'urutan' => $mengingat->urutan, 'rumusan_awal' => "  \n ", 'status' => B::BELUM]);
    $mengingat->update(['rumusan_awal' => 'Undang-Undang Nomor 6 Tahun 2014 tentang Desa (Lembaran Negara Republik Indonesia Tahun 2014 Nomor ...);']);

    $kurang = collect(PemeriksaIsian::kurangLengkap($n, $n->baris()->get()));

    expect($kurang)->toHaveCount(2)
        ->and($kurang->pluck('pesan')->implode(' | '))->toContain('belum diisi.', 'masih berisi titik-titik isian', 'Nomor ...)')
        ->and($this->alur->aksiTersedia($n, $this->pd)['ajukan']['hambatan'])->toBe('1 baris masih kosong.');

    // Daftarnya tampil di layar kerja, terbuka, dan setiap butir membuka barisnya.
    Livewire::actingAs($this->pd)->test(LayarKerja::class, ['naskah' => $n])
        ->assertSee(['Belum bisa diajukan: 1 galat, 1 baris kosong', 'masih berisi titik-titik isian'])
        ->assertSeeHtml('wire:click="bukaBaris(' . $mengingat->id . ')"')
        // Pesan umumnya cukup di tooltip tombol Ajukan, tidak menjadi kotak peringatan kedua.
        ->assertSeeHtml('title="1 baris masih kosong."')
        ->assertDontSeeHtml('<div class="min-w-0 flex-1">' . "\n" . '        1 baris masih kosong.');

    // Kartu barisnya juga menyebut alasannya.
    Livewire::actingAs($this->pd)->test(BarisKerja::class, ['baris' => $mengingat->fresh(), 'naskah' => $n])
        ->assertSee('Masih ada titik-titik isian');
});

it('jendela Ajukan merangkum galat, baris kosong, dan peringatan tanpa kolom catatan', function () {
    $n = drafLengkap($this);
    $pasal1 = $n->baris()->where('label', 'Pasal 1')->first();
    // Isian "..." adalah galat; rujukan ke pasal yang tidak ada dan judul yang berbeda hanya peringatan.
    $pasal1->update(['rumusan_awal' => "Ketentuan lebih lanjut diatur sesuai Pasal 9 Peraturan Bupati ini.\nBesarnya Rp... per orang."]);
    $n->baris()->where('bagian', 'judul')->first()->update(['rumusan_awal' => "PERATURAN BUPATI HULU SUNGAI TENGAH\nNOMOR ... TAHUN ...\nTENTANG\nJUDUL LAIN"]);

    // Tombol Ajukan tetap bisa dibuka untuk melihat rinciannya.
    expect($this->alur->aksiTersedia($n, $this->pd)['ajukan']['hambatan'])->toContain('Masih ada isian');

    Livewire::actingAs($this->pd)->test(LayarKerja::class, ['naskah' => $n])
        ->call('pilihAksi', 'ajukan')
        ->assertSee(['Galat', 'Baris kosong', 'Peringatan', 'harus diperbaiki', 'boleh tetap diajukan'])
        ->assertSee('Pasal 1 merujuk ke Pasal 9, yang tidak ada di naskah ini.')
        ->assertSee('Judul di naskah berbeda')
        ->assertDontSee('Catatan (opsional)')
        ->call('jalankanAksi')
        ->assertHasErrors('aksi');

    // Isian diganti: rujukan pasal dan judul tinggal peringatan, jadi tetap bisa diajukan.
    $pasal1->update(['rumusan_awal' => 'Ketentuan lebih lanjut diatur sesuai Pasal 9 Peraturan Bupati ini.']);

    Livewire::actingAs($this->pd)->test(LayarKerja::class, ['naskah' => $n->fresh()])
        ->call('pilihAksi', 'ajukan')
        ->assertSee('Tidak ada galat. Peringatan di bawah boleh diperiksa dulu')
        ->call('jalankanAksi')
        ->assertHasNoErrors();

    expect($n->fresh()->tahap->value)->toBe('persetujuan_berkas');
});

it('memperingatkan judul dan Menetapkan yang berbeda dengan judul pengajuan', function () {
    $n = drafLengkap($this);
    expect(PemeriksaIsian::periksa($n, $n->baris()->get()))->toBe([]);

    $n->baris()->where('bagian', 'judul')->first()->update(['rumusan_awal' => "PERATURAN BUPATI HULU SUNGAI TENGAH\nNOMOR ... TAHUN ...\nTENTANG\nJUDUL LAIN"]);
    $n->baris()->where('bagian', 'diktum')->first()->update(['rumusan_awal' => 'PERATURAN BUPATI TENTANG AIR BERSIH DESA.']);

    $pesan = collect(PemeriksaIsian::periksa($n, $n->baris()->get()))->pluck('pesan');
    expect($pesan->first())->toContain('Judul di naskah berbeda')
        ->and($pesan->last())->toContain('Diktum tidak memuat “Menetapkan :”');
});

it('judul dan Menetapkan dari Word mengikuti judul di formulir', function () {
    $word = app(CetakNaskah::class)->wordTemplate('Peraturan Bupati');
    $this->actingAs($this->pd);

    Livewire::test(BuatNaskah::class)
        ->set('jenis', 'Peraturan Bupati')
        ->set('tentang', 'Pengelolaan Air Bersih di Desa')
        ->set('tanggalNota', '2026-11-14')
        ->set('nomorNota', '400/1/UJI/2026')
        ->set('notaDinas', UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'))
        ->set('word', UploadedFile::fake()->createWithContent('template.docx', file_get_contents($word)))
        ->call('simpan')
        ->assertHasNoErrors();

    $n = Naskah::latest('id')->first();
    expect($n->baris()->where('bagian', 'judul')->value('rumusan_awal'))->toEndWith("TENTANG\nPENGELOLAAN AIR BERSIH DI DESA")
        ->and($n->baris()->where('bagian', 'diktum')->value('rumusan_awal'))->toContain('Menetapkan: PERATURAN BUPATI TENTANG PENGELOLAAN AIR BERSIH DI DESA.');
});

it('menyisipkan Menetapkan tepat sesudah MEMUTUSKAN bila belum ada', function () {
    expect(TemplateNaskah::diktumDenganTentang("MEMUTUSKAN:\nlain", 'Uji'))->toBe("MEMUTUSKAN:\nMenetapkan: PERATURAN BUPATI TENTANG UJI.\nlain")
        ->and(TemplateNaskah::diktumDenganTentang('', 'Uji'))->toBe("MEMUTUSKAN:\nMenetapkan: PERATURAN BUPATI TENTANG UJI.")
        ->and(TemplateNaskah::judulDenganTentang("PERATURAN BUPATI\nTENTANG\nLAMA\nSEKALI", 'Baru'))->toBe("PERATURAN BUPATI\nTENTANG\nBARU");
});

describe('Ubah Judul', function () {
    it('saat penyusunan mengubah Tentang, judul, dan Menetapkan sekaligus', function () {
        $n = drafLengkap($this);

        Livewire::actingAs($this->pd)->test(LayarKerja::class, ['naskah' => $n])
            ->assertSee('Ubah Judul')
            ->call('bukaUbahJudul')->assertSet('judulBaru', 'Air Bersih Desa')
            ->set('judulBaru', '  Pengelolaan   Air Minum Desa ')
            ->call('ubahJudul')->assertHasNoErrors();

        $n->refresh();
        expect($n->tentang)->toBe('Pengelolaan Air Minum Desa')
            ->and($n->baris()->where('bagian', 'judul')->value('rumusan_awal'))->toEndWith("TENTANG\nPENGELOLAAN AIR MINUM DESA")
            ->and($n->baris()->where('bagian', 'diktum')->value('rumusan_awal'))->toContain('TENTANG PENGELOLAAN AIR MINUM DESA.')
            ->and($n->riwayat()->where('aksi', 'ubah_judul')->value('sesudah'))->toBe('Pengelolaan Air Minum Desa');
    });

    it('saat penelitian menjadi rumusan penyempurnaan, dan hanya pemegang giliran yang bisa', function () {
        $n = drafLengkap($this);
        $this->alur->jalankan($n, $this->pd, 'ajukan');
        $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
        $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);

        expect(fn () => $this->alur->ubahJudul($n, $this->pd, 'Lain'))->toThrow(HttpException::class);

        $this->alur->ubahJudul($n, $this->peneliti, 'Air Bersih Desa Mandiri');
        $judul = $n->baris()->where('bagian', 'judul')->first();
        expect($judul->status)->toBe(B::DIUBAH)
            ->and($judul->rumusan_awal)->toEndWith('AIR BERSIH DESA')
            ->and($judul->rumusan_penyempurnaan)->toEndWith('AIR BERSIH DESA MANDIRI')
            ->and($n->fresh()->tentang)->toBe('Air Bersih Desa Mandiri');
    });
});

it('perbaikan PD harus diterima Peneliti sebelum naskah diteruskan', function () {
    $n = drafLengkap($this);
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
    $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);
    $pasal = $n->baris()->where('label', 'Pasal 1')->first();

    $n->baris()->where('id', '!=', $pasal->id)->get()->each(fn (B $b) => $this->alur->tetapkan($b, $this->peneliti));
    $this->alur->mintaPerbaikan($pasal, $this->peneliti, 'Lengkapi definisi.');
    $this->alur->jalankan($n, $this->peneliti, 'kirim_ke_pd');
    $this->alur->sesuaikan($pasal->fresh(), $this->pd, 'Desa adalah desa di Kabupaten Hulu Sungai Tengah, Provinsi Kalimantan Selatan.');
    $this->alur->jalankan($n, $this->pd, 'kembali_ke_peneliti');

    expect(fn () => $this->alur->jalankan($n->fresh(), $this->peneliti, 'teruskan_ke_verifikator'))->toThrow(ValidationException::class, '1 baris belum diperiksa');

    Livewire::actingAs($this->peneliti)->test(BarisKerja::class, ['baris' => $pasal->fresh(), 'naskah' => $n->fresh()])
        ->assertSee('Terima Perbaikan')
        ->call('terimaSesuaian', null);

    expect($pasal->fresh()->status)->toBe(B::DIUBAH)
        ->and($pasal->fresh()->teksAkhir())->toContain('Provinsi Kalimantan Selatan')
        ->and($this->alur->jalankan($n->fresh(), $this->peneliti, 'teruskan_ke_verifikator')->tahap->value)->toBe('validasi_rancangan');
});
