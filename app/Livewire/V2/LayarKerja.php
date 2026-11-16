<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahBerkas;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Support\BandingNaskah;
use App\Support\BlokNaskah;
use App\Support\PemeriksaIsian;
use App\Support\PemeriksaPenomoran;
use App\Support\TandaTanganLangsung;
use App\Support\TandaTanganPeneliti;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.kerja')]
class LayarKerja extends Component
{
    use WithFileUploads;

    private const FILTER = ['semua', 'belum', 'perlu', 'diubah', 'berubah'];

    /** Aksi yang formulirnya ada di halaman Nota Dinas & Tanda Tangan (PantauNaskah). */
    public const AKSI_TAHAP_AKHIR = ['terbitkan_nota', 'tandai_tahapan', 'arsipkan'];

    #[Locked]
    public Naskah $naskah;

    /** Daftar asal, supaya tombol kembali menuju menu yang tadi dibuka. */
    #[Locked]
    public string $kembali = '';

    #[Url(except: 'kerja')]
    public string $tab = 'kerja';

    #[Url(except: '')]
    public string $filter = '';

    /** Bagian naskah yang sedang dikerjakan (lihat BlokNaskah). */
    #[Url(as: 'bagian', except: '')]
    public string $blok = '';

    /** Isi tab Naskah: bentuk cetak atau perbandingan dengan rumusan awal. */
    #[Url(as: 'lihat', except: 'cetak')]
    public string $tampilNaskah = 'cetak';

    /** Isian modal perpindahan tahap. */
    public ?string $aksiTerpilih = null;
    public string $catatan = '';
    public ?int $penelitiId = null;

    /** Daftar periksa Admin sebelum "Berkas lengkap"; semua butir wajib dicentang. */
    public const PERIKSA_BERKAS = [
        'ttd' => 'Nota dinas PD sudah ditandatangani kepala Perangkat Daerah.',
        'nomor' => 'Nomor dan tanggal pada nota dinas sama dengan yang diisi di pengajuan.',
        'judul' => 'Judul rancangan sama dengan yang dimohonkan di nota dinas.',
        'isi' => 'Isi rancangan terbaca utuh (tidak ada bagian yang hilang atau berantakan).',
    ];

    public array $periksaBerkas = [];

    /** Naskah hasil perbaikan yang diunggah PD saat Ajukan fasilitasi (Word atau PDF). */
    public $berkasFasilitasi;

    /** Isian modal Ubah Judul: judul pengajuan yang juga menjadi judul naskah dan "Menetapkan". */
    public string $judulBaru = '';

    /** Tanda tangan matriks pada langkah terakhir Peneliti di fasilitasi: 'tersimpan' | 'langsung'. */
    public string $sumberTtd = 'tersimpan';
    public string $ttdLangsung = '';
    public bool $simpanTtd = false;

    /** Isian nota dinas. */


    /** Isian arsip. */

    #[Locked]
    public ?int $versiTerpilih = null;

    public $berkasBaru;
    public string $jenisBerkas = NaskahBerkas::LAMPIRAN;

    public function mount(Naskah $naskah)
    {
        Gate::authorize('view', $naskah);

        // Tamu memantau tanda tangan, bukan mengerjakan matriks.
        if (auth()->user()->pemantau()) {
            return $this->redirectRoute('naskah.pantau', $naskah);
        }

        $this->naskah = $naskah;
        $naskah->tandaiNotifikasiDibaca(auth()->user());

        $asal = url()->previous();
        $this->kembali = str_starts_with($asal, url('/')) && preg_match('#^/(dashboard|daftar/|notifikasi)#', (string) parse_url($asal, PHP_URL_PATH))
            ? $asal
            : route('dashboard');
    }

    /** Tiap peran langsung melihat pekerjaannya, kecuali memilih filter lain. */
    private function filterAwal(?string $mode, \Illuminate\Support\Collection $hitung): string
    {
        $adaPerubahan = collect(B::BERUBAH)->contains(fn ($s) => isset($hitung[$s]));
        $memvalidasi = $this->naskah->tahap === TahapNaskah::ValidasiRancangan && $this->naskah->giliranMilik(auth()->user());

        return match (true) {
            // Baris yang sudah diperiksa tetap terlihat; penanda selesainya ada di panel kiri.
            $mode === AlurNaskah::MODE_TELITI => isset($hitung[B::BELUM]) || isset($hitung[B::DISESUAIKAN]) ? 'semua' : 'berubah',
            // Naskah bisa dikembalikan hanya dengan catatan, tanpa baris yang ditandai; layar jangan sampai kosong.
            $mode === AlurNaskah::MODE_SESUAIKAN => isset($hitung[B::PERLU_PD]) ? 'perlu' : 'semua',
            ($mode === AlurNaskah::MODE_KOREKSI || $memvalidasi) && $adaPerubahan => 'diubah',
            default => 'semua',
        };
    }

