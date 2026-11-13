<?php

use App\Enums\TahapNaskah as T;
use App\Livewire\V2\Laporan;
use App\Models\Naskah;
use App\Models\NaskahRiwayat;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Support\LaporanNaskah;
use Database\Seeders\RoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::factory()->create(['nama_perangkat_daerah' => 'Dinas Uji'])->id]))
        ->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create())->assignRole('Peneliti');

    // Naskah yang diajukan $hariLalu hari lalu lalu berpindah tahap sesuai $jalur: [label tahap => hari setelah diajukan].
    $this->naskah = function (string $jenis, int $hariLalu, array $jalur, T $tahap) {
        $diajukan = now()->subDays($hariLalu);
        $n = Naskah::create(['jenis' => $jenis, 'tentang' => 'Uji Laporan ' . uniqid(), 'pemrakarsa_id' => $this->pd->id, 'peneliti_id' => $this->peneliti->id]);
        $n->forceFill(['tahap' => $tahap, 'diajukan_at' => $diajukan])->save();

        foreach ($jalur as $label => $hari) {
            $r = NaskahRiwayat::create(['naskah_id' => $n->id, 'user_id' => $this->admin->id, 'aksi' => 'tahap', 'sesudah' => $label]);
            $r->forceFill(['created_at' => $diajukan->copy()->addDays($hari)])->save();
        }

        return $n->fresh();
    };
});

it('menghitung ringkasan, lama per tahap, dan naskah yang tertahan', function () {
    // Selesai: 2 hari di Persetujuan Berkas, 8 hari di Penelitian, lalu selesai di hari ke-10.
    ($this->naskah)('Peraturan Bupati', 20, [
        T::PersetujuanBerkas->label() => 0,
        T::Penelitian->label() => 2,
        T::Selesai->label() => 10,
    ], T::Selesai);
    // Tertahan 9 hari di Penelitian.
    $tertahan = ($this->naskah)('Keputusan Bupati', 12, [T::PersetujuanBerkas->label() => 0, T::Penelitian->label() => 3], T::Penelitian);
    // Baru masuk, belum tertahan.
    ($this->naskah)('Keputusan Bupati', 2, [T::PersetujuanBerkas->label() => 0], T::PersetujuanBerkas);
    // Draf yang belum diajukan tidak dihitung.
    Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Masih Draf', 'pemrakarsa_id' => $this->pd->id]);

    $l = LaporanNaskah::susun();

    expect($l['ringkasan'])->toMatchArray(['diajukan' => 3, 'berjalan' => 2, 'selesai' => 1, 'rata_proses' => 10, 'peraturan' => 1, 'keputusan' => 2])
        ->and($l['tertahan'])->toHaveCount(1)
        ->and($l['tertahan'][0]['naskah']->id)->toBe($tertahan->id)
        ->and($l['tertahan'][0]['lama_tahap'])->toBe(9)
        ->and($l['perPd'][0])->toMatchArray(['nama' => 'Dinas Uji', 'diajukan' => 3, 'berjalan' => 2, 'selesai' => 1]);

    $penelitian = $l['perTahap']->firstWhere('tahap', T::Penelitian);
    $berkas = $l['perTahap']->firstWhere('tahap', T::PersetujuanBerkas);

    // Rata-rata hanya dari kunjungan yang sudah berakhir: Penelitian 8 hari, Persetujuan Berkas (2 + 3) / 2.
    expect($penelitian)->toMatchArray(['sekarang' => 1, 'rata_hari' => 8.0])
        ->and($berkas)->toMatchArray(['sekarang' => 1, 'rata_hari' => 2.5]);

    // Saring jenis.
    expect(LaporanNaskah::susun(null, 'Peraturan Bupati')['ringkasan']['diajukan'])->toBe(1);
});

it('halaman Laporan hanya untuk Bagian Hukum dan bisa diunduh sebagai Excel', function () {
    ($this->naskah)('Peraturan Bupati', 9, [T::PersetujuanBerkas->label() => 0], T::PersetujuanBerkas);

    $this->actingAs($this->admin)->get(route('laporan'))->assertOk()->assertSee('Tertahan lebih dari 7 hari')->assertSee('Dinas Uji');
    $this->actingAs($this->verifikator)->get(route('laporan'))->assertOk();
    $this->actingAs($this->pd)->get(route('laporan'))->assertForbidden();
    $this->actingAs($this->peneliti)->get(route('laporan'))->assertForbidden();

    Livewire::actingAs($this->admin)->test(Laporan::class)
        ->set('jenis', 'Peraturan Bupati')
        ->call('unduhExcel')
        ->assertFileDownloaded();
});

