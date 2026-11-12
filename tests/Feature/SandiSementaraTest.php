<?php

use App\Livewire\Profile\UpdatePassword;
use App\Livewire\UserManagement;
use App\Models\PerangkatDaerah;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->pengguna = tap(User::factory()->create([
        'email_verified_at' => null,
        'perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id,
    ]))->assignRole('Perangkat Daerah');
});

it('Admin membuat kata sandi sementara yang tampil sekali, tanpa email', function () {
    $halaman = Livewire::actingAs($this->admin)->test(UserManagement::class)
        ->call('buatSandiSementara', $this->pengguna->id)
        ->assertDispatched('openModal', 'modalSandiSementara');

    $sandi = $halaman->get('sandiSementara')['sandi'];
    $pengguna = $this->pengguna->fresh();

    expect($sandi)->toMatch('/^[a-z2-9]{4}-[a-z2-9]{4}-[a-z2-9]{4}$/')
        ->and(Hash::check($sandi, $pengguna->password))->toBeTrue()
        ->and($pengguna->wajib_ganti_sandi)->toBeTrue()
        // Akun yang belum aktif langsung bisa dipakai masuk.
        ->and($pengguna->email_verified_at)->not->toBeNull();

    $halaman->call('tutupSandiSementara')->assertSet('sandiSementara', null);
});

it('hanya Admin, dan tidak untuk akun sendiri', function () {
    $verifikator = tap(User::factory()->create())->assignRole('Verifikator');

    Livewire::actingAs($verifikator)->test(UserManagement::class)
        ->call('buatSandiSementara', $this->pengguna->id)->assertForbidden();
    Livewire::actingAs($this->admin)->test(UserManagement::class)
        ->call('buatSandiSementara', $this->admin->id)->assertForbidden();

    expect($this->pengguna->fresh()->wajib_ganti_sandi)->toBeFalse();
});

it('pengguna dengan kata sandi sementara diarahkan ke Profil sampai menggantinya', function () {
    $this->pengguna->forceFill(['password' => Hash::make('abcd-efgh-jkmn'), 'wajib_ganti_sandi' => true, 'email_verified_at' => now()])->save();

    $this->actingAs($this->pengguna)->get(route('dashboard'))->assertRedirect(route('profile.edit'));
    $this->actingAs($this->pengguna)->get(route('profile.edit'))->assertOk()->assertSee('kata sandi sementara');

    Livewire::actingAs($this->pengguna)->test(UpdatePassword::class)
        ->assertSet('mengubah', true)
        ->set('current_password', 'abcd-efgh-jkmn')
        ->set('password', 'SandiBaru#2026')
        ->set('password_confirmation', 'SandiBaru#2026')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect($this->pengguna->fresh()->wajib_ganti_sandi)->toBeFalse();
    $this->actingAs($this->pengguna->fresh())->get(route('dashboard'))->assertOk();
});

it('perintah server membuat Super Admin pertama atau memulihkan akun yang terkunci', function () {
    $this->artisan('akun:super-admin', ['nip' => '197001012000031001'])->assertFailed();

    $this->artisan('akun:super-admin', ['nip' => '197001012000031001', '--nama' => 'Admin Darurat', '--email' => 'darurat@example.com'])
        ->expectsOutputToContain('Akun Super Admin Admin Darurat dibuat.')
        ->assertSuccessful();

    $baru = User::where('NIP', '197001012000031001')->firstOrFail();
    expect($baru->hasRole('Super Admin'))->toBeTrue()->and($baru->wajib_ganti_sandi)->toBeTrue();

    // Akun yang sudah ada: kata sandi diganti, peran tidak berubah.
    $lama = $this->pengguna->password;
    $this->artisan('akun:super-admin', ['nip' => $this->pengguna->NIP])->assertSuccessful();

    expect($this->pengguna->fresh()->password)->not->toBe($lama)
        ->and($this->pengguna->fresh()->wajib_ganti_sandi)->toBeTrue()
        ->and($this->pengguna->fresh()->hasRole('Super Admin'))->toBeFalse();
});

it('akun yang sudah punya naskah tidak terhapus dan tidak memunculkan galat', function () {
    \App\Models\Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $this->pengguna->id]);
    $kosong = tap(User::factory()->create())->assignRole('Peneliti');

    Livewire::actingAs($this->admin)->test(UserManagement::class)
        ->call('delete', $this->pengguna->id)
        ->assertDispatched('swal:modal')
        ->call('delete', $kosong->id)
        ->call('delete', $this->admin->id)->assertForbidden();

    expect(User::find($this->pengguna->id))->not->toBeNull()
        ->and(User::find($kosong->id))->toBeNull();
});

it('panduan di aplikasi sesuai peran; Admin bisa membaca semua', function () {
    $this->actingAs($this->pengguna)->get(route('panduan'))->assertOk()->assertSee('Panduan Perangkat Daerah')->assertDontSee('Panduan Verifikator');
    $this->actingAs($this->pengguna)->get(route('panduan', 'admin'))->assertNotFound();
    $this->actingAs($this->pengguna)->get('/panduan/..%2F..%2F.env')->assertNotFound();

    $this->actingAs($this->admin)->get(route('panduan', 'verifikator'))->assertOk()->assertSee('Panduan Verifikator')->assertSee('Perangkat Daerah');
});

it('Verifikator tidak bisa menjadi Admin dengan mengubah isAdmin dari peramban', function () {
    $verifikator = tap(User::factory()->create())->assignRole('Verifikator');

    // Properti terkunci: perubahan dari peramban ditolak.
    expect(fn () => Livewire::actingAs($verifikator)->test(UserManagement::class)->set('isAdmin', true))
        ->toThrow(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

    expect(Hash::check('x', $this->pengguna->fresh()->password))->toBeFalse()
        ->and($this->pengguna->fresh()->wajib_ganti_sandi)->toBeFalse();
});

it('Admin tidak bisa mengambil alih atau membuat akun Super Admin', function () {
    $super = tap(User::factory()->create())->assignRole('Super Admin');
    $sandiLama = $super->password;

    $halaman = fn () => Livewire::actingAs($this->admin)->test(UserManagement::class);

    $halaman()->assertDontSeeHtml("buatSandiSementara({$super->id})");
    foreach (['buatSandiSementara', 'edit', 'delete'] as $aksi) {
        $halaman()->call($aksi, $super->id)->assertForbidden();
    }
    expect($super->fresh()->password)->toBe($sandiLama)
        ->and($super->fresh())->not->toBeNull();

    // Peran Super Admin tidak bisa diberikan Admin.
    $halaman()->call('edit', $this->pengguna->id)->set('role', 'Super Admin')->call('update')->assertHasErrors('role');
    expect($this->pengguna->fresh()->hasRole('Super Admin'))->toBeFalse();

    // Super Admin sendiri tetap bisa (isian lain pada akun uji tidak ikut dinilai di sini).
    Livewire::actingAs($super)->test(UserManagement::class)
        ->call('edit', $this->pengguna->id)->set('role', 'Super Admin')->call('update')->assertHasNoErrors('role');
});

it('urutan dan jumlah per halaman dari URL dibatasi ke pilihan yang ada', function () {
    Livewire::actingAs($this->admin)->withQueryParams(['sortField' => 'password', 'sortDirection' => 'x', 'perPage' => 100000])
        ->test(UserManagement::class)
        ->assertOk()
        ->assertSet('sortField', 'id')
        ->assertSet('sortDirection', 'asc')
        ->assertSet('perPage', 10);
});
