<?php

use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Support\TurPengenalan;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]))->assignRole('Perangkat Daerah');
});

it('muncul sekali di Dashboard, lalu tidak lagi setelah selesai atau dilewati', function () {
    $this->actingAs($this->pd)->get(route('dashboard'))->assertOk()->assertSee('data-tur-pengenalan', false)->assertSee('Rancanganku');

    // Hanya di Dashboard.
    $this->actingAs($this->pd)->get(route('panduan'))->assertOk()->assertDontSee('data-tur-pengenalan', false);

    $this->actingAs($this->pd)->post(route('tur.selesai'))->assertNoContent();
    expect($this->pd->fresh()->tur_selesai_at)->not->toBeNull();

    $this->actingAs($this->pd)->get(route('dashboard'))->assertDontSee('data-tur-pengenalan', false);

    // Bisa diulang dari Panduan atau menu akun.
    $this->actingAs($this->pd)->get(route('panduan'))->assertSee(route('dashboard', ['tur' => 1]), false);
    $this->actingAs($this->pd)->get(route('dashboard', ['tur' => 1]))->assertSee('data-tur-pengenalan', false);
});

it('langkahnya sesuai peran dan hanya menyorot menu yang dimiliki', function () {
    $sasaran = fn (User $u) => collect(TurPengenalan::langkah($u))->pluck('target')->filter()->values()->all();

    expect($sasaran($this->pd))->toContain('menu-rancanganku', 'menu-fasilitasiku', 'giliran', 'lonceng', 'menu-panduan')
        ->not->toContain('menu-laporan', 'menu-persetujuan-berkas');

    $admin = tap(User::factory()->create())->assignRole('Admin');
    expect($sasaran($admin))->toContain('menu-persetujuan-berkas', 'menu-laporan', 'menu-daftar-pengguna')
        ->not->toContain('menu-rancanganku');

    // Setiap menu yang disorot benar-benar ada di sidebar pengguna itu.
    foreach ([$this->pd, $admin] as $u) {
        $html = $this->actingAs($u)->get(route('panduan'))->getContent();
        foreach (array_filter($sasaran($u), fn ($t) => str_starts_with($t, 'menu-')) as $t) {
            expect($html)->toContain("data-tur=\"{$t}\"");
        }
    }
});

it('tidak muncul selama pengguna wajib mengganti kata sandi sementara', function () {
    $this->pd->forceFill(['wajib_ganti_sandi' => true])->save();

    expect(TurPengenalan::tampil($this->pd))->toBeFalse();
});

it('panduan menampilkan screenshot, dan setiap gambar yang disebut memang ada', function () {
    foreach (glob(base_path('docs/panduan/*.md')) as $berkas) {
        preg_match_all('#\]\(\.\./\.\./public/(img/panduan/[^)]+)\)#', file_get_contents($berkas), $cocok);

        expect($cocok[1])->not->toBeEmpty();
        foreach ($cocok[1] as $gambar) {
            expect(public_path($gambar))->toBeFile();
        }
    }

    $this->actingAs($this->pd)->get(route('panduan'))
        ->assertOk()
        ->assertSee(asset('img/panduan/pd-dashboard.jpg'), false)
        ->assertSee('loading="lazy"', false);
});

it('tur dan panduan Admin menjelaskan cara menerbitkan nota dinas', function () {
    $admin = tap(User::factory()->create())->assignRole('Admin');
    $nota = collect(TurPengenalan::langkah($admin))->firstWhere('target', 'menu-daftar-nota-dinas');

    expect($nota['judul'])->toBe('Menerbitkan Nota Dinas')
        ->and($nota['isi'])->toContain('Terbitkan Nota Dinas', 'nomor urut', 'tanda tangan');

    $this->actingAs($admin)->get(route('panduan', 'admin'))
        ->assertSee('Menerbitkan nota dinas')
        ->assertSee(asset('img/panduan/admin-nota-dinas.jpg'), false);
});

it('memakai kalimat untuk aplikasi kosong bila belum ada naskah', function () {
    $langkah = collect(TurPengenalan::langkah($this->pd));

    expect($langkah->firstWhere('target', 'giliran')['isi_kosong'])->toContain('Ajukan Rancangan Baru')
        ->and($langkah->last()['cek'])->toBe('giliran')
        ->and($langkah->last()['isi_kosong'])->toContain('rancangan pertama');

    // Peran lain tidak diarahkan mengajukan rancangan.
    $peneliti = tap(User::factory()->create())->assignRole('Peneliti');
    expect(collect(TurPengenalan::langkah($peneliti))->firstWhere('target', 'giliran')['isi_kosong'])->toContain('tugas baru');

    // Dashboard menandai bagian yang kosong; tur memilih kalimatnya dari tanda ini.
    $html = $this->actingAs($this->pd)->get(route('dashboard'))->getContent();
    expect($html)->toMatch('/data-tur="statistik"[^>]*data-kosong/')
        ->toMatch('/data-tur="giliran"[^>]*data-kosong/');

    \App\Models\Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Draf Pertama', 'pemrakarsa_id' => $this->pd->id]);
    $html = $this->actingAs($this->pd)->get(route('dashboard'))->getContent();
    expect($html)->not->toMatch('/data-tur="giliran"[^>]*data-kosong/')
        ->not->toMatch('/data-tur="statistik"[^>]*data-kosong/');
});
