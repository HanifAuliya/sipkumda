<?php

use App\Livewire\Profile\UpdateProfileInformation;
use App\Livewire\V2\PengaturanWhatsApp;
use App\Models\Pengaturan;
use App\Models\PerangkatDaerah;
use App\Models\PesanWa;
use App\Models\User;
use App\Notifications\NaskahNotification;
use App\Support\PesanNotifikasi;
use App\Support\Wa\AntreanWa;
use App\Support\Wa\GagalKirimWa;
use App\Support\Wa\NomorWa;
use App\Support\Wa\PengirimGowa;
use App\Support\Wa\PengirimWa;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

/** Pengganti gateway untuk uji: bisa dibuat terputus atau menolak nomor tertentu. */
class PengirimPalsu implements PengirimWa
{
    public bool $masuk = true;

    public array $terkirim = [];

    public ?string $tolak = null;

    public function status(): array
    {
        return ['mode' => 'gowa', 'gateway' => true, 'masuk' => $this->masuk, 'nomor' => '6281100000000', 'galat' => $this->masuk ? null : 'Belum tersambung'];
    }

    public function qr(): ?array
    {
        return ['gambar' => 'data:image/png;base64,QRPALSU', 'durasi' => 30];
    }

    public function kirim(string $nomor, string $isi): void
    {
        if ($nomor === $this->tolak) {
            throw new GagalKirimWa('Nomor tidak terdaftar di WhatsApp');
        }

        $this->terkirim[] = [$nomor, $isi];
    }

    public function keluar(): void
    {
    }

    public function sambungUlang(): void
    {
    }
}

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->palsu = new PengirimPalsu();
    app()->instance(PengirimWa::class, $this->palsu);

    $this->penerima = User::factory()->create(['no_wa' => '6281234567890']);
});

function notifikasi(string $kategori, string $judul = 'Periksa kelengkapan berkas'): NaskahNotification
{
    return new NaskahNotification([
        'kategori' => $kategori, 'title' => $judul, 'message' => 'NPB-2026-009 · Dana Desa',
        'catatan' => null, 'pelaku' => 'Budi', 'tahap' => 'persetujuan_berkas', 'slug' => 'abc-123',
    ]);
}

describe('nomor WhatsApp', function () {
    it('dinormalkan ke 62xxx dari berbagai cara menulis', function (?string $masuk, ?string $hasil) {
        expect(NomorWa::normal($masuk))->toBe($hasil);
    })->with([
        ['081234567890', '6281234567890'],
        ['+62 812-3456-7890', '6281234567890'],
        ['812 3456 7890', '6281234567890'],
        ['6281234567890', '6281234567890'],
        ['0212345678', null],
        ['0812', null],
        ['', null],
        [null, null],
    ]);

    it('ditampilkan dalam bentuk lokal', function () {
        expect(NomorWa::tampil('6281234567890'))->toBe('0812-3456-7890')->and(NomorWa::tampil(null))->toBe('-');
    });
});

