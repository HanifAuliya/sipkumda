<?php

use App\Livewire\V2\DaftarNaskah;
use App\Models\Naskah;
use App\Models\User;
use App\Support\Navigation;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
});

it('menu v2 berpindah tanpa muat ulang, sedangkan aplikasi lama tetap seperti semula', function () {
    $this->actingAs($this->admin)->get(route('v2.beranda'))
        ->assertOk()
        ->assertSee('wire:navigate', false)
        ->assertSee('data-penanda-menu', false);

    $this->actingAs($this->admin)->withSession([Navigation::SESI_APLIKASI => Navigation::LAMA])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('wire:navigate', false)
        ->assertDontSee('data-penanda-menu', false);
});

it('kerangka saat server lambat hanya ada di v2, layar masuk ada di kedua aplikasi', function () {
    $this->actingAs($this->admin)->get(route('v2.beranda'))
        ->assertSee('id="kerangka-halaman"', false)
        ->assertSee('id="layar-masuk"', false)
        ->assertSee('Sistem Fasilitasi Produk Hukum Daerah');

    $this->actingAs($this->admin)->withSession([Navigation::SESI_APLIKASI => Navigation::LAMA])
        ->get(route('dashboard'))
        ->assertDontSee('id="kerangka-halaman"', false)
        ->assertSee('id="layar-masuk"', false);
});

it('layar masuk tampil lagi setelah login berikutnya', function () {
    auth()->logout();

    $this->get(route('login'))->assertOk()->assertSee("sessionStorage.removeItem('splash-shown')", false);
});

it('tombol Kembali mengikuti aplikasi yang sedang dipakai', function () {
    // Halaman lama yang dibuka dari menu v2 tidak boleh melempar pengguna ke aplikasi lama.
    $this->actingAs($this->admin)->withSession([Navigation::SESI_APLIKASI => Navigation::V2])
        ->get(route('user.management'))
        ->assertOk()
        ->assertSee('href="'.route('v2.beranda').'"', false);

    $this->actingAs($this->admin)->withSession([Navigation::SESI_APLIKASI => Navigation::LAMA])
        ->get(route('user.management'))
        ->assertOk()
        ->assertSee('href="'.route('dashboard').'"', false)
        ->assertDontSee('wire:navigate', false);
});

it('setiap menu v2 satu peran memakai ikon yang berbeda', function () {
    foreach (['Admin', 'Verifikator', 'Peneliti', 'Perangkat Daerah', 'Tamu'] as $peran) {
        $menu = collect(Navigation::sectionsV2())->pluck('items')->flatten(1)
            ->filter(fn (array $item) => empty($item['roles']) || in_array($peran, $item['roles'], true));

        expect($menu->pluck('icon')->duplicates()->all())->toBe([], "Ikon kembar pada peran {$peran}");
    }
});

it('tombol ikon cukup besar untuk diklik dan ukuran ikonnya mengikuti tombol', function () {
    $html = Blade::render('<x-ui.button size="sm" square icon="eye" aria-label="Lihat" />');

    expect($html)->toContain('size-9')
        ->toContain('max-sm:size-10')
        ->toContain('bi-eye text-sm')
        ->not->toContain('size-8');
});

it('tombol Kerjakan hanya tampil pada naskah yang menjadi giliran pengguna', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    $naskah = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $pd->id]);
    $lembarKerja = route('v2.naskah.kerja', $naskah);

    $this->actingAs($pd)->get(route('v2.daftar', 'rancanganku'))
        ->assertOk()
        ->assertSee($lembarKerja, false);

    $this->actingAs($this->admin)->get(route('v2.daftar', 'rancangan'))
        ->assertOk()
        ->assertSee($naskah->nomor)
        ->assertDontSee($lembarKerja, false);
});

it('daftar kosong menawarkan ajukan rancangan, dan pencarian kosong menawarkan hapus filter', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');

    Livewire::actingAs($pd)->test(DaftarNaskah::class, ['kelompok' => 'rancanganku'])
        ->assertSee('Tidak ada naskah pada daftar ini.')
        ->assertSee('Ajukan Rancangan Baru')
        ->assertDontSee('Hapus pencarian dan filter')
        ->set('search', 'tidak-ada')
        ->assertSee('Tidak ada naskah yang cocok dengan pencarian.')
        ->assertSee('Hapus pencarian dan filter');
});
