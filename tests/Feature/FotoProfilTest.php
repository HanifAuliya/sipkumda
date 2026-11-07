<?php

use App\Livewire\Profile\KartuProfil;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    Storage::fake('local');
    $this->pengguna = User::factory()->create();
});

/** JPEG 200x100 dengan belahan kiri merah, ditandai EXIF bahwa foto ini diputar (arah 6 = 90 derajat). */
function jpegMiring()
{
    $g = imagecreatetruecolor(200, 100);
    imagefilledrectangle($g, 0, 0, 99, 99, imagecolorallocate($g, 255, 0, 0));
    imagefilledrectangle($g, 100, 0, 199, 99, imagecolorallocate($g, 0, 0, 255));
    ob_start();
    imagejpeg($g, null, 95);
    $jpeg = ob_get_clean();

    $tiff = "II*\0" . pack('V', 8) . pack('v', 1) . pack('vvVvv', 0x0112, 3, 1, 6, 0) . pack('V', 0);
    $app1 = "Exif\0\0" . $tiff;
    $isi = substr($jpeg, 0, 2) . "\xFF\xE1" . pack('n', strlen($app1) + 2) . $app1 . substr($jpeg, 2);

    return UploadedFile::fake()->createWithContent('miring.jpg', $isi);
}

function warna(GdImage $g, int $x, int $y): string
{
    $c = imagecolorsforindex($g, imagecolorat($g, $x, $y));

    return $c['red'] > $c['blue'] ? 'merah' : 'biru';
}

it('foto belum tersimpan sebelum pratinjau disetujui, dan bisa dibatalkan', function () {
    Livewire::actingAs($this->pengguna)->test(KartuProfil::class)
        ->set('foto', UploadedFile::fake()->image('saya.jpg', 600, 400))
        ->assertHasNoErrors()
        ->assertSee('Simpan Foto')
        ->call('batalFoto')
        ->assertSet('foto', null);

    expect($this->pengguna->refresh()->foto_profil)->toBeNull();
    expect(Storage::disk('local')->allFiles('foto_profil'))->toBe([]);
});

it('menyimpan foto sebagai JPEG persegi 512 piksel dan membuang foto lama', function () {
    $komponen = Livewire::actingAs($this->pengguna)->test(KartuProfil::class);

    $komponen->set('foto', UploadedFile::fake()->image('a.png', 900, 300))->call('simpanFoto')->assertHasNoErrors();
    $lama = $this->pengguna->refresh()->foto_profil;

    [$lebar, $tinggi, $jenis] = getimagesizefromstring(Storage::disk('local')->get($lama));
    expect([$lebar, $tinggi, $jenis])->toBe([512, 512, IMAGETYPE_JPEG]);

    $komponen->set('foto', UploadedFile::fake()->image('b.webp', 300, 300))->call('simpanFoto')
        ->assertHasNoErrors()
        ->assertDispatched('foto-berubah')
        ->assertSet('foto', null);

    $baru = $this->pengguna->refresh()->foto_profil;
    expect($baru)->not->toBe($lama);
    Storage::disk('local')->assertMissing($lama);
    Storage::disk('local')->assertExists($baru);
});

it('menegakkan foto HP yang miring dan membuang data EXIF', function () {
    Livewire::actingAs($this->pengguna)->test(KartuProfil::class)
        ->set('foto', jpegMiring())
        ->call('simpanFoto')
        ->assertHasNoErrors();

    $isi = Storage::disk('local')->get($this->pengguna->refresh()->foto_profil);
    $g = imagecreatefromstring($isi);

    // Belahan kiri yang merah kini berada di atas.
    expect(warna($g, 256, 80))->toBe('merah')
        ->and(warna($g, 256, 430))->toBe('biru')
        ->and(str_contains($isi, 'Exif'))->toBeFalse();
});

