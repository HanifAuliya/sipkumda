<?php

use App\Enums\TahapNaskah as T;
use App\Livewire\NotificationDropdown;
use App\Livewire\V2\LayarKerja;
use App\Livewire\V2\Lonceng;
use App\Livewire\V2\Notifikasi;
use App\Models\Naskah;
use App\Models\PerangkatDaerah;
use App\Models\User;
use App\Notifications\NaskahNotification;
use App\Services\AlurNaskah;
use Database\Seeders\RoleSeeder;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->alur = app(AlurNaskah::class);
    $this->pd = tap(User::factory()->create(['perangkat_daerah_id' => PerangkatDaerah::factory()->create()->id]))->assignRole('Perangkat Daerah');
    $this->admin = tap(User::factory()->create())->assignRole('Admin');
    $this->admin2 = tap(User::factory()->create())->assignRole('Admin');
    $this->verifikator = tap(User::factory()->create())->assignRole('Verifikator');
    $this->peneliti = tap(User::factory()->create())->assignRole('Peneliti');

    $this->naskah = fn (T $tahap) => tap(
        Naskah::create(['jenis' => 'Peraturan Bupati', 'tentang' => 'Uji Notifikasi', 'pemrakarsa_id' => $this->pd->id, 'peneliti_id' => $this->peneliti->id]),
        fn (Naskah $n) => $n->forceFill(['tahap' => $tahap])->save(),
    );

    $this->terakhir = fn (User $u) => $u->notifikasiNaskah()->first()?->data;
});

describe('isi dan penerima', function () {
    it('pemegang giliran mendapat tugas yang jelas, pemrakarsa mendapat kabar, pelaku tidak mendapat apa-apa', function () {
        $n = ($this->naskah)(T::PersetujuanBerkas);

        $this->alur->jalankan($n, $this->admin, 'setujui_berkas');

        expect(($this->terakhir)($this->verifikator))->toMatchArray([
            'kategori' => 'tugas',
            'title' => 'Pilih peneliti untuk rancangan ini',
            'message' => "{$n->nomor} · Uji Notifikasi",
            'pelaku' => $this->admin->nama_user,
            'slug' => $n->slug,
        ])
            ->and(($this->terakhir)($this->pd))->toMatchArray(['kategori' => 'kabar', 'title' => 'Naskah masuk tahap Menunggu Peneliti'])
            ->and($this->admin->notifikasiNaskah()->count())->toBe(0);
    });

    it('naskah yang dikembalikan menyebut alasan dan catatannya', function () {
        $n = ($this->naskah)(T::PersetujuanBerkas);

        $this->alur->jalankan($n, $this->admin, 'tolak_berkas', 'Nota dinas belum bertanda tangan.');

        expect(($this->terakhir)($this->pd))->toMatchArray([
            'kategori' => 'kembali',
            'title' => 'Berkas dikembalikan, perlu dilengkapi',
            'catatan' => 'Nota dinas belum bertanda tangan.',
        ]);
    });

    it('semua peran yang boleh menerbitkan nota dinas ikut diberi tahu', function () {
        $n = ($this->naskah)(T::ValidasiFasilitasi);

        $lain = tap(User::factory()->create())->assignRole('Verifikator');
        $this->alur->jalankan($n, $this->verifikator, 'finalkan');

        foreach ([$this->admin, $this->admin2, $lain] as $penerima) {
            expect(($this->terakhir)($penerima))->toMatchArray(['kategori' => 'tugas', 'title' => 'Terbitkan nota dinas']);
        }
    });

    it('tugas lama tertutup saat tahap berpindah, juga bagi rekan yang tidak mengerjakannya', function () {
        $n = ($this->naskah)(T::Draf);
        $n->forceFill(['tahap' => T::PersetujuanBerkas])->save();
        $this->admin2->notify(new NaskahNotification(['kategori' => 'tugas', 'title' => 'Periksa kelengkapan berkas', 'message' => '-', 'slug' => $n->slug]));
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'Kabar', 'message' => '-', 'slug' => $n->slug]));

        $this->alur->jalankan($n, $this->admin, 'setujui_berkas');

        expect($this->admin2->notifikasiNaskah()->whereNull('read_at')->count())->toBe(0)
            ->and($this->pd->notifikasiNaskah()->where('data->title', 'Kabar')->whereNull('read_at')->exists())->toBeTrue();
    });
});

