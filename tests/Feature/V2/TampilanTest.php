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

it('tampilan awal selalu terang, tidak mengikuti tema Windows atau HP', function () {
    $halaman = [
        $this->get('/'),
        $this->get(route('login')),
        $this->get(route('jdih.index')),
        $this->actingAs($this->admin)->get(route('v2.beranda')),
    ];

    foreach ($halaman as $respons) {
        $respons->assertOk()
            ->assertSee('<meta name="color-scheme" content="only light">', false)
            ->assertSee("classList.toggle('dark', theme === 'dark')", false)
            ->assertDontSee('prefers-color-scheme', false);
    }
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
    $alamat = route('v2.naskah.kerja', $naskah);

    Livewire::actingAs($pd)->test(LayarKerja::class, ['naskah' => $naskah])
        ->call('buangDraf')
        ->assertRedirect(route('v2.beranda'));

    expect(session('pesan'))->toBe("Draf {$naskah->nomor} sudah dibuang.")
        ->and(Naskah::find($naskah->id))->toBeNull();

    $this->actingAs($pd)->get($alamat)
        ->assertRedirect(route('v2.beranda'))
        ->assertSessionHas('pesan', 'Naskah yang Anda buka sudah dibuang atau tidak ditemukan.');

    $this->actingAs($pd)->withSession(['pesan' => 'Draf NPB-1 sudah dibuang.'])->get(route('v2.beranda'))
        ->assertOk()
        ->assertSee('Draf NPB-1 sudah dibuang.');
});

it('daftar kosong menawarkan ajukan rancangan, dan pencarian kosong menawarkan hapus filter', function () {
    $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');

    Livewire::actingAs($pd)->test(DaftarNaskah::class, ['kelompok' => 'rancanganku'])
        ->assertSee('Daftar “Sedang Diajukan” masih kosong.')
        ->assertSee('Ajukan Rancangan Baru')
        ->assertDontSee('Hapus pencarian dan filter')
        ->set('search', 'tidak-ada')
        ->assertSee('Tidak ada naskah yang cocok dengan pencarian.')
        ->assertSee('Hapus pencarian dan filter');
});