it('mengingatkan pemegang giliran setiap kelipatan batas hari, kecuali di Penandatanganan', function () {
    $tujuh = ($this->naskah)('Peraturan Bupati', 7, [T::PersetujuanBerkas->label() => 0], T::PersetujuanBerkas);
    ($this->naskah)('Peraturan Bupati', 8, [T::PersetujuanBerkas->label() => 0], T::PersetujuanBerkas);
    $penelitian = ($this->naskah)('Peraturan Bupati', 14, [T::Penelitian->label() => 0], T::Penelitian);
    ($this->naskah)('Peraturan Bupati', 7, [T::Penandatanganan->label() => 0], T::Penandatanganan);

    $this->artisan('naskah:ingatkan')->expectsOutput('Pengingat dikirim untuk 2 naskah.')->assertSuccessful();

    // Persetujuan Berkas dipegang Admin; Penelitian dipegang peneliti naskah itu saja.
    expect($this->admin->notifikasiNaskah()->count())->toBe(1)
        ->and($this->admin->notifikasiNaskah()->first()->data)->toMatchArray([
            'kategori' => 'tugas',
            'slug' => $tujuh->slug,
            'catatan' => 'Naskah ini sudah 7 hari di tahap ' . T::PersetujuanBerkas->label() . '.',
        ])
        ->and($this->admin->notifikasiNaskah()->first()->data['title'])->toStartWith('Pengingat: ')
        ->and($this->peneliti->notifikasiNaskah()->first()->data['slug'])->toBe($penelitian->slug)
        ->and($this->verifikator->notifikasiNaskah()->count())->toBe(0);
});

it('arsip Keputusan Bupati boleh tanpa Berita Daerah, dan tahunnya mengikuti tanggal penetapan', function () {
    Livewire::actingAs($this->verifikator)->test(\App\Livewire\Dokumentasi\AjukanDokumentasiProdukHukum::class)
        ->call('open')
        ->set('tentang_dokumentasi', 'Tim Uji')
        ->set('jenis_dokumentasi', 'Peraturan Bupati')
        ->set('perangkat_daerah_id', $this->pd->perangkat_daerah_id)
        ->set('nomor', '5')
        ->set('tanggal_penetapan', '2024-03-04')
        ->set('file_produk_hukum', \Illuminate\Http\UploadedFile::fake()->create('sk.pdf', 10, 'application/pdf'))
        // Peraturan Bupati tetap wajib Berita Daerah.
        ->call('store')->assertHasErrors(['nomor_berita', 'tahun_berita'])
        ->set('jenis_dokumentasi', 'Keputusan Bupati')
        ->assertSee('Opsional')
        ->call('store')->assertHasNoErrors();

    $d = \App\Models\DokumentasiProdukHukum::first();

    expect((int) $d->tahun)->toBe(2024)
        ->and($d->nomor_formatted)->toBe('Nomor 140/5/412.1.6/TAHUN 2024')
        ->and($d->nomor_tahun_berita)->toBe('-/-');

    // Saat diubah, tanggal yang dibetulkan ikut membetulkan tahun; berita daerah tetap boleh kosong.
    Livewire::actingAs($this->verifikator)->test(\App\Livewire\Dokumentasi\Dokumentasi::class)
        ->call('edit', $d->id)
        ->assertSet('nomor_berita', '')
        ->set('tanggal_penetapan', '2025-01-10')
        ->call('update')->assertHasNoErrors();

    expect((int) $d->fresh()->tahun)->toBe(2025)
        ->and($d->fresh()->nomor_formatted)->toBe('Nomor 140/5/412.1.6/TAHUN 2025');
});

