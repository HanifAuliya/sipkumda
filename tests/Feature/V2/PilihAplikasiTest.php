<?php

use App\Livewire\V2\DaftarNaskah;
use App\Livewire\V2\DetailNaskah;
use App\Livewire\V2\LayarKerja;
use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Support\BlokNaskah;
use App\Support\Navigation;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['Perangkat Daerah', 'Peneliti', 'Admin', 'Verifikator', 'Super Admin', 'Tamu'] as $peran) {
        Role::findOrCreate($peran);
    }

    $this->peran = fn (string $peran) => tap(User::factory()->create(['email_verified_at' => now()]))->assignRole($peran);
});

describe('pilih aplikasi', function () {
    it('halaman pilihan menawarkan kedua aplikasi', function () {
        $this->actingAs(($this->peran)('Admin'))->get(route('aplikasi.pilih'))
            ->assertOk()->assertSee('SIFKUMDA v2')->assertSee('Aplikasi saat ini');
    });

    it('menuju aplikasi yang dipilih dan mengingat pilihannya', function (string $pilihan, string $tujuan) {
        $this->actingAs(($this->peran)('Admin'))
            ->post(route('aplikasi.simpan'), ['aplikasi' => $pilihan])
            ->assertRedirect(route($tujuan))
            ->assertSessionHas(Navigation::SESI_APLIKASI, $pilihan);
    })->with([
        'lama' => [Navigation::LAMA, 'dashboard'],
        'v2' => [Navigation::V2, 'v2.beranda'],
    ]);

    it('menolak pilihan yang tidak dikenal', function () {
        $this->actingAs(($this->peran)('Admin'))
            ->post(route('aplikasi.simpan'), ['aplikasi' => 'lain'])
            ->assertSessionHasErrors('aplikasi');
    });

    it('harus login', function () {
        $this->get(route('aplikasi.pilih'))->assertRedirect(route('login'));
    });
});

describe('menu', function () {
    it('halaman v2 memakai kerangka lama dengan menu v2', function () {
        $this->actingAs(($this->peran)('Peneliti'))->get(route('v2.beranda'))
            ->assertOk()
            ->assertSee('Penelitian Rancangan')
            ->assertSee('Pengecekan Fasilitasi')
            ->assertDontSee('Upload Penelitian');
    });

    it('halaman bersama tetap memakai menu aplikasi yang dipilih', function () {
        $admin = ($this->peran)('Admin');

        $this->actingAs($admin)->withSession([Navigation::SESI_APLIKASI => Navigation::V2])
            ->get(route('user.management'))->assertOk()->assertSee('Pengaturan Naskah');

        $this->actingAs($admin)->withSession([Navigation::SESI_APLIKASI => Navigation::LAMA])
            ->get(route('user.management'))->assertOk()->assertDontSee('Pengaturan Naskah');
    });

    it('setiap peran hanya melihat menu pekerjaannya', function (string $peran, array $ada, array $tidak) {
        // Bukan Dashboard, karena dropdown filternya memuat nama semua tahap.
        $halaman = $this->actingAs(($this->peran)($peran))->get(route('v2.daftar', 'arsip'))->assertOk();

        foreach ($ada as $label) {
            $halaman->assertSee($label);
        }
        foreach ($tidak as $label) {
            $halaman->assertDontSee($label);
        }
    })->with([
        'pd' => ['Perangkat Daerah', ['Rancanganku', 'Fasilitasiku'], ['Pilih Peneliti', 'Persetujuan Berkas', 'Pengaturan Naskah']],
        'admin' => ['Admin', ['Persetujuan Berkas', 'Penandatanganan', 'Pengaturan Naskah'], ['Rancanganku', 'Pilih Peneliti', 'Persetujuan Fasilitasi']],
        'peneliti' => ['Peneliti', ['Penelitian Rancangan', 'Persetujuan Fasilitasi', 'Pengecekan Fasilitasi'], ['Rancanganku', 'Penandatanganan', 'Persetujuan Berkas']],
        'tamu' => ['Tamu', ['Dashboard', 'Profil', 'Penandatanganan'], ['Rancanganku', 'Daftar Rancangan', 'Daftar Fasilitasi', 'Persetujuan Fasilitasi', 'Pengaturan Naskah']],
        'verifikator' => ['Verifikator', ['Pilih Peneliti', 'Validasi Rancangan', 'Validasi Fasilitasi', 'Kelola TTD'], ['Rancanganku', 'Persetujuan Berkas']],
    ]);

    it('semua menu v2 bisa dibuka oleh perannya', function (string $peran) {
        $user = ($this->peran)($peran);
        $this->actingAs($user)->get(route('v2.beranda'))->assertOk();

        foreach (Navigation::sectionsV2() as $bagian) {
            foreach ($bagian['items'] as $item) {
                if (($item['route'] === 'v2.daftar' || str_starts_with($item['route'], 'v2.')) && (empty($item['roles']) || $user->hasAnyRole($item['roles']))) {
                    $this->get(route($item['route'], $item['params'] ?? []))->assertOk();
                }
            }
        }
    })->with(['Perangkat Daerah', 'Peneliti', 'Admin', 'Verifikator', 'Super Admin', 'Tamu']);

    it('daftar yang tidak dikenal menghasilkan 404', function () {
        $this->actingAs(($this->peran)('Admin'))->get('/v2/daftar/tidak-ada')->assertNotFound();
    });
});

