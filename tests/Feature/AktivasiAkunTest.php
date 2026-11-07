<?php

use App\Http\Controllers\Auth\AktivasiAkunController;
use App\Livewire\UserManagement;
use App\Mail\AktivasiAkun;
use App\Models\PerangkatDaerah;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->baru = User::factory()->unverified()->create(['password' => Hash::make('Acak#Sementara1')]);
    $this->tautan = AktivasiAkunController::tautan($this->baru);
});

function isiFormPengguna($komponen, array $ubah = [])
{
    $data = $ubah + [
        'nama_user' => 'Pegawai Baru',
        'NIP' => '199001012020011001',
        'email' => 'pegawai.baru@gmail.com',
        'role' => 'Perangkat Daerah',
        'perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id,
    ];

    foreach ($data as $kolom => $nilai) {
        $komponen->set($kolom, $nilai);
    }

    return $komponen;
}

describe('tambah pengguna', function () {
    it('mengirim tautan aktivasi tanpa mencantumkan password', function () {
        Mail::fake();

        isiFormPengguna(Livewire::actingAs($this->admin)->test(UserManagement::class))
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('swal:modal', fn ($nama, $data) => $data[0]['type'] === 'success');

        $user = User::where('NIP', '199001012020011001')->first();
        expect($user->hasRole('Perangkat Daerah'))->toBeTrue()
            ->and($user->email_verified_at)->toBeNull();

        Mail::assertSent(AktivasiAkun::class, function (AktivasiAkun $mail) use ($user) {
            $html = $mail->render();

            return $mail->hasTo($user->email)
                && str_contains($html, '/aktivasi-akun/' . $user->id . '/')
                && str_contains($html, $user->NIP)
                && ! str_contains(strtolower($html), 'password sementara');
        });
    });

    it('tetap menyimpan akun dan memberi tahu admin bila email gagal terkirim', function () {
        Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP mati'));

        isiFormPengguna(Livewire::actingAs($this->admin)->test(UserManagement::class))
            ->call('store')
            ->assertHasNoErrors()
            ->assertDispatched('swal:modal', fn ($nama, $data) => $data[0]['type'] === 'warning' && str_contains($data[0]['message'], 'Kirim Ulang Aktivasi'));

        expect(User::where('NIP', '199001012020011001')->exists())->toBeTrue();
    });

    it('menampilkan tombol kirim ulang hanya untuk akun yang belum aktif', function () {
        $aktif = User::factory()->create();

        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->assertSeeHtml("kirimUlangAktivasi({$this->baru->id})")
            ->assertDontSeeHtml("kirimUlangAktivasi({$aktif->id})");
    });

    it('mengirim ulang tautan untuk akun yang belum aktif', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('kirimUlangAktivasi', $this->baru->id)
            ->assertDispatched('swal:toast');

        Mail::assertSent(AktivasiAkun::class, fn ($mail) => $mail->hasTo($this->baru->email));
    });

    it('tidak mengirim aktivasi untuk akun yang sudah aktif', function () {
        Mail::fake();

        Livewire::actingAs($this->admin)->test(UserManagement::class)
            ->call('kirimUlangAktivasi', User::factory()->create()->id);

        Mail::assertNothingSent();
    });

    it('menolak kirim ulang dari selain admin', function () {
        Mail::fake();
        $verifikator = tap(User::factory()->create())->assignRole('Verifikator');

        Livewire::actingAs($verifikator)->test(UserManagement::class)
            ->call('kirimUlangAktivasi', $this->baru->id)
            ->assertForbidden();

        Mail::assertNothingSent();
    });
});

describe('halaman aktivasi', function () {
    it('membuka form dan mengaktifkan akun', function () {
        $this->get($this->tautan)->assertOk()->assertSee('Aktifkan Akun')->assertSee($this->baru->NIP);

        $this->post($this->tautan, ['password' => 'Baru#12345', 'password_confirmation' => 'Baru#12345'])
            ->assertRedirect(route('login'));

        $this->baru->refresh();
        expect(Hash::check('Baru#12345', $this->baru->password))->toBeTrue()
            ->and($this->baru->email_verified_at)->not->toBeNull();
    });

    it('hanya bisa dipakai sekali', function () {
        $this->post($this->tautan, ['password' => 'Baru#12345', 'password_confirmation' => 'Baru#12345'])->assertRedirect();

        $this->get($this->tautan)->assertForbidden()->assertSee('Tautan aktivasi sudah tidak berlaku');
        $this->post($this->tautan, ['password' => 'Lagi#12345', 'password_confirmation' => 'Lagi#12345'])->assertForbidden();

        expect(Hash::check('Baru#12345', $this->baru->refresh()->password))->toBeTrue();
    });

    it('menolak tautan kedaluwarsa', function () {
        $this->travel(AktivasiAkunController::BERLAKU_HARI + 1)->days();

        $this->get($this->tautan)->assertForbidden();
    });

    it('menolak tautan yang diubah untuk akun lain', function () {
        $lain = User::factory()->create(['password' => Hash::make('Lama#12345')]);
        $palsu = str_replace("/aktivasi-akun/{$this->baru->id}/", "/aktivasi-akun/{$lain->id}/", $this->tautan);

        $this->post($palsu, ['password' => 'Baru#12345', 'password_confirmation' => 'Baru#12345'])->assertForbidden();

        expect(Hash::check('Lama#12345', $lain->refresh()->password))->toBeTrue();
    });

    it('menolak tautan tanpa tanda tangan', function () {
        $this->get(strtok($this->tautan, '?'))->assertForbidden();
    });

    it('menolak password yang tidak cocok tanpa mengubah akun', function () {
        $this->from($this->tautan)
            ->post($this->tautan, ['password' => 'Baru#12345', 'password_confirmation' => 'Beda#12345'])
            ->assertSessionHasErrors('password');

        expect(Hash::check('Acak#Sementara1', $this->baru->refresh()->password))->toBeTrue()
            ->and($this->baru->email_verified_at)->toBeNull();
    });
});