it('tab Per Perangkat Daerah menyebar naskah menurut langkah, dan bisa disaring per Perangkat Daerah', function () {
    ($this->naskah)('Peraturan Bupati', 10, [T::Penelitian->label() => 0], T::Penelitian);
    ($this->naskah)('Peraturan Bupati', 30, [T::Selesai->label() => 20], T::Selesai);
    ($this->naskah)('Keputusan Bupati', 5, [T::PersetujuanBerkas->label() => 0], T::PersetujuanBerkas);
    PerangkatDaerah::factory()->create(['nama_perangkat_daerah' => 'Dinas Belum Mengajukan']);

    $matriks = LaporanNaskah::matriksPd(LaporanNaskah::baris(null, 'Peraturan Bupati'));
    expect($matriks)->toHaveCount(1)
        ->and($matriks[0]['nama'])->toBe('Dinas Uji')
        ->and($matriks[0]['total'])->toBe(2)
        ->and($matriks[0]['jumlah'][T::Penelitian->langkah()])->toBe(1)
        ->and($matriks[0]['jumlah'][T::Selesai->langkah()])->toBe(1);

    // Ikut menampilkan PD yang belum pernah mengajukan.
    expect(LaporanNaskah::matriksPd(LaporanNaskah::baris(), true)->pluck('nama'))->toContain('Dinas Belum Mengajukan');

    Livewire::actingAs($this->admin)->test(Laporan::class)
        ->set('tab', 'perangkat')
        ->assertSee('Sebaran per Perangkat Daerah')
        ->assertSee('Dinas Uji')
        ->assertViewHas('matriks', fn ($m) => ! $m->pluck('nama')->contains('Dinas Belum Mengajukan'))
        ->set('semuaPd', true)
        ->assertViewHas('matriks', fn ($m) => $m->pluck('nama')->contains('Dinas Belum Mengajukan'))
        // Filter Perangkat Daerah juga berlaku di Ringkasan.
        ->set('tab', 'ringkasan')
        ->set('pd', (string) PerangkatDaerah::where('nama_perangkat_daerah', 'Dinas Belum Mengajukan')->value('id'))
        ->assertViewHas('ringkasan', fn ($r) => $r['diajukan'] === 0);
});

it('tab Aktivitas menampilkan kegiatan semua naskah dengan kalimat yang jelas dan bisa disaring', function () {
    $alur = app(\App\Services\AlurNaskah::class);
    $n = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Uji Aktivitas', 'pemrakarsa_id' => $this->pd->id, 'peneliti_id' => $this->peneliti->id]);
    $n->forceFill(['tahap' => T::PersetujuanBerkas, 'diajukan_at' => now()])->save();
    $alur->jalankan($n, $this->admin, 'setujui_berkas');
    $alur->jalankan($n->fresh(), $this->verifikator, 'tugaskan_peneliti', null, $this->peneliti->id);

    $halaman = Livewire::actingAs($this->admin)->test(Laporan::class)->set('tab', 'aktivitas');
    $halaman->assertSee('Uji Aktivitas')
        ->assertSee('menugaskan peneliti')
        ->assertSee($this->verifikator->nama_user)
        ->assertSee('(Verifikator)');

    // Disaring per peran pelaku: hanya kegiatan Admin.
    $halaman->set('peran', 'Admin')->assertDontSee('menugaskan peneliti')->assertSee($this->admin->nama_user);

    // Rentang tanggal di masa depan: kosong.
    $halaman->set('peran', '')->set('dari', now()->addDay()->toDateString())->assertSee('Belum ada aktivitas dengan filter ini.');

    // Kalimat untuk perpindahan tahap yang umum.
    $kalimat = fn ($dari, $ke) => \App\Support\KalimatRiwayat::untuk(new \App\Models\NaskahRiwayat(['aksi' => 'tahap', 'sebelum' => $dari->label(), 'sesudah' => $ke->label()]))['teks'];
    expect($kalimat(T::Draf, T::PersetujuanBerkas))->toBe('mengajukan rancangan')
        ->and($kalimat(T::Final, T::Penandatanganan))->toBe('menerbitkan nota dinas')
        ->and($kalimat(T::Arsip, T::Selesai))->toBe('mengarsipkan ke JDIH')
        ->and($kalimat(T::PersetujuanBerkas, T::Draf))->toStartWith('mengembalikan naskah');
});
