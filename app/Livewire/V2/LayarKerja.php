<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahBerkas;
use App\Models\TandaTangan;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Support\BandingNaskah;
use App\Support\BlokNaskah;
use App\Support\PemeriksaPenomoran;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    /** Isian nota dinas. */
    public string $nomorInputan = '';
    public string $kepada = '';
    public ?int $tandaTanganId = null;

    public string $tanggalTahapan = '';

    /** Isian arsip. */
    public string $nomorArsip = '';
    public string $tanggalPenetapan = '';
    public string $nomorBerita = '';
    public string $tahunBerita = '';
    public $fileArsip;

    #[Locked]
    public ?int $versiTerpilih = null;

    public $berkasBaru;
    public string $jenisBerkas = NaskahBerkas::LAMPIRAN;

    public function mount(Naskah $naskah): void
    {
        Gate::authorize('view', $naskah);
        $this->naskah = $naskah;

        $asal = url()->previous();
        $this->kembali = str_starts_with($asal, url('/v2')) && ! str_contains($asal, '/naskah/')
            ? $asal
            : route('v2.beranda');
    }

    /** Tiap peran langsung melihat pekerjaannya, kecuali memilih filter lain. */
    private function filterAwal(?string $mode, \Illuminate\Support\Collection $hitung): string
    {
        $adaPerubahan = collect(B::BERUBAH)->contains(fn ($s) => isset($hitung[$s]));
        $memvalidasi = $this->naskah->tahap === TahapNaskah::ValidasiRancangan && $this->naskah->giliranMilik(auth()->user());

        return match (true) {
            // Baris yang sudah diperiksa tetap terlihat; penanda selesainya ada di panel kiri.
            $mode === AlurNaskah::MODE_TELITI => isset($hitung[B::BELUM]) ? 'semua' : 'berubah',
            $mode === AlurNaskah::MODE_SESUAIKAN => 'perlu',
            ($mode === AlurNaskah::MODE_KOREKSI || $memvalidasi) && $adaPerubahan => 'diubah',
            default => 'semua',
        };
    }

    public function updatedFilter(): void
    {
        $this->blok = '';
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
    private function keadaanBlok(array $ids, Collection $perId): string
    {
        $anggota = collect($ids)->map(fn (int $id) => $perId[$id]);

        return match (true) {
            $anggota->contains('status', B::PERLU_PD) => 'perlu',
            // Lampiran boleh kosong, jadi tidak dihitung belum selesai.
            $this->naskah->tahap === TahapNaskah::Draf => $anggota->every(fn (B $b) => $b->bagian === 'lampiran' || filled($b->cuplikan)) ? 'selesai' : 'kosong',
            default => $anggota->contains('status', B::BELUM) ? 'kosong' : 'selesai',
        };
    }

    public function pilihAksi(string $aksi): void
    {
        $tersedia = app(AlurNaskah::class)->aksiTersedia($this->naskah->fresh(), auth()->user());
        abort_unless(isset($tersedia[$aksi]), 403);

        $this->resetIsianAksi();
        $this->aksiTerpilih = $aksi;
        $this->tanggalTahapan = now()->toDateString();
        $this->resetValidation();
        $this->dispatch('openModal', 'modalAksi');
    }

    public function jalankanAksi(AlurNaskah $alur): void
    {
        abort_unless($this->aksiTerpilih, 403);
        $user = auth()->user();

        // Galat dari percobaan sebelumnya jangan tertinggal saat pengguna sudah memperbaiki isiannya.
        $this->resetValidation();

        $this->naskah = match ($this->aksiTerpilih) {
            'terbitkan_nota' => $alur->terbitkanNota($this->naskah, $user, $this->nomorInputan, $this->kepada, $this->tandaTanganId),
            'tandai_tahapan' => $alur->tandaiTahapan($this->naskah, $user, $this->tanggalTahapan),
            'arsipkan' => $this->arsipkan($alur),
            default => $alur->jalankan($this->naskah, $user, $this->aksiTerpilih, $this->catatan ?: null, $this->penelitiId),
        };

        $this->resetIsianAksi();
        $this->reset('filter');
        $this->dispatch('closeModal', 'modalAksi');
    }

    private function arsipkan(AlurNaskah $alur): Naskah
    {
        $this->validate([
            'nomorArsip' => 'required|numeric|digits_between:1,3',
            'tanggalPenetapan' => 'required|date',
            'nomorBerita' => 'nullable|numeric|digits_between:1,3',
            'tahunBerita' => 'nullable|numeric|digits:4',
            'fileArsip' => 'required|file|mimes:pdf|max:20480',
        ], [], [
            'nomorArsip' => 'nomor',
            'tanggalPenetapan' => 'tanggal penetapan',
            'nomorBerita' => 'nomor berita daerah',
            'tahunBerita' => 'tahun berita daerah',
            'fileArsip' => 'file bertanda tangan',
        ]);

        return $alur->arsipkan($this->naskah, auth()->user(), $this->nomorArsip, $this->tanggalPenetapan, $this->nomorBerita ?: null, $this->tahunBerita ?: null, $this->fileArsip);
    }

    private function resetIsianAksi(): void
    {
        $this->reset('aksiTerpilih', 'catatan', 'penelitiId', 'nomorInputan', 'kepada', 'tandaTanganId', 'tanggalTahapan',
            'nomorArsip', 'tanggalPenetapan', 'nomorBerita', 'tahunBerita', 'fileArsip');
    }

    public function buangDraf(AlurNaskah $alur)
    {
        $nomor = $this->naskah->nomor;

        $alur->buangDraf($this->naskah, auth()->user());

        session()->flash('pesan', "Draf {$nomor} sudah dibuang.");

        return $this->redirectRoute('v2.beranda');
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
            'jenisBerkas' => 'required|in:' . NaskahBerkas::LAMPIRAN . ',' . NaskahBerkas::BAHAN_PENDUKUNG,
            'berkasBaru' => 'required|file|mimes:pdf,doc,docx,xls,xlsx|max:20480',
        ]);

        if (! $this->bolehUnggah($alur)) {
            abort(403, 'Berkas hanya bisa diubah pada giliran Anda.');
        }

        DB::transaction(function () {
            // Lampiran hanya satu yang berlaku; yang lama tetap tersimpan sebagai riwayat.
            if ($this->jenisBerkas === NaskahBerkas::LAMPIRAN) {
                $this->naskah->berkas()->where('jenis', NaskahBerkas::LAMPIRAN)->update(['aktif' => false]);
            }

            $this->naskah->berkas()->create([
                'jenis' => $this->jenisBerkas,
                'path' => $this->berkasBaru->store("naskah/{$this->naskah->slug}", 'local'),
                'nama_asli' => $this->berkasBaru->getClientOriginalName(),
                'diunggah_oleh' => auth()->id(),
            ]);
        });

        $this->reset('berkasBaru');
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
        $filter = in_array($this->filter, self::FILTER, true) ? $this->filter : $this->filterAwal($mode, $hitung);

        $terlihat = $semuaBaris->filter(fn (B $b) => match ($filter) {
            'belum' => $b->status === B::BELUM,
            'perlu' => $b->status === B::PERLU_PD,
            'diubah' => in_array($b->status, B::BERUBAH, true),
            'berubah' => $b->giliran_diubah !== null && $b->giliran_diubah >= $this->naskah->giliran - 1 && $b->diubah_oleh !== $user->id,
            default => true,
        })->pluck('id');

        // Filter yang menyisakan sebagian baris juga menyembunyikan bagian yang tidak punya baris cocok.
        $perId = $semuaBaris->keyBy('id');
        $daftarBlok = collect(BlokNaskah::susun($this->naskah, $semuaBaris))
            ->map(fn (array $b) => [...$b, 'keadaan' => $this->keadaanBlok($b['ids'], $perId)])
            ->filter(fn (array $b) => $terlihat->intersect($b['ids'])->isNotEmpty())
            ->values();

        $blokAktif = $daftarBlok->firstWhere('kunci', $this->blok)
            ?? ($bertugas ? $daftarBlok->firstWhere('keadaan', '!=', 'selesai') : null)
            ?? $daftarBlok->first();

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
            'filterAktif' => $filter,
            'perId' => $perId,
            'daftarBlok' => $daftarBlok,
            'blokAktif' => $blokAktif,
            'posisiBlok' => $posisi,
            'blokSebelumnya' => $posisi > 0 ? $daftarBlok->get($posisi - 1) : null,
            'blokBerikutnya' => $daftarBlok->get($posisi + 1),
            'daftarBaris' => $blokAktif ? $this->naskah->baris()->whereIn('id', $terlihat->intersect($blokAktif['ids']))->get() : collect(),
            'penanda' => TemplateNaskah::penanda($this->naskah, $semuaBaris),
            'hitung' => $hitung,
            'total' => $total,
            'diperiksa' => $total - ($hitung[B::BELUM] ?? 0),
            'aksi' => $alur->aksiTersedia($this->naskah, $user),
            'peneliti' => $this->aksiTerpilih === 'tugaskan_peneliti' ? User::role('Peneliti')->orderBy('nama_user')->get(['id', 'nama_user']) : collect(),
            'daftarTtd' => $this->aksiTerpilih === 'terbitkan_nota' ? TandaTangan::where('status', 'Aktif')->get(['id', 'nama_ttd', 'jabatan']) : collect(),
            'peringatan' => $this->tab === 'kerja' && $bertugas
                ? PemeriksaPenomoran::periksa($this->naskah->baris()->where('bagian', 'batang_tubuh')->get())
                : [],
            'nota' => $this->naskah->tahap->langkah() >= 4 || $this->naskah->tahap === TahapNaskah::ValidasiFasilitasi ? $this->naskah->notaDinas()->first() : null,
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
        ])->title($this->naskah->nomor);
    }
}