describe('daftar naskah', function () {
    it('hanya memuat tahap menu itu dan hak lihat tetap berlaku', function () {
        $pd = ($this->peran)('Perangkat Daerah');
        $lain = ($this->peran)('Perangkat Daerah');
        $buat = fn (User $u, string $tahap, string $tentang) => Naskah::denganNomorTerkunci(fn () => Naskah::create([
            'jenis' => 'Peraturan Bupati', 'tentang' => $tentang, 'pemrakarsa_id' => $u->id, 'tahap' => $tahap,
            'nomor_nota' => uniqid(), 'tanggal_nota' => now(),
        ]));

        $buat($pd, 'penelitian', 'Rancangan Milik Saya');
        $buat($pd, 'selesai', 'Sudah Selesai Milik Saya');
        $buat($lain, 'penelitian', 'Milik Dinas Lain');

        $this->actingAs($pd)->get(route('v2.daftar', 'rancanganku'))
            ->assertSee('Rancangan Milik Saya')
            ->assertDontSee('Sudah Selesai Milik Saya')
            ->assertDontSee('Milik Dinas Lain');

        $this->get(route('v2.daftar', 'fasilitasiku'))->assertDontSee('Sudah Selesai Milik Saya');
        $this->get(route('v2.daftar', ['fasilitasiku', 'tab' => 'riwayat']))->assertSee('Sudah Selesai Milik Saya');
        $this->actingAs(($this->peran)('Admin'))->get(route('v2.daftar', 'rancangan'))
            ->assertSee('Rancangan Milik Saya')->assertSee('Milik Dinas Lain');
    });

    it('menu pekerjaan memisahkan yang menunggu dan riwayatnya', function () {
        $pd = ($this->peran)('Perangkat Daerah');
        $buat = fn (string $tahap, string $tentang) => Naskah::denganNomorTerkunci(fn () => Naskah::create([
            'jenis' => 'Peraturan Bupati', 'tentang' => $tentang, 'pemrakarsa_id' => $pd->id, 'tahap' => $tahap,
            'nomor_nota' => uniqid(), 'tanggal_nota' => now(),
        ]));
        $buat('persetujuan_berkas', 'Masih Menunggu');
        $buat('penelitian', 'Sudah Lewat');
        $buat('draf', 'Belum Sampai');
        $this->actingAs(($this->peran)('Admin'));

        Livewire::test(DaftarNaskah::class, ['kelompok' => 'persetujuan-berkas'])
            ->assertSee('Masih Menunggu')->assertDontSee('Sudah Lewat')->assertDontSee('Belum Sampai')
            ->assertViewHas('jumlah', ['menunggu' => 1, 'riwayat' => 1])
            ->call('pilihTab', 'riwayat')
            ->assertSee('Sudah Lewat')->assertDontSee('Masih Menunggu')->assertDontSee('Belum Sampai');
    });

    it('daftar tidak langsung membuka layar kerja, hanya tombol detail', function (string $kelompok) {
        $pd = ($this->peran)('Perangkat Daerah');
        $n = Naskah::denganNomorTerkunci(fn () => Naskah::create([
            'jenis' => 'Peraturan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $pd->id, 'tahap' => 'penelitian',
            'nomor_nota' => uniqid(), 'tanggal_nota' => now(),
        ]));

        $this->actingAs($pd)->get(route('v2.daftar', $kelompok))
            ->assertOk()->assertSee($n->nomor)->assertSee('bukaDetailNaskah', false)
            ->assertDontSee(route('v2.naskah.kerja', $n));
    })->with(['rancanganku', 'rancangan']);
});