it('menolak file yang bukan gambar, terlalu berat, atau terlalu besar', function ($file) {
    Livewire::actingAs($this->pengguna)->test(KartuProfil::class)
        ->set('foto', $file)
        ->assertHasErrors('foto')
        ->assertSet('foto', null);

    expect($this->pengguna->refresh()->foto_profil)->toBeNull();
})->with([
    'pdf' => fn () => UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'),
    'svg berisi skrip' => fn () => UploadedFile::fake()->createWithContent('a.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
    'php menyamar gambar' => fn () => UploadedFile::fake()->createWithContent('a.jpg', '<?php echo 1;'),
    'lebih dari 5MB' => fn () => UploadedFile::fake()->image('a.jpg', 100, 100)->size(5121),
    'lebih dari 25 megapiksel' => fn () => UploadedFile::fake()->image('a.jpg', 6000, 5000),
]);

it('file JPEG rusak menjadi pesan, bukan halaman error, dan foto lama tetap ada', function () {
    $komponen = Livewire::actingAs($this->pengguna)->test(KartuProfil::class)
        ->set('foto', UploadedFile::fake()->image('a.jpg', 300, 300))->call('simpanFoto');
    $lama = $this->pengguna->refresh()->foto_profil;

    $utuh = UploadedFile::fake()->image('b.jpg', 300, 300)->getContent();

    $komponen->set('foto', UploadedFile::fake()->createWithContent('b.jpg', substr($utuh, 0, 200)))
        ->call('simpanFoto')
        ->assertHasErrors('foto')
        ->assertSet('foto', null);

    expect($this->pengguna->refresh()->foto_profil)->toBe($lama);
    Storage::disk('local')->assertExists($lama);

    // Kepala file masih utuh tetapi isinya terpotong: tidak boleh berujung halaman error.
    $komponen->set('foto', UploadedFile::fake()->createWithContent('c.jpg', substr($utuh, 0, (int) (strlen($utuh) * 0.6))))
        ->call('simpanFoto')
        ->assertSet('foto', null);
});

it('menghapus foto dan kembali ke gambar bawaan', function () {
    $komponen = Livewire::actingAs($this->pengguna)->test(KartuProfil::class)
        ->set('foto', UploadedFile::fake()->image('a.jpg', 300, 300))->call('simpanFoto');
    $path = $this->pengguna->refresh()->foto_profil;

    $komponen->call('hapusFoto')->assertSee('Foto profil')->assertDispatched('foto-berubah');

    Storage::disk('local')->assertMissing($path);
    expect($this->pengguna->refresh()->fotoUrl())->toContain('assets/img/theme/profile.png');
});

it('membuang file foto saat akun dihapus', function () {
    Livewire::actingAs($this->pengguna)->test(KartuProfil::class)
        ->set('foto', UploadedFile::fake()->image('a.jpg', 300, 300))->call('simpanFoto');
    $path = $this->pengguna->refresh()->foto_profil;

    $this->pengguna->delete();

    Storage::disk('local')->assertMissing($path);
});

describe('alamat foto', function () {
    it('hanya mengirim foto milik pengguna yang sedang masuk', function () {
        Storage::disk('local')->put('foto_profil/saya.jpg', 'foto-saya');
        Storage::disk('local')->put('foto_profil/lain.jpg', 'foto-lain');
        $this->pengguna->forceFill(['foto_profil' => 'foto_profil/saya.jpg'])->save();
        $lain = User::factory()->create();
        $lain->forceFill(['foto_profil' => 'foto_profil/lain.jpg'])->save();

        $respons = $this->actingAs($this->pengguna)->get(route('foto-profil', ['user' => $lain->id]));

        $respons->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        expect($respons->streamedContent())->toBe('foto-saya');
    });

    it('menolak pengunjung yang belum masuk', function () {
        $this->get(route('foto-profil'))->assertRedirect('/login');
    });

    it('tidak bisa dibuka lewat rute file privat', function () {
        Storage::disk('local')->put('foto_profil/x/saya.jpg', 'foto');

        $this->actingAs($this->pengguna)->get('/view-private/foto_profil/x/saya.jpg')->assertForbidden();
    });

    it('jatuh ke gambar bawaan bila filenya hilang', function () {
        $this->pengguna->forceFill(['foto_profil' => 'foto_profil/hilang.jpg'])->save();

        $this->actingAs($this->pengguna)->get(route('foto-profil'))->assertRedirectContains('assets/img/theme/profile.png');
    });
});

it('foto tampil di menu pengguna dan kartu profil', function () {
    $this->pengguna->forceFill(['foto_profil' => 'foto_profil/abcdefghijklmnop.jpg'])->save();

    $this->actingAs($this->pengguna)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get('/profile')
        ->assertOk()
        ->assertSee('foto-profil?v=abcdefghijkl', false);
});