    public function updatedFilter(): void
    {
        $this->blok = '';
    }

    /** Memperhatikan bersifat opsional pada Keputusan Bupati: barisnya disisipkan sesudah Mengingat. */
    public function tambahMemperhatikan(AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);
        abort_unless($this->naskah->jenis === 'Keputusan Bupati', 404);

        $baris = $this->naskah->baris()->get();
        abort_if($baris->contains('bagian', 'memperhatikan'), 409, 'Bagian Memperhatikan sudah ada.');

        // Sesudah Mengingat; bila tidak ada, sesudah bagian terakhir sebelum diktum.
        $acuan = $baris->whereIn('bagian', ['judul', 'menimbang', 'mengingat'])->last();
        abort_if($acuan === null, 422, 'Naskah belum punya judul.');

        $alur->tambahSetelah($acuan, auth()->user(), null, 'memperhatikan');
        $this->strukturBerubah();
        $this->pilihBlok('memperhatikan');
    }

    public function pilihBlok(string $kunci): void
    {
        $this->blok = $kunci;
        $this->tab = 'kerja';
        $this->dispatch('blok-berubah');
    }

    /** Menampilkan baris yang tersembunyi oleh filter, saat dipilih dari panel kiri. */
    public function lompatKe(int $id): void
    {
        $blok = collect(BlokNaskah::susun($this->naskah, $this->barisRingkas()))
            ->first(fn (array $b) => in_array($id, $b['ids'], true));
        abort_if($blok === null, 404);

        $this->filter = 'semua';
        $this->blok = $blok['kunci'];
    }

    /** Tombol Edit pada perbandingan: kembali ke matriks, tepat di baris itu. */
    public function bukaBaris(int $id): void
    {
        $this->lompatKe($id);
        $this->tab = 'kerja';
        $this->dispatch('fokus-baris', id: $id);
    }

    /** Hanya kolom yang dibutuhkan panel kiri, karena isi naskah bisa ratusan baris. */
    private function barisRingkas(): Collection
    {
        return $this->naskah->baris()
            ->select(['id', 'naskah_id', 'urutan', 'bagian', 'label', 'status', 'giliran_diubah', 'diubah_oleh'])
            ->selectRaw('substr(rumusan_awal, 1, 80) as cuplikan')
            ->get();
    }

    /** Penanda di panel kiri: perlu perbaikan, selesai, atau belum dikerjakan. */
    private function keadaanBlok(array $ids, Collection $perId, array $statusBelum, Collection $berisian): string
    {
        $anggota = collect($ids)->map(fn (int $id) => $perId[$id]);

        return match (true) {
            $anggota->contains('status', B::PERLU_PD) => 'perlu',
            // Lampiran boleh kosong, jadi tidak dihitung belum selesai. Isian "…" dari template juga belum selesai.
            $this->naskah->tahap === TahapNaskah::Draf => $anggota->every(fn (B $b) => $b->bagian === 'lampiran' || (filled($b->cuplikan) && ! $berisian->contains($b->id))) ? 'selesai' : 'kosong',
            default => $anggota->contains(fn (B $b) => in_array($b->status, $statusBelum, true)) ? 'kosong' : 'selesai',
        };
    }

    public function bukaUbahJudul(): void
    {
        $this->judulBaru = $this->naskah->fresh()->tentang;
        $this->resetValidation('tentang');
        $this->dispatch('openModal', 'modalJudul');
    }

    public function ubahJudul(AlurNaskah $alur): void
    {
        $this->naskah = $alur->ubahJudul($this->naskah, auth()->user(), $this->judulBaru);
        $this->dispatch('closeModal', 'modalJudul');
        $this->dispatch('struktur-berubah');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Judul diperbarui.']);
    }

    public function pilihAksi(string $aksi): void
    {
        $tersedia = app(AlurNaskah::class)->aksiTersedia($this->naskah->fresh(), auth()->user());
        abort_unless(isset($tersedia[$aksi]), 403);

        // Nota dinas dan tahapan tanda tangan dikerjakan di halamannya sendiri, bukan di atas matriks.
        if (in_array($aksi, self::AKSI_TAHAP_AKHIR, true)) {
            $this->redirectRoute('naskah.pantau', $this->naskah);

            return;
        }

        $this->resetIsianAksi();
        $this->aksiTerpilih = $aksi;
        $this->sumberTtd = auth()->user()->file_ttd ? 'tersimpan' : 'langsung';
        $this->resetValidation();
        $this->dispatch('openModal', 'modalAksi');
    }

    public function jalankanAksi(AlurNaskah $alur)
    {
        abort_unless($this->aksiTerpilih, 403);
        $user = auth()->user();

        // Galat dari percobaan sebelumnya jangan tertinggal saat pengguna sudah memperbaiki isiannya.
        $this->resetValidation();

        // Langkah terakhir Peneliti di fasilitasi sekaligus menandatangani matriks.
        $denganTtd = ! empty($alur->aksiTersedia($this->naskah->fresh(), $user)[$this->aksiTerpilih]['ttd_peneliti']);
        $ttd = null;
        $sementara = null;

        if ($denganTtd && $this->sumberTtd === 'langsung') {
            $ttd = $sementara = TandaTanganLangsung::fileSementara($this->ttdLangsung, 'ttd');
        } elseif ($denganTtd) {
            $ttd = TandaTanganPeneliti::path($user)
                ?? throw ValidationException::withMessages(['ttd' => 'Belum ada tanda tangan tersimpan. Pilih Tanda tangan sekarang, atau unggah dulu di Profil.']);
        }

        if ($this->aksiTerpilih === 'setujui_berkas' && array_diff(array_keys(self::PERIKSA_BERKAS), $this->periksaBerkas)) {
            throw ValidationException::withMessages(['periksaBerkas' => 'Centang semua butir daftar periksa dulu.']);
        }

        // Ajukan fasilitasi: naskah yang sudah direvisi PD diunggah sebagai bukti perubahan sudah diterapkan.
        if ($this->aksiTerpilih === 'ajukan_fasilitasi' && $this->berkasFasilitasi) {
            $this->validate(['berkasFasilitasi' => 'file|mimes:pdf,docx|max:20480'], [], ['berkasFasilitasi' => 'naskah hasil perbaikan']);
            $this->simpanBerkas(NaskahBerkas::NASKAH_FASILITASI, $this->berkasFasilitasi);
        }

        $aksi = $this->aksiTerpilih;

        try {
            $this->naskah = match ($aksi) {
                'ganti_peneliti' => $alur->gantiPeneliti($this->naskah, $user, $this->penelitiId, $this->catatan ?: null),
                default => $alur->jalankan($this->naskah, $user, $aksi, $this->catatan ?: null, $this->penelitiId, $ttd),
            };

            if ($sementara && $this->simpanTtd) {
                TandaTanganPeneliti::simpan($user, $sementara);
            }
        } finally {
            if ($sementara) {
                @unlink($sementara);
            }
        }

        $this->resetIsianAksi();
        $this->reset('filter');
        $this->dispatch('closeModal', 'modalAksi');

        // Giliran sudah berpindah: kembali ke daftar asal, tempat pekerjaan berikutnya menunggu.
        if ($aksi !== 'ganti_peneliti' && ! $this->naskah->giliranMilik($user)) {
            $ke = $this->naskah->tahap;
            session()->flash('toast', ['type' => 'success', 'message' => "{$this->naskah->nomor_tampil} diteruskan: {$ke->label()}, giliran {$ke->peran()}."]);

            return $this->redirect($this->kembali, navigate: true);
        }
    }

    private function resetIsianAksi(): void
    {
        $this->reset('aksiTerpilih', 'catatan', 'penelitiId', 'sumberTtd', 'ttdLangsung', 'simpanTtd', 'berkasFasilitasi', 'periksaBerkas');
    }

    /** Lampiran dan naskah hasil perbaikan hanya satu yang berlaku; yang lama tersimpan sebagai versi lama. */
    private function simpanBerkas(string $jenis, $file): void
    {
        DB::transaction(function () use ($jenis, $file) {
            if (in_array($jenis, [NaskahBerkas::LAMPIRAN, NaskahBerkas::NASKAH_FASILITASI], true)) {
                $this->naskah->berkas()->where('jenis', $jenis)->update(['aktif' => false]);
            }

            $this->naskah->berkas()->create([
                'jenis' => $jenis,
                'path' => $file->store("naskah/{$this->naskah->slug}", 'local'),
                'nama_asli' => $file->getClientOriginalName(),
                'diunggah_oleh' => auth()->id(),
            ]);
        });
    }

    public function buangDraf(AlurNaskah $alur)
    {
        $nama = $this->naskah->nomor === null
            ? 'Draf "' . Str::limit($this->naskah->tentang, 60) . '"'
            : "Rancangan {$this->naskah->nomor}";

        $alur->buangDraf($this->naskah, auth()->user());

        session()->flash('pesan', "{$nama} sudah dihapus.");

        return $this->redirectRoute('dashboard');
    }

    public function lihatVersi(int $id): void
    {
        $this->versiTerpilih = $this->naskah->versi()->whereKey($id)->firstOrFail()->id;
        $this->dispatch('openModal', 'modalVersi');
    }

    public function unggahBerkas(AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);

        $this->validate([
            'jenisBerkas' => 'required|in:' . implode(',', $this->jenisUnggahan()),
            'berkasBaru' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
        ]);

        if (! $this->bolehUnggah($alur)) {
            abort(403, 'Berkas hanya bisa diubah pada giliran Anda.');
        }

        $this->simpanBerkas($this->jenisBerkas, $this->berkasBaru);

        $this->reset('berkasBaru');
    }

    /** Peneliti beserta jumlah naskah yang sedang ditanganinya dan yang menunggu dikerjakannya. */
    private function penelitiDenganBeban(): Collection
    {
        $berjalan = [TahapNaskah::Penelitian, TahapNaskah::PerbaikanPd, TahapNaskah::ValidasiRancangan, TahapNaskah::Disetujui,
            TahapNaskah::PersetujuanFasilitasi, TahapNaskah::ValidasiFasilitasi, TahapNaskah::PerbaikanFasilitasi, TahapNaskah::CekPeneliti];
        $menunggu = [TahapNaskah::Penelitian, TahapNaskah::PersetujuanFasilitasi, TahapNaskah::CekPeneliti];
        $hitung = fn (array $tahap) => Naskah::whereIn('tahap', $tahap)->whereNotNull('peneliti_id')
            ->selectRaw('peneliti_id, count(*) as n')->groupBy('peneliti_id')->pluck('n', 'peneliti_id');
        [$aktif, $antre] = [$hitung($berjalan), $hitung($menunggu)];

        return User::role('Peneliti')->whereKeyNot($this->naskah->peneliti_id ?? 0)->orderBy('nama_user')->get(['id', 'nama_user'])
            ->each(function (User $u) use ($aktif, $antre) {
                $u->beban_aktif = (int) ($aktif[$u->id] ?? 0);
                $u->beban_menunggu = (int) ($antre[$u->id] ?? 0);
            });
    }

    /** Jenis berkas yang bisa diunggah di tab Berkas; naskah hasil perbaikan hanya pada tahap fasilitasi. */
    private function jenisUnggahan(): array
    {
        return [
            NaskahBerkas::LAMPIRAN, NaskahBerkas::BAHAN_PENDUKUNG,
            ...($this->naskah->tahap->langkah() === 3 ? [NaskahBerkas::NASKAH_FASILITASI] : []),
        ];
    }

    private function bolehUnggah(AlurNaskah $alur): bool
    {
        return in_array($alur->modeBaris($this->naskah->fresh(), auth()->user()), [
            AlurNaskah::MODE_SUSUN, AlurNaskah::MODE_TELITI, AlurNaskah::MODE_SESUAIKAN,
        ], true);
    }

    /** Baris lain berubah: cukup kartu ringkasan yang dihitung ulang. */
    #[On('baris-berubah')]
    #[On('baris-selesai')]
    public function segarkan(): void
    {
        $this->naskah->refresh();
    }

    /** Baris yang baru disisipkan harus terlihat, padahal filter bawaan menyembunyikan ubahan sendiri. */
    #[On('struktur-berubah')]
    public function strukturBerubah(): void
    {
        $this->filter = 'semua';
        $this->naskah->refresh();
    }

    public function render(AlurNaskah $alur)
    {
        $this->naskah->refresh();
        $user = auth()->user();
        $mode = $alur->modeBaris($this->naskah, $user);
        $bisaUnduh = Gate::allows('unduh', $this->naskah);

        // Tab Berkas hanya berisi unduhan, jadi tidak ada artinya bagi yang tidak boleh mengunduh.
        if ($this->tab === 'berkas' && ! $bisaUnduh) {
            $this->tab = 'kerja';
        }

        $semuaBaris = $this->barisRingkas();
        $bertugas = $mode !== null || ($this->naskah->tahap === TahapNaskah::ValidasiRancangan && $this->naskah->giliranMilik($user));
        $hitung = $semuaBaris->countBy('status');
        $total = $semuaBaris->count();
        // Bagi Peneliti, perbaikan PD yang belum diterima juga belum diperiksa.
        $statusBelum = $mode === AlurNaskah::MODE_TELITI ? B::BELUM_DIPERIKSA : [B::BELUM];
        $jumlahBelum = collect($statusBelum)->sum(fn ($s) => $hitung[$s] ?? 0);
        $barisLengkap = $this->naskah->tahap === TahapNaskah::Draf || ($this->tab === 'kerja' && $bertugas) ? $this->naskah->baris()->get() : collect();
        $berisian = $this->naskah->tahap === TahapNaskah::Draf
            ? $barisLengkap->filter(fn (B $b) => PemeriksaIsian::adaIsian($b))->pluck('id')
            : collect();
        $filter = in_array($this->filter, self::FILTER, true) ? $this->filter : $this->filterAwal($mode, $hitung);

        $terlihat = $semuaBaris->filter(fn (B $b) => match ($filter) {
            'belum' => in_array($b->status, $statusBelum, true),
            'perlu' => $b->status === B::PERLU_PD,
            'diubah' => in_array($b->status, B::BERUBAH, true),
            'berubah' => $b->giliran_diubah !== null && $b->giliran_diubah >= $this->naskah->giliran - 1 && $b->diubah_oleh !== $user->id,
            default => true,
        })->pluck('id');

        // Filter yang menyisakan sebagian baris juga menyembunyikan bagian yang tidak punya baris cocok.
        $perId = $semuaBaris->keyBy('id');
        $daftarBlok = collect(BlokNaskah::susun($this->naskah, $semuaBaris))
            ->map(fn (array $b) => [...$b, 'keadaan' => $this->keadaanBlok($b['ids'], $perId, $statusBelum, $berisian)])
            ->filter(fn (array $b) => $terlihat->intersect($b['ids'])->isNotEmpty())
            ->values();

        $blokAktif = $daftarBlok->firstWhere('kunci', $this->blok)
            ?? ($bertugas ? $daftarBlok->firstWhere('keadaan', '!=', 'selesai') : null)
            ?? $daftarBlok->first();

        // Tombol Tambah Memperhatikan tampil sesudah bagian terakhir sebelum diktum yang terlihat.
        $bisaTambahMemperhatikan = $this->naskah->jenis === 'Keputusan Bupati'
            && in_array($mode, [AlurNaskah::MODE_SUSUN, AlurNaskah::MODE_TELITI, AlurNaskah::MODE_SESUAIKAN], true)
            && ! $semuaBaris->contains('bagian', 'memperhatikan');
        $letakMemperhatikan = $bisaTambahMemperhatikan
            ? ($daftarBlok->filter(fn (array $b) => in_array($b['bagian'], ['judul', 'menimbang', 'mengingat'], true))->last()['kunci'] ?? '')
            : null;

        // Disimpan supaya pilihan tidak melompat saat baris terakhir bagian ini selesai diperiksa.
        $this->blok = $blokAktif['kunci'] ?? '';
        $posisi = $blokAktif ? $daftarBlok->search(fn (array $b) => $b['kunci'] === $blokAktif['kunci']) : 0;

        // Draf belum punya rumusan penyempurnaan, jadi belum ada yang dibandingkan.
        $banding = $this->tab === 'naskah' && $this->tampilNaskah === 'banding' && $this->naskah->tahap !== TahapNaskah::Draf;

        if ($this->tab === 'kerja' && $blokAktif) {
            $this->dispatch('pratinjau-sorot', ids: $blokAktif['ids']);
        }

        return view('v2.layar-kerja', [
            'banding' => $banding,
            'bisaUnduh' => $bisaUnduh,
            'mode' => $mode,
            'bolehBuang' => $alur->bolehBuang($this->naskah, $user),
            'filterAktif' => $filter,
            'perId' => $perId,
            'daftarBlok' => $daftarBlok,
            'letakMemperhatikan' => $letakMemperhatikan,
            'blokAktif' => $blokAktif,
            'posisiBlok' => $posisi,
            'blokSebelumnya' => $posisi > 0 ? $daftarBlok->get($posisi - 1) : null,
            'blokBerikutnya' => $daftarBlok->get($posisi + 1),
            'daftarBaris' => $blokAktif ? $this->naskah->baris()->whereIn('id', $terlihat->intersect($blokAktif['ids']))->get() : collect(),
            'penanda' => TemplateNaskah::penanda($this->naskah, $semuaBaris),
            'hitung' => $hitung,
            'total' => $total,
            'diperiksa' => $total - $jumlahBelum,
            'jumlahBelum' => $jumlahBelum,
            'statusBelum' => $statusBelum,
            'bolehUbahJudul' => in_array($mode, [AlurNaskah::MODE_SUSUN, AlurNaskah::MODE_TELITI, AlurNaskah::MODE_KOREKSI], true),
            'aksi' => $alur->aksiTersedia($this->naskah, $user),
            // Saat mengganti, peneliti yang sedang bertugas tidak ditawarkan lagi. Bebannya ikut ditampilkan
            // supaya tugas terbagi rata.
            'peneliti' => in_array($this->aksiTerpilih, ['tugaskan_peneliti', 'ganti_peneliti'], true)
                ? $this->penelitiDenganBeban()
                : collect(),
            'barisUntukPd' => in_array($this->aksiTerpilih, ['kirim_ke_pd', 'cek_ke_pd'], true)
                ? $this->naskah->baris()->where('status', B::PERLU_PD)->get(['id', 'bagian', 'label', 'keterangan'])
                : collect(),
            'naskahPerbaikan' => $this->naskah->tahap->langkah() === 3
                ? $this->naskah->berkas()->where('jenis', NaskahBerkas::NASKAH_FASILITASI)->where('aktif', true)->latest('id')->first()
                : null,
            'berkasFasilitasiBerlaku' => $this->aksiTerpilih === 'ajukan_fasilitasi' ? $alur->berkasFasilitasiBerlaku($this->naskah) : null,
            'jenisUnggahan' => $this->jenisUnggahan(),
            // Sebelum penelitian belum ada baris yang diperiksa, jadi penghitungnya tidak berarti.
            'tampilkanHitung' => ! in_array($this->naskah->tahap, [TahapNaskah::Draf, TahapNaskah::PersetujuanBerkas, TahapNaskah::PilihPeneliti], true),
            // Isi naskah lebih dulu (isian "…", judul, Menetapkan), lalu penomoran.
            'peringatan' => $this->tab === 'kerja' && $bertugas
                ? [...PemeriksaIsian::periksa($this->naskah, $barisLengkap), ...PemeriksaPenomoran::periksa($barisLengkap->where('bagian', 'batang_tubuh')->values())]
                : [],
            'nota' => $this->naskah->tahap->langkah() >= 4 || $this->naskah->tahap === TahapNaskah::ValidasiFasilitasi ? $this->naskah->notaDinas()->first() : null,
            'ttdSelesai' => count(array_intersect_key($this->naskah->tahapan_ttd ?? [], AlurNaskah::tahapanTtd())),
            'isiVersi' => $this->versiTerpilih ? $this->naskah->versi()->find($this->versiTerpilih) : null,
            'bolehUnggah' => $this->bolehUnggah($alur),
            'blokNaskah' => match (true) {
                $banding => BandingNaskah::susun($this->naskah, $this->naskah->baris()->get()),
                $this->tab === 'naskah' => TataLetakNaskah::susun($this->naskah, $this->naskah->baris()->get()),
                default => [],
            },
            'berkas' => $this->tab === 'berkas' ? $this->naskah->berkas()->with('pengunggah:id,nama_user')->get() : collect(),
            'riwayat' => $this->tab === 'riwayat' ? $this->naskah->riwayat()->with(['user:id,nama_user', 'baris:id,label,bagian'])->limit(200)->get() : collect(),
            'versi' => $this->tab === 'riwayat' ? $this->naskah->versi()->with('user:id,nama_user')->get(['id', 'naskah_id', 'versi', 'tahap', 'user_id', 'created_at']) : collect(),
        ])->title($this->naskah->nomor_tampil);
    }
}