describe('detail naskah', function () {
    beforeEach(function () {
        $this->pemilik = ($this->peran)('Perangkat Daerah');
        $this->naskah = Naskah::denganNomorTerkunci(fn () => Naskah::create([
            'jenis' => 'Peraturan Bupati', 'tentang' => 'Tentang Uji', 'pemrakarsa_id' => $this->pemilik->id,
            'nomor_nota' => 'NOTA-UJI', 'tanggal_nota' => now(),
        ]));
    });

    it('membuka modal dengan tombol kerjakan saat giliran pengguna', function () {
        $this->actingAs($this->pemilik);

        Livewire::test(DetailNaskah::class)
            ->call('buka', $this->naskah->slug)
            ->assertDispatched('openModal', 'modalDetailNaskah')
            ->assertSee('Tentang Uji')->assertSee('NOTA-UJI')
            ->assertSee('Kerjakan Naskah')->assertDontSee('Lihat Matriks')
            ->assertSee(route('v2.naskah.kerja', $this->naskah));
    });

    it('hanya lihat matriks saat bukan giliran pengguna', function () {
        $this->actingAs(($this->peran)('Admin'));

        Livewire::test(DetailNaskah::class)->call('buka', $this->naskah->slug)
            ->assertSee('Lihat Matriks')->assertDontSee('Kerjakan Naskah');
    });

    it('menolak pengguna yang tidak berhak melihat naskah itu', function () {
        $this->actingAs(($this->peran)('Perangkat Daerah'));

        Livewire::test(DetailNaskah::class)->call('buka', $this->naskah->slug)->assertForbidden();
    });

    it('slug yang tidak ada menghasilkan 404', function () {
        $this->actingAs($this->pemilik);

        Livewire::test(DetailNaskah::class)->call('buka', 'tidak-ada')->assertNotFound();
    });

    it('tutup mengosongkan isi modal', function () {
        $this->actingAs($this->pemilik);

        Livewire::test(DetailNaskah::class)->call('buka', $this->naskah->slug)
            ->call('tutup')->assertSet('naskahId', null)->assertDontSee('Tentang Uji');
    });

    it('modal tersedia di halaman v2 dan halaman bersama, tidak di aplikasi lama', function () {
        $admin = ($this->peran)('Admin');

        $this->actingAs($admin)->get(route('v2.beranda'))->assertSeeLivewire(DetailNaskah::class);
        $this->withSession([Navigation::SESI_APLIKASI => Navigation::V2])->get(route('user.management'))->assertSeeLivewire(DetailNaskah::class);
        $this->withSession([Navigation::SESI_APLIKASI => Navigation::LAMA])->get(route('user.management'))->assertDontSeeLivewire(DetailNaskah::class);
    });
});

describe('layar kerja', function () {
    it('draf dibuka dari bagian pertama yang belum terisi, lampiran kosong tidak dihitung', function () {
        $pd = ($this->peran)('Perangkat Daerah');
        $n = Naskah::denganNomorTerkunci(fn () => Naskah::create([
            'jenis' => 'Keputusan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $pd->id,
            'nomor_nota' => uniqid(), 'tanggal_nota' => now(),
        ]));
        $alur = app(AlurNaskah::class);
        $baris = fn (array $kosong) => collect(['judul', 'menimbang', 'mengingat', 'diktum', 'penutup', 'lampiran'])
            ->map(fn (string $b) => ['bagian' => $b, 'label' => null, 'teks' => in_array($b, $kosong, true) ? '' : 'isi'])->all();

        $alur->isiBaris($n, $baris(['lampiran']));
        $this->actingAs($pd);
        Livewire::test(LayarKerja::class, ['naskah' => $n])->assertSet('blok', 'judul');

        $n->baris()->delete();
        $alur->isiBaris($n, $baris(['mengingat', 'lampiran']));
        Livewire::test(LayarKerja::class, ['naskah' => $n])->assertSet('blok', 'mengingat');
    });

    it('tombol kembali menuju daftar asal, bukan alamat lain', function () {
        $pd = ($this->peran)('Perangkat Daerah');
        $n = Naskah::denganNomorTerkunci(fn () => Naskah::create([
            'jenis' => 'Keputusan Bupati', 'tentang' => 'Uji', 'pemrakarsa_id' => $pd->id,
            'nomor_nota' => uniqid(), 'tanggal_nota' => now(),
        ]));
        $daftar = route('v2.daftar', 'rancanganku');

        $this->actingAs($pd)->withHeader('referer', $daftar)->get(route('v2.naskah.kerja', $n))->assertSee('href="'.$daftar.'"', false);
        $this->withHeader('referer', 'https://contoh.test/jahat')->get(route('v2.naskah.kerja', $n))->assertDontSee('contoh.test');
    });
});

