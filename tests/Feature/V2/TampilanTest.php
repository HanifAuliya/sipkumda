<?php

use App\Livewire\V2\DaftarNaskah;
use App\Livewire\V2\LayarKerja;
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

it('menu berpindah tanpa muat ulang, juga di halaman di luar alur naskah', function () {
    foreach (['dashboard', 'user.management'] as $rute) {
        $this->actingAs($this->admin)->get(route($rute))
            ->assertOk()
            ->assertSee('wire:navigate', false)
            ->assertSee('data-penanda-menu', false);
    }
});

it('Dashboard di /dashboard, tanpa awalan /v2 dan tanpa pilihan aplikasi', function () {
    expect(route('dashboard', absolute: false))->toBe('/dashboard');

    $this->actingAs($this->admin)->get('/v2')->assertNotFound();
    $this->actingAs($this->admin)->get('/pilih-aplikasi')->assertNotFound();

    $this->actingAs($this->admin)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Pindah Aplikasi');
});

it('tampilan awal selalu terang, tidak mengikuti tema Windows atau HP', function () {
    $halaman = [
        $this->get('/'),
        $this->get(route('login')),
        $this->get(route('jdih.index')),
        $this->actingAs($this->admin)->get(route('dashboard')),
    ];

    foreach ($halaman as $respons) {
        $respons->assertOk()
            ->assertSee('<meta name="color-scheme" content="only light">', false)
            ->assertSee("classList.toggle('dark', theme === 'dark')", false)
            ->assertDontSee('prefers-color-scheme', false);
    }
});

it('kerangka saat server lambat dan layar masuk ada di semua halaman dalam', function () {
    foreach (['dashboard', 'user.management'] as $rute) {
        $this->actingAs($this->admin)->get(route($rute))
            ->assertSee('id="kerangka-halaman"', false)
            ->assertSee('id="layar-masuk"', false)
            ->assertSee('Sistem Fasilitasi Produk Hukum Daerah');
    }
});

it('layar masuk tampil lagi setelah login berikutnya', function () {
    auth()->logout();

    $this->get(route('login'))->assertOk()->assertSee("sessionStorage.removeItem('splash-shown')", false);
});

it('tombol Kembali di halaman mana pun menuju Dashboard', function () {
    $this->actingAs($this->admin)->get(route('user.management'))
        ->assertOk()
        ->assertSee('href="'.route('dashboard').'"', false);
});

it('setiap menu v2 satu peran memakai ikon yang berbeda', function () {
    foreach (['Admin', 'Verifikator', 'Peneliti', 'Perangkat Daerah', 'Tamu'] as $peran) {
        $menu = collect(Navigation::sections())->pluck('items')->flatten(1)
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
    $naskah = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Uji Tombol Kerjakan', 'pemrakarsa_id' => $pd->id]);
    $lembarKerja = route('naskah.kerja', $naskah);

    // Draf ada di tab Draf, bukan di Sedang Diajukan.
    $this->actingAs($pd)->get(route('daftar', ['kelompok' => 'rancanganku', 'tab' => 'draf']))
        ->assertOk()
        ->assertSee($lembarKerja, false);
    $this->actingAs($pd)->get(route('daftar', 'rancanganku'))
        ->assertOk()
        ->assertSee('Draf')
        ->assertDontSee('Uji Tombol Kerjakan');

    $this->actingAs($this->admin)->get(route('daftar', 'rancangan'))
        ->assertOk()
        ->assertSee('Uji Tombol Kerjakan')
        ->assertDontSee($lembarKerja, false);
});

it('kartu naskah menonjolkan tombol Kerjakan dan petunjuknya tidak bisa ditutup', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $pd->id]);

    $this->actingAs($pd);
    $kartu = Blade::render('<x-naskah.item :naskah="$naskah" />', ['naskah' => Naskah::first()]);

    expect($kartu)->toContain('Kerjakan')
        ->toContain('border-l-primary-500')
        ->not->toContain('Dibuat tahun')
        ->not->toContain('aria-label="Tutup"');

    $this->actingAs($this->admin);
    $kartu = Blade::render('<x-naskah.item :naskah="$naskah" />', ['naskah' => Naskah::first()]);

    expect($kartu)->not->toContain('Kerjakan')->not->toContain('border-l-primary-500');
});

it('naskah yang sudah dibuang mengarah ke Dashboard dengan pesan, bukan halaman 404', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
    $naskah = Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $pd->id]);
    $alamat = route('naskah.kerja', $naskah);

    Livewire::actingAs($pd)->test(LayarKerja::class, ['naskah' => $naskah])
        ->call('buangDraf')
        ->assertRedirect(route('dashboard'));

    expect(session('pesan'))->toBe('Draf "Uji" sudah dihapus.')
        ->and(Naskah::find($naskah->id))->toBeNull();

    $this->actingAs($pd)->get($alamat)
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('pesan', 'Naskah yang Anda buka sudah dibuang atau tidak ditemukan.');

    $this->actingAs($pd)->withSession(['pesan' => 'Draf PB-1 sudah dibuang.'])->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Draf PB-1 sudah dibuang.');
});

it('daftar kosong menawarkan ajukan rancangan, dan pencarian kosong menawarkan hapus filter', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');

    Livewire::actingAs($pd)->test(DaftarNaskah::class, ['kelompok' => 'rancanganku'])
        ->assertSee('Daftar “Sedang Diajukan” masih kosong.')
        ->assertSee('Ajukan Rancangan Baru')
        ->assertDontSee('Hapus pencarian dan filter')
        ->set('search', 'tidak-ada')
        ->assertSee('Tidak ada naskah yang cocok dengan pencarian atau filter.')
        ->assertSee('Hapus pencarian dan filter');
});
