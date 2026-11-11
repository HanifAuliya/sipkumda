<?php

use App\Livewire\Profile\UpdatePassword;
use App\Livewire\Profile\UpdateProfileInformation;
use App\Models\PerangkatDaerah;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;

beforeEach(function () {
    $this->pengguna = User::factory()->create(['password' => Hash::make('Lama#12345')]);
});

it('halaman profil langsung terbuka tanpa konfirmasi password', function () {
    $this->actingAs($this->pengguna)->get('/profile')->assertOk();
});

it('mengganti email meminta password saat ini, mengganti nama tidak', function () {
    $emailLama = $this->pengguna->email;

    Livewire::actingAs($this->pengguna)
        ->test(UpdateProfileInformation::class)
        ->call('mulaiUbah')
        ->set('email', 'baru@contoh.go.id')
        ->call('updateProfile')
        ->assertHasErrors(['sandiSekarang' => 'required'])
        ->set('sandiSekarang', 'salah')
        ->call('updateProfile')
        ->assertHasErrors(['sandiSekarang' => 'current_password']);

    expect($this->pengguna->refresh()->email)->toBe($emailLama);

    Livewire::actingAs($this->pengguna)
        ->test(UpdateProfileInformation::class)
        ->call('mulaiUbah')
        ->set('email', 'baru@contoh.go.id')
        ->set('sandiSekarang', 'Lama#12345')
        ->call('updateProfile')
        ->assertHasNoErrors()
        ->assertSet('sandiSekarang', '');

    expect($this->pengguna->refresh()->email)->toBe('baru@contoh.go.id');
});

// Sama seperti NIP (lihat KeamananTest): perangkat daerah hanya diubah oleh admin.
it('perangkat daerah tidak bisa diubah sendiri dari peramban', function () {
    Livewire::actingAs($this->pengguna)
        ->test(UpdateProfileInformation::class)
        ->set('perangkat_daerah_id', PerangkatDaerah::factory()->create()->id);
})->throws(CannotUpdateLockedPropertyException::class);

it('mengganti password bila password lama benar', function () {
    Livewire::actingAs($this->pengguna)
        ->test(UpdatePassword::class)
        ->set('current_password', 'Lama#12345')
        ->set('password', 'Baru#12345')
        ->set('password_confirmation', 'Baru#12345')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('Baru#12345', $this->pengguna->refresh()->password))->toBeTrue();
});

it('menolak penggantian bila password lama salah', function () {
    Livewire::actingAs($this->pengguna)
        ->test(UpdatePassword::class)
        ->set('current_password', 'salah')
        ->set('password', 'Baru#12345')
        ->set('password_confirmation', 'Baru#12345')
        ->call('updatePassword')
        ->assertHasErrors('current_password');

    expect(Hash::check('Lama#12345', $this->pengguna->refresh()->password))->toBeTrue();
});

it('menampilkan data profil sebagai bacaan sampai tombol Ubah ditekan', function () {
    Livewire::actingAs($this->pengguna)
        ->test(UpdateProfileInformation::class)
        ->assertSee($this->pengguna->email)
        ->assertDontSeeHtml('wire:model="email"')
        ->call('mulaiUbah')
        ->assertSeeHtml('wire:model="email"');
});

it('membatalkan perubahan profil tanpa menyimpan', function () {
    $emailLama = $this->pengguna->email;

    Livewire::actingAs($this->pengguna)
        ->test(UpdateProfileInformation::class)
        ->call('mulaiUbah')
        ->set('email', 'bukan-email')
        ->call('updateProfile')
        ->assertHasErrors('email')
        ->assertSet('mengubah', true)
        ->call('batal')
        ->assertHasNoErrors()
        ->assertSet('mengubah', false)
        ->assertSet('email', $emailLama);

    expect($this->pengguna->refresh()->email)->toBe($emailLama);
});

it('kembali ke mode baca sesudah profil tersimpan', function () {
    $user = $this->pengguna;
    $user->update(['perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]);

    Livewire::actingAs($user)
        ->test(UpdateProfileInformation::class)
        ->call('mulaiUbah')
        ->set('nama_user', 'Nama Baru')
        ->call('updateProfile')
        ->assertHasNoErrors()
        ->assertSet('mengubah', false)
        ->assertSee('Nama Baru');

    expect($user->refresh()->nama_user)->toBe('Nama Baru');
});

it('menyembunyikan form password sampai diminta dan mengosongkannya saat batal', function () {
    Livewire::actingAs($this->pengguna)
        ->test(UpdatePassword::class)
        ->assertDontSeeHtml('wire:model="current_password"')
        ->call('mulaiUbah')
        ->assertSeeHtml('wire:model="current_password"')
        ->set('current_password', 'Lama#12345')
        ->call('batal')
        ->assertSet('mengubah', false)
        ->assertSet('current_password', null);
});

it('tidak ada pendaftaran mandiri', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['NIP' => '1', 'password' => 'x'])->assertNotFound();
});
