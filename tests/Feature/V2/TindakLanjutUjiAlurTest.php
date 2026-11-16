<?php

use App\Livewire\V2\LayarKerja;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahBerkas;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Services\AlurNaskah;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

/*
 * Temuan uji alur lengkap: daftar periksa Admin, pratinjau PDF, naskah hasil perbaikan saat fasilitasi,
 * arahan PD yang basi di matriks, tugas basi di notifikasi, beban peneliti, dan dibawa ke antrean.
 */

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    $this->alur = app(AlurNaskah::class);
    $this->pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Uji'])->id]))->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->admin2 = tap(User::factory()->create())->assignRole('Admin');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create(['nama_user' => 'Peneliti Satu']))->assignRole('Peneliti');
});

function drafUji(object $uji): Naskah
{
    $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Air Bersih', 'pemrakarsa_id' => $uji->pd->id, 'nomor_nota' => 'ND-' . uniqid()]);
    $uji->alur->isiBaris($n, [
        ['bagian' => 'judul', 'label' => null, 'teks' => "PERATURAN BUPATI\nTENTANG\nAIR BERSIH"],
        ['bagian' => 'menimbang', 'label' => null, 'teks' => 'bahwa satu'],
        ['bagian' => 'batang_tubuh', 'label' => 'Pasal 1', 'teks' => 'Isi pasal.'],
    ]);
    $n->berkas()->create(['jenis' => NaskahBerkas::NOTA_DINAS_PD, 'path' => 'x.pdf', 'nama_asli' => 'nota.pdf', 'diunggah_oleh' => $uji->pd->id]);

    return $n;
}

function sampaiDisetujui(object $uji, Naskah $n): Naskah
{
    $uji->alur->jalankan($n, $uji->pd, 'ajukan');
    $uji->alur->jalankan($n, $uji->admin, 'setujui_berkas');
    $uji->alur->jalankan($n, $uji->verifikator, 'tugaskan_peneliti', null, $uji->peneliti->id);
    $n->baris()->get()->each(fn (B $b) => $uji->alur->tetapkan($b, $uji->peneliti));
    $uji->alur->jalankan($n, $uji->peneliti, 'teruskan_ke_verifikator');

    return $uji->alur->jalankan($n, $uji->verifikator, 'setujui_rancangan');
}

it('Admin wajib mencentang daftar periksa, lalu dibawa kembali ke antrean', function () {
    $n = drafUji($this);
    $this->alur->jalankan($n, $this->pd, 'ajukan');

    $layar = Livewire::actingAs($this->admin)
        ->withQueryParams([])
        ->test(LayarKerja::class, ['naskah' => $n->fresh()])
        ->assertDontSee('Baris diperiksa')
        ->call('pilihAksi', 'setujui_berkas')
        ->assertSee('Daftar periksa')
        ->assertSee('Lihat Nota Dinas PD')
        ->call('jalankanAksi')
        ->assertHasErrors('periksaBerkas');

    $layar->set('periksaBerkas', array_keys(LayarKerja::PERIKSA_BERKAS))
        ->call('jalankanAksi')
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect($n->fresh()->tahap->value)->toBe('pilih_peneliti')
        ->and(session('toast')['message'])->toContain('giliran Verifikator');
});

it('berkas PDF bisa dilihat di halaman tanpa diunduh', function () {
    $n = drafUji($this);
    Storage::disk('local')->put('x.pdf', '%PDF-1.4 uji');
    $berkas = $n->berkas()->first();

    $this->actingAs($this->pd)->get(route('naskah.berkas', [$n, $berkas, 'lihat' => 1]))
        ->assertOk()->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'inline; filename=nota.pdf');
    expect($this->actingAs($this->pd)->get(route('naskah.berkas', [$n, $berkas]))->headers->get('Content-Disposition'))->toContain('attachment');
});

it('Ajukan fasilitasi tanpa unggahan: naskah hasil perbaikan disusun dari matriks', function () {
    $n = sampaiDisetujui($this, drafUji($this));

    expect($this->alur->aksiTersedia($n->fresh(), $this->pd)['ajukan_fasilitasi']['hambatan'])->toBeNull();

    Livewire::actingAs($this->pd)->test(LayarKerja::class, ['naskah' => $n])
        ->call('pilihAksi', 'ajukan_fasilitasi')
        ->assertSee('tidak perlu mengunggah file')
        ->assertSee('Lihat naskah')
        ->call('jalankanAksi')
        ->assertHasNoErrors();

    expect($n->fresh()->tahap->value)->toBe('persetujuan_fasilitasi')
        ->and($n->berkas()->where('jenis', NaskahBerkas::NASKAH_FASILITASI)->exists())->toBeFalse();

    // Peneliti memeriksa naskah dari sistem, yang bisa dipratinjau di browser.
    Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $n->fresh()])
        ->assertSee('Naskah hasil perbaikan, dibuat dari isi matriks terbaru.');
    expect($this->actingAs($this->peneliti)->get(route('naskah.cetak', [$n, 'naskah-pdf', 'lihat' => 1]))->headers->get('Content-Disposition'))->toStartWith('inline')
        ->and($this->actingAs($this->peneliti)->get(route('naskah.cetak', [$n, 'naskah-pdf']))->headers->get('Content-Disposition'))->toStartWith('attachment');
});