describe('pembagian bagian naskah', function () {
    function barisContoh(string $bagian, ?string $label, int $id): NaskahBaris
    {
        return (new NaskahBaris)->forceFill(['id' => $id, 'bagian' => $bagian, 'label' => $label, 'rumusan_awal' => $label === 'BAB I' ? "KETENTUAN UMUM\nlanjutan" : 'isi']);
    }

    it('batang tubuh dipecah per BAB, bagian lain tetap satu', function () {
        $n = new Naskah(['jenis' => 'Peraturan Bupati']);
        $blok = BlokNaskah::susun($n, collect([
            barisContoh('menimbang', null, 1),
            barisContoh('menimbang', null, 2),
            barisContoh('batang_tubuh', 'BAB I', 3),
            barisContoh('batang_tubuh', 'Pasal 1', 4),
            barisContoh('batang_tubuh', 'BAB II', 5),
            barisContoh('batang_tubuh', 'Pasal 2', 6),
            barisContoh('penutup', null, 7),
        ]));

        expect(collect($blok)->pluck('nama')->all())->toBe(['Konsiderans Menimbang', 'BAB I', 'BAB II', 'Penutup'])
            ->and($blok[1]['sub'])->toBe('KETENTUAN UMUM')
            ->and($blok[1]['ids'])->toBe([3, 4])
            ->and($blok[0]['anak'])->toBeFalse()
            ->and($blok[1]['anak'])->toBeTrue();
    });

    it('naskah tanpa BAB dibagi per rentang pasal, tanpa baris yang hilang', function () {
        $n = new Naskah(['jenis' => 'Peraturan Bupati']);
        $baris = collect(range(1, 60))->map(fn (int $i) => barisContoh('batang_tubuh', "Pasal {$i}", $i));
        $blok = BlokNaskah::susun($n, $baris);

        expect($blok)->toHaveCount(3)
            ->and($blok[0]['nama'])->toBe('Pasal 1 – Pasal 25')
            ->and($blok[2]['nama'])->toBe('Pasal 51 – Pasal 60')
            ->and(collect($blok)->pluck('ids')->flatten()->all())->toBe(range(1, 60))
            ->and(collect($blok)->pluck('kunci')->unique())->toHaveCount(3);
    });

    it('BAB yang panjang dan bagian lain yang panjang dipotong dengan kunci berbeda', function () {
        $n = new Naskah(['jenis' => 'Peraturan Bupati']);
        $bab = collect(range(1, 30))->map(fn (int $i) => barisContoh('batang_tubuh', $i === 1 ? 'BAB I' : "Pasal {$i}", $i));
        $ingat = collect(range(31, 60))->map(fn (int $i) => barisContoh('mengingat', null, $i));
        $blok = BlokNaskah::susun($n, $ingat->concat($bab));

        expect(collect($blok)->pluck('nama')->all())->toBe(['Dasar Hukum Mengingat', 'Dasar Hukum Mengingat (lanjutan)', 'BAB I', 'BAB I (lanjutan)'])
            ->and(collect($blok)->pluck('kunci')->unique())->toHaveCount(4);
    });

    it('Keputusan Bupati tidak punya batang tubuh', function () {
        $n = new Naskah(['jenis' => 'Keputusan Bupati']);
        $blok = BlokNaskah::susun($n, collect([barisContoh('diktum', null, 1), barisContoh('batang_tubuh', 'Pasal 1', 2)]));

        expect(collect($blok)->pluck('bagian')->all())->toBe(['diktum']);
    });
});