describe('membuka notifikasi', function () {
    it('giliran sendiri langsung ke layar kerja', function () {
        $n = ($this->naskah)(T::PerbaikanPd);
        $this->pd->notify(new NaskahNotification(['kategori' => 'kembali', 'title' => 'x', 'message' => '-', 'slug' => $n->slug]));
        $id = $this->pd->notifikasiNaskah()->first()->id;

        Livewire::actingAs($this->pd)->test(Lonceng::class)
            ->call('buka', $id)
            ->assertRedirect(route('v2.naskah.kerja', $n));

        expect(DatabaseNotification::find($id)->read_at)->not->toBeNull();
    });

    it('bukan giliran sendiri membuka detail naskah', function () {
        $n = ($this->naskah)(T::Penelitian);
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'x', 'message' => '-', 'slug' => $n->slug]));

        Livewire::actingAs($this->pd)->test(Lonceng::class)
            ->call('buka', $this->pd->notifikasiNaskah()->first()->id)
            ->assertNoRedirect()
            ->assertDispatched('bukaDetailNaskah', slug: $n->slug);
    });

    it('naskah yang sudah tidak ada memberi pesan, bukan 404', function () {
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'x', 'message' => '-', 'slug' => 'sudah-tidak-ada']));

        Livewire::actingAs($this->pd)->test(Lonceng::class)
            ->call('buka', $this->pd->notifikasiNaskah()->first()->id)
            ->assertOk()
            ->assertNotDispatched('bukaDetailNaskah')
            ->assertDispatched('swal:toast');
    });

    it('notifikasi milik orang lain tidak bisa dibuka', function () {
        $n = ($this->naskah)(T::Penelitian);
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'x', 'message' => '-', 'slug' => $n->slug]));

        Livewire::actingAs($this->admin)->test(Lonceng::class)
            ->call('buka', $this->pd->notifikasiNaskah()->first()->id)
            ->assertNotFound();
    });
});

describe('keadaan belum dibaca', function () {
    it('membuka layar kerja menandai notifikasi naskah itu saja', function () {
        $n = ($this->naskah)(T::Draf);
        $lain = ($this->naskah)(T::Penelitian);
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'Ini', 'message' => '-', 'slug' => $n->slug]));
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'Lain', 'message' => '-', 'slug' => $lain->slug]));

        Livewire::actingAs($this->pd)->test(LayarKerja::class, ['naskah' => $n]);

        expect($this->pd->notifikasiNaskah()->whereNull('read_at')->pluck('data')->pluck('title')->all())->toBe(['Lain']);
    });

    it('membuang draf ikut menghapus notifikasinya', function () {
        $n = ($this->naskah)(T::Draf);
        $this->admin->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'x', 'message' => '-', 'slug' => $n->slug]));

        $this->alur->buangDraf($n, $this->pd);

        expect($this->admin->notifikasiNaskah()->count())->toBe(0);
    });

    it('lonceng v2 dan lonceng lama tidak saling mencampur notifikasi', function () {
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'Kabar naskah v2', 'message' => '-']));
        DatabaseNotification::create([
            'id' => (string) str()->uuid(), 'type' => 'App\Notifications\ValidationResultNotification',
            'notifiable_type' => User::class, 'notifiable_id' => $this->pd->id, 'data' => ['title' => 'Kabar alur lama', 'message' => '-'],
        ]);

        Livewire::actingAs($this->pd)->test(Lonceng::class)
            ->assertSee('Kabar naskah v2')->assertDontSee('Kabar alur lama')
            ->assertViewHas('belumDibaca', 1);

        Livewire::actingAs($this->pd)->test(NotificationDropdown::class)
            ->assertSee('Kabar alur lama')->assertDontSee('Kabar naskah v2')
            ->assertViewHas('unreadCount', 1);
    });

    it('notifikasi format lama tetap tampil', function () {
        $this->pd->notify(new NaskahNotification(['title' => 'NPB-2026-001: Penelitian', 'message' => 'Naskah kini pada tahap Penelitian.']));

        Livewire::actingAs($this->pd)->test(Lonceng::class)
            ->assertSee('NPB-2026-001: Penelitian')
            ->assertSee('Naskah kini pada tahap Penelitian.');
    });

    it('notifikasi baru memunculkan toast, dan pemeriksaan berikutnya tidak mengulanginya', function () {
        $lonceng = Livewire::actingAs($this->pd)->test(Lonceng::class);

        $lonceng->call('periksa')->assertNotDispatched('swal:toast');

        $this->pd->notify(new NaskahNotification(['kategori' => 'tugas', 'title' => 'Sesuaikan rumusan rancangan', 'message' => 'NPB-1 · Uji']));

        $lonceng->call('periksa')->assertDispatched('swal:toast');
        $lonceng->call('periksa')->assertNotDispatched('swal:toast');
    });
});

describe('halaman notifikasi', function () {
    it('bisa difilter, dan hanya menampilkan milik sendiri', function () {
        $this->pd->notify(new NaskahNotification(['kategori' => 'tugas', 'title' => 'Tugas belum dikerjakan', 'message' => '-']));
        $this->pd->notify(new NaskahNotification(['kategori' => 'kabar', 'title' => 'Kabar biasa', 'message' => '-']));
        $this->admin->notify(new NaskahNotification(['kategori' => 'tugas', 'title' => 'Milik admin', 'message' => '-']));

        $this->actingAs($this->pd)->get(route('v2.notifikasi'))->assertOk();

        Livewire::actingAs($this->pd)->test(Notifikasi::class)
            ->assertSee('Tugas belum dikerjakan')->assertSee('Kabar biasa')->assertDontSee('Milik admin')
            ->call('pilihFilter', 'tindakan')
            ->assertSee('Tugas belum dikerjakan')->assertDontSee('Kabar biasa')
            ->call('tandaiSemua')
            ->assertSee('Tidak ada yang perlu Anda kerjakan saat ini.')
            ->call('pilihFilter', 'tidak-dikenal')
            ->assertNotFound();
    });
});
