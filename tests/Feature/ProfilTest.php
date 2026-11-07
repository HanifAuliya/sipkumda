<?php

use App\Livewire\Profile\UpdatePassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

beforeEach(function () {
    $this->pengguna = User::factory()->create(['password' => Hash::make('Lama#12345')]);
});

it('halaman profil meminta konfirmasi password dulu', function () {
    $this->actingAs($this->pengguna)->get('/profile')->assertRedirect('/confirm-password');
});

it('halaman profil terbuka sesudah password dikonfirmasi', function () {
    $this->actingAs($this->pengguna)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/profile')
        ->assertOk();
});

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

it('tidak ada pendaftaran mandiri', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', ['NIP' => '1', 'password' => 'x'])->assertNotFound();
});