describe('antrean', function () {
    it('notifikasi yang perlu tindakan masuk antrean lalu terkirim, kabar biasa tidak', function () {
        $this->penerima->notify(notifikasi(PesanNotifikasi::TUGAS));
        $this->penerima->notify(notifikasi(PesanNotifikasi::KABAR, 'Naskah masuk tahap Penelitian'));

        expect(PesanWa::count())->toBe(1)
            ->and(PesanWa::first()->status)->toBe(PesanWa::TERKIRIM)
            ->and($this->palsu->terkirim)->toHaveCount(1);

        [$nomor, $isi] = $this->palsu->terkirim[0];
        expect($nomor)->toBe('6281234567890')
            ->and($isi)->toContain('Perlu tindakan Anda')
            ->toContain('*Periksa kelengkapan berkas*')
            ->toContain('NPB-2026-009 · Dana Desa')
            ->toContain('Oleh: Budi')
            ->toContain(route('v2.naskah.kerja', 'abc-123'));
    });

    it('cakupan "semua" ikut mengirim kabar; pengguna tanpa nomor atau yang mematikan WA tidak dikirimi', function () {
        Pengaturan::simpan(Pengaturan::WA_CAKUPAN, AntreanWa::CAKUPAN_SEMUA);
        $this->penerima->notify(notifikasi(PesanNotifikasi::KABAR));

        $tanpaNomor = User::factory()->create();
        $mati = User::factory()->create(['no_wa' => '6281299990000']);
        $mati->forceFill(['wa_aktif' => false])->save();
        $tanpaNomor->notify(notifikasi(PesanNotifikasi::TUGAS));
        $mati->notify(notifikasi(PesanNotifikasi::TUGAS));

        expect(PesanWa::pluck('user_id')->all())->toBe([$this->penerima->id]);
    });

    it('saat putus pesan menunggu; yang sudah dibuka di lonceng dilewati, sisanya terkirim setelah tersambung', function () {
        $this->palsu->masuk = false;

        $this->penerima->notify(notifikasi(PesanNotifikasi::TUGAS, 'Tugas satu'));
        $this->penerima->notify(notifikasi(PesanNotifikasi::KEMBALI, 'Tugas dua'));

        expect(PesanWa::where('status', PesanWa::MENUNGGU)->count())->toBe(2)
            ->and(Cache::has(AntreanWa::KUNCI_PUTUS))->toBeTrue();

        // Tugas satu sudah dibuka di aplikasi sebelum WhatsApp tersambung lagi.
        $this->penerima->notifications()->where('data->title', 'Tugas satu')->update(['read_at' => now()]);

        $this->palsu->masuk = true;
        expect(app(AntreanWa::class)->proses())->toBe(1);

        expect(PesanWa::where('status', PesanWa::DILEWATI)->value('galat'))->toBe('Sudah dibuka di aplikasi.')
            ->and($this->palsu->terkirim)->toHaveCount(1)
            ->and($this->palsu->terkirim[0][1])->toContain('Tugas dua')->toContain('Naskah dikembalikan')
            ->and(Cache::has(AntreanWa::KUNCI_PUTUS))->toBeFalse();
    });

    it('pesan yang terlalu lama tertunda tidak dikirim', function () {
        $this->palsu->masuk = false;
        $this->penerima->notify(notifikasi(PesanNotifikasi::TUGAS));
        PesanWa::query()->update(['created_at' => now()->subHours(49)]);

        $this->palsu->masuk = true;
        app(AntreanWa::class)->proses();

        expect(PesanWa::first()->status)->toBe(PesanWa::DILEWATI)->and($this->palsu->terkirim)->toBe([]);
    });

    it('galat selain putus dicoba tiga kali lalu gagal, dan bisa diulang dari halaman Admin', function () {
        $this->palsu->tolak = '6281234567890';
        $this->penerima->notify(notifikasi(PesanNotifikasi::TUGAS));

        expect(PesanWa::first())->percobaan->toBe(1)->status->toBe(PesanWa::MENUNGGU);

        app(AntreanWa::class)->proses();
        app(AntreanWa::class)->proses();

        $pesan = PesanWa::first();
        expect($pesan->status)->toBe(PesanWa::GAGAL)->and($pesan->galat)->toBe('Nomor tidak terdaftar di WhatsApp');

        $this->palsu->tolak = null;
        $admin = tap(User::factory()->create())->assignRole('Admin');
        Livewire::actingAs($admin)->test(PengaturanWhatsApp::class)->call('kirimUlang', $pesan->id);
        app(AntreanWa::class)->proses();

        expect($pesan->fresh()->status)->toBe(PesanWa::TERKIRIM);
    });

    it('Tamu mendapat tautan halaman pantau', function () {
        $tamu = tap(User::factory()->create(['no_wa' => '6281300000000']))->assignRole('Tamu');
        $tamu->notify(notifikasi(PesanNotifikasi::TUGAS, 'Catat paraf dan tanda tangan'));

        expect($this->palsu->terkirim[0][1])->toContain(route('v2.naskah.pantau', 'abc-123'));
    });

    it('Beranda Admin memberi peringatan bila pesan lama tertunda', function () {
        $admin = tap(User::factory()->create())->assignRole('Admin');
        $this->palsu->masuk = false;
        $this->penerima->notify(notifikasi(PesanNotifikasi::TUGAS));

        $this->actingAs($admin)->get(route('v2.beranda'))->assertDontSee('WhatsApp tertunda');

        PesanWa::query()->update(['created_at' => now()->subMinutes(11)]);
        $this->actingAs($admin)->get(route('v2.beranda'))->assertSee('WhatsApp tertunda');
    });
});

