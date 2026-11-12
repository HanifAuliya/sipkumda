<?php

use App\Exports\DokumentasiExport;
use App\Livewire\Profile\UpdateProfileInformation;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function akun(string $role): User
{
    return tap(User::factory()->create())->assignRole($role);
}

describe('file privat', function () {
    beforeEach(function () {
        $this->pemilik = akun('Perangkat Daerah');
        Storage::disk('local')->put('dokumentasi/file_produk_hukum/perbup.pdf', '%PDF');
        Storage::disk('local')->put('tanda_tangan/ttd/ttd.png', 'png');
        Storage::disk('local')->put('rancangan/rancangan/berkas.docx', 'isi');
    });

    it('produk hukum bisa dibuka pengguna yang login', function () {
        $this->actingAs($this->pemilik)->get('/view-private/dokumentasi/file_produk_hukum/perbup.pdf')->assertOk();
    });

    it('folder alur lama tidak lagi bisa dibuka', function () {
        $this->actingAs(akun('Admin'))->get('/view-private/rancangan/rancangan/berkas.docx')->assertForbidden();
    });

    it('mengarahkan pengunjung yang belum login ke halaman login', function () {
        $this->get('/view-private/dokumentasi/file_produk_hukum/perbup.pdf')->assertRedirect('/login');
    });

    it('membatasi gambar tanda tangan untuk pengelola nota dinas', function () {
        $this->actingAs($this->pemilik)->get('/view-private/tanda_tangan/ttd/ttd.png')->assertForbidden();
        $this->actingAs(akun('Admin'))->get('/view-private/tanda_tangan/ttd/ttd.png')->assertOk();
    });

    it('menolak folder yang tidak dikenal', function () {
        Storage::disk('local')->put('public/templates/rahasia.txt', 'x');
        $this->actingAs(akun('Admin'))->get('/view-private/public/templates/rahasia.txt')->assertForbidden();
        $this->actingAs(akun('Admin'))->get('/view-private/livewire-tmp/x/rahasia.txt')->assertNotFound();
    });
});

describe('login', function () {
    it('menolak NIP berbentuk array', function () {
        $this->post('/login', ['NIP' => ['a', 'b'], 'password' => 'password'])->assertSessionHasErrors('NIP');
        $this->assertGuest();
    });

    it('mengunci IP setelah terlalu banyak percobaan gagal ke banyak NIP', function () {
        foreach (range(1, 20) as $i) {
            $this->post('/login', ['NIP' => "nip{$i}", 'password' => 'salah']);
        }

        $user = akun('Admin');
        $this->post('/login', ['NIP' => $user->NIP, 'password' => 'password'])->assertSessionHasErrors('NIP');
        $this->assertGuest();
    });
});

describe('aksi livewire', function () {
    it('tidak bisa mengubah NIP sendiri dari form profil', function () {
        Livewire::actingAs(akun('Perangkat Daerah'))
            ->test(UpdateProfileInformation::class)
            ->set('NIP', 'nip-orang-lain');
    })->throws(CannotUpdateLockedPropertyException::class);

});

it('menyimpan teks berawalan = di ekspor Excel sebagai teks, bukan rumus', function () {
    $sheet = (new Spreadsheet())->getActiveSheet();
    $export = new DokumentasiExport('', '', '');

    $export->bindValue($sheet->getCell('A1'), '=cmd|calc');
    $export->bindValue($sheet->getCell('A2'), '=HYPERLINK("' . url('/view-private/x/y/z.pdf') . '", "Lihat")');

    expect($sheet->getCell('A1')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($sheet->getCell('A2')->getDataType())->toBe(DataType::TYPE_FORMULA);
});

it('mengirim header keamanan', function () {
    $this->get('/login')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('X-Content-Type-Options', 'nosniff');
});