it('arahan untuk PD tidak ikut tercetak setelah perbaikannya diterima', function () {
    $n = drafUji($this);
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
    $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);
    [$a, $b] = [$n->baris()->where('bagian', 'menimbang')->first(), $n->baris()->where('label', 'Pasal 1')->first()];
    $n->baris()->whereNotIn('id', [$a->id, $b->id])->get()->each(fn (B $x) => $this->alur->tetapkan($x, $this->peneliti));
    $this->alur->mintaPerbaikan($a, $this->peneliti, 'Lengkapi pertimbangan.');
    $this->alur->mintaPerbaikan($b, $this->peneliti, 'Lengkapi pasal.');
    $this->alur->jalankan($n, $this->peneliti, 'kirim_ke_pd');
    $this->alur->sesuaikan($a->fresh(), $this->pd, 'bahwa satu yang lengkap');
    $this->alur->sesuaikan($b->fresh(), $this->pd, 'Isi pasal yang lengkap.');
    $this->alur->jalankan($n, $this->pd, 'kembali_ke_peneliti');

    // Keterangan yang masih berisi arahan lama dibuang; keterangan baru dipertahankan.
    $this->alur->terimaSesuaian($a->fresh(), $this->peneliti, 'Lengkapi pertimbangan.');
    $this->alur->terimaSesuaian($b->fresh(), $this->peneliti, 'Disesuaikan dengan Perda Nomor 3 Tahun 2020.');

    expect($a->fresh()->keterangan)->toBeNull()
        ->and($b->fresh()->keterangan)->toBe('Disesuaikan dengan Perda Nomor 3 Tahun 2020.');
});

it('tugas tahap sebelumnya tidak lagi "Perlu tindakan" setelah dikerjakan siapa pun', function () {
    $n = drafUji($this);
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $tugasAdmin2 = fn () => $this->admin2->notifikasiNaskah()->whereNull('read_at')->where('data->slug', $n->slug)->count();
    expect($tugasAdmin2())->toBe(1);

    // Admin pertama yang menyetujui; tugas Admin kedua ikut selesai.
    $this->alur->jalankan($n->fresh(), $this->admin, 'setujui_berkas');
    expect($tugasAdmin2())->toBe(0);
});

it('Verifikator melihat beban tiap peneliti saat menugaskan, dan placeholder tidak bisa dipilih', function () {
    $lain = drafUji($this);
    $this->alur->jalankan($lain, $this->pd, 'ajukan');
    $this->alur->jalankan($lain, $this->admin, 'setujui_berkas');
    $this->alur->jalankan($lain, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);

    $n = drafUji($this);
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');

    Livewire::actingAs($this->verifikator)->test(LayarKerja::class, ['naskah' => $n->fresh()])
        ->call('pilihAksi', 'tugaskan_peneliti')
        ->assertSee('Peneliti Satu · 1 naskah berjalan, 1 menunggu dikerjakan')
        ->assertSeeHtml('<option value="" hidden>Pilih peneliti</option>');
});

it('jendela Kirim ke PD menyebut baris yang ditandai', function () {
    $n = drafUji($this);
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
    $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);
    $pasal = $n->baris()->where('label', 'Pasal 1')->first();
    $n->baris()->where('id', '!=', $pasal->id)->get()->each(fn (B $x) => $this->alur->tetapkan($x, $this->peneliti));
    $this->alur->mintaPerbaikan($pasal, $this->peneliti, 'Besaran tarif belum ada.');

    Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $n->fresh()])
        ->call('pilihAksi', 'kirim_ke_pd')
        ->assertSee('1 baris dikirim untuk diperbaiki PD')
        ->assertSee('Besaran tarif belum ada.');
});

it('naskah yang dikembalikan hanya dengan catatan tidak membuka layar kosong', function () {
    $n = drafUji($this);
    $this->alur->jalankan($n, $this->pd, 'ajukan');
    $this->alur->jalankan($n, $this->admin, 'setujui_berkas');
    $n = $this->alur->jalankan($n, $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);
    $n->baris()->get()->each(fn (B $b) => $this->alur->tetapkan($b, $this->peneliti));
    $this->alur->jalankan($n, $this->peneliti, 'teruskan_ke_verifikator');
    $this->alur->jalankan($n, $this->verifikator, 'tolak_rancangan', 'Konsiderans kurang lengkap.');

    Livewire::actingAs($this->peneliti)->test(LayarKerja::class, ['naskah' => $n->fresh()])
        ->assertDontSee('Tidak ada baris pada tampilan ini')
        ->assertSee('Konsiderans kurang lengkap.');
});