describe('GOWA', function () {
    beforeEach(function () {
        $this->gowa = new PengirimGowa('http://127.0.0.1:3000', 'sifkumda', 'rahasia');
    });

    it('membaca status dan nomor yang tersambung', function () {
        Http::fake(['127.0.0.1:3000/app/status' => Http::response(['code' => 'SUCCESS', 'results' => [
            'is_connected' => true, 'is_logged_in' => true, 'device_id' => 'x', 'jid' => '6281100000000:12@s.whatsapp.net',
        ]])]);

        expect($this->gowa->status())->toMatchArray(['gateway' => true, 'masuk' => true, 'nomor' => '6281100000000']);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Basic ' . base64_encode('sifkumda:rahasia')));
    });

    it('program mati terbaca sebagai gateway tidak tersedia', function () {
        Http::fake(fn () => throw new ConnectionException('Connection refused'));

        expect($this->gowa->status())->toMatchArray(['gateway' => false, 'masuk' => false]);
        expect(fn () => $this->gowa->kirim('6281234567890', 'x'))->toThrow(fn (GagalKirimWa $e) => expect($e->putus)->toBeTrue());
    });

    it('mengirim ke JID dan membedakan sambungan putus dari galat biasa', function () {
        Http::fake([
            '127.0.0.1:3000/send/message' => Http::sequence()
                ->push(['code' => 'SUCCESS', 'results' => ['message_id' => 'm1']])
                ->push(['code' => 'INTERNAL_SERVER_ERROR', 'message' => 'you are not loggin'], 500)
                ->push(['code' => 400, 'message' => 'phone: invalid'], 400),
        ]);

        $this->gowa->kirim('6281234567890', 'Halo');
        Http::assertSent(fn ($r) => $r->url() === 'http://127.0.0.1:3000/send/message' && $r['phone'] === '6281234567890@s.whatsapp.net' && $r['message'] === 'Halo');

        expect(fn () => $this->gowa->kirim('6281234567890', 'x'))->toThrow(fn (GagalKirimWa $e) => expect($e->putus)->toBeTrue());
        expect(fn () => $this->gowa->kirim('6281234567890', 'x'))->toThrow(fn (GagalKirimWa $e) => expect([$e->putus, $e->getMessage()])->toBe([false, 'phone: invalid']));
    });

    it('QR diambil lewat server lalu dijadikan data URI', function () {
        Http::fake([
            '127.0.0.1:3000/app/login' => Http::response(['code' => 'SUCCESS', 'results' => ['qr_duration' => 30, 'qr_link' => 'http://localhost:3000/statics/qrcode/scan-qr-1.png']]),
            '127.0.0.1:3000/statics/qrcode/scan-qr-1.png' => Http::response('PNGDATA', 200, ['Content-Type' => 'image/png']),
        ]);

        expect($this->gowa->qr())->toBe(['gambar' => 'data:image/png;base64,' . base64_encode('PNGDATA'), 'durasi' => 30]);

        // Halaman Admin diperbarui tiap 5 detik: QR yang sama dipakai sampai hampir kedaluwarsa.
        $this->gowa->qr();
        Http::assertSentCount(2);
    });

    it('membuat perangkat GOWA sendiri bila belum ada', function () {
        Http::fake([
            '127.0.0.1:3000/app/status' => Http::sequence()
                ->push(['code' => 'DEVICE_NOT_FOUND', 'message' => 'device not found'], 404)
                ->push(['code' => 'SUCCESS', 'results' => ['is_connected' => false, 'is_logged_in' => false, 'jid' => '']]),
            '127.0.0.1:3000/devices' => Http::response(['code' => 'SUCCESS', 'results' => ['id' => 'sifkumda']]),
        ]);

        expect($this->gowa->status())->toMatchArray(['gateway' => true, 'masuk' => false]);
        Http::assertSent(fn ($r) => $r->url() === 'http://127.0.0.1:3000/devices' && $r['device_id'] === 'sifkumda');
        Http::assertSent(fn ($r) => $r->hasHeader('X-Device-Id', 'sifkumda'));
    });
});

describe('halaman dan isian', function () {
    it('halaman WhatsApp hanya untuk Admin; menampilkan QR saat belum tersambung', function () {
        $pd = tap(User::factory()->create())->assignRole('Perangkat Daerah');
        $admin = tap(User::factory()->create(['no_wa' => '6281200000000']))->assignRole('Admin');

        $this->actingAs($pd)->get(route('v2.whatsapp'))->assertForbidden();
        $this->actingAs($admin)->get(route('v2.whatsapp'))->assertOk()->assertSee('Tersambung');

        $this->palsu->masuk = false;
        Livewire::actingAs($admin)->test(PengaturanWhatsApp::class)
            ->assertSee('Sambungkan nomor kantor')
            ->assertSeeHtml('data:image/png;base64,QRPALSU')
            ->assertSet('nomorUji', '6281200000000')
            ->set('cakupan', AntreanWa::CAKUPAN_SEMUA);

        expect(AntreanWa::cakupan())->toBe(AntreanWa::CAKUPAN_SEMUA);
    });

    it('pesan uji langsung dikirim, nomor tidak sah ditolak', function () {
        $admin = tap(User::factory()->create())->assignRole('Admin');

        Livewire::actingAs($admin)->test(PengaturanWhatsApp::class)
            ->set('nomorUji', '12345')
            ->call('kirimUji')
            ->assertHasErrors('nomorUji')
            ->set('nomorUji', '0812-3456-7890')
            ->call('kirimUji')
            ->assertHasNoErrors();

        expect($this->palsu->terkirim[0][0])->toBe('6281234567890')->and($this->palsu->terkirim[0][1])->toContain('Pesan uji');
    });

    it('pengguna mengisi nomor dan mematikan WA dari Profil', function () {
        $pd = PerangkatDaerah::create(['nama_perangkat_daerah' => 'Dinas Uji']);
        $user = User::factory()->create(['perangkat_daerah_id' => $pd->id]);

        Livewire::actingAs($user)->test(UpdateProfileInformation::class)
            ->set('no_wa', '0812 3456')
            ->call('updateProfile')
            ->assertHasErrors('no_wa')
            ->set('no_wa', '+62 813-1111-2222')
            ->set('wa_aktif', false)
            ->call('updateProfile')
            ->assertHasNoErrors()
            ->assertSet('no_wa', '0813-1111-2222');

        expect($user->fresh())->no_wa->toBe('6281311112222')->wa_aktif->toBeFalse();
    });
});
