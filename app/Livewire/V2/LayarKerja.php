<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahBerkas;
use App\Models\User;
use App\Services\AlurNaskah;
use App\Support\TataLetakNaskah;
use App\Support\TemplateNaskah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.v2')]
class LayarKerja extends Component
{
    use WithFileUploads;

    private const FILTER = ['semua', 'belum', 'perlu', 'diubah', 'berubah'];

    /** Naskah bisa ratusan baris: yang dirender bertahap supaya halaman tetap ringan. */
    private const PER_MUAT = 30;

    #[Locked]
    public Naskah $naskah;

    #[Url(except: 'kerja')]
    public string $tab = 'kerja';

    #[Url(except: '')]
    public string $filter = '';

    public int $batas = self::PER_MUAT;

    /** Isian modal perpindahan tahap. */
    public ?string $aksiTerpilih = null;
    public string $catatan = '';
    public ?int $penelitiId = null;

    public $berkasBaru;
    public string $jenisBerkas = NaskahBerkas::LAMPIRAN;

    public function mount(Naskah $naskah): void
    {
        Gate::authorize('view', $naskah);
        $this->naskah = $naskah;
    }

    /** Tiap peran langsung melihat pekerjaannya, kecuali memilih filter lain. */
    private function filterAwal(?string $mode, \Illuminate\Support\Collection $hitung): string
    {
        $adaPerubahan = collect([B::DIUBAH, B::DIHAPUS, B::PERLU_PD, B::DISESUAIKAN])->contains(fn ($s) => isset($hitung[$s]));
        $memvalidasi = $this->naskah->tahap === TahapNaskah::ValidasiRancangan && $this->naskah->giliranMilik(auth()->user());

        return match (true) {
            $mode === AlurNaskah::MODE_TELITI => isset($hitung[B::BELUM]) ? 'belum' : 'berubah',
            $mode === AlurNaskah::MODE_SESUAIKAN => 'perlu',
            ($mode === AlurNaskah::MODE_KOREKSI || $memvalidasi) && $adaPerubahan => 'diubah',
            default => 'semua',
        };
    }

    public function updatedFilter(): void
    {
        $this->batas = self::PER_MUAT;
    }

    public function muatLagi(): void
    {
        $this->batas += self::PER_MUAT;
    }

    /** Menampilkan baris yang tersembunyi oleh filter atau belum dimuat, saat dipilih dari panel struktur. */
    public function lompatKe(int $id): void
    {
        $urutan = $this->naskah->baris()->whereKey($id)->value('urutan');
        abort_if($urutan === null, 404);

        $this->filter = 'semua';
        $this->batas = max($this->batas, $this->naskah->baris()->where('urutan', '<=', $urutan)->count() + 5);
    }

    public function pilihAksi(string $aksi): void
    {
        $tersedia = app(AlurNaskah::class)->aksiTersedia($this->naskah->fresh(), auth()->user());
        abort_unless(isset($tersedia[$aksi]), 403);

        $this->aksiTerpilih = $aksi;
        $this->reset('catatan', 'penelitiId');
        $this->resetValidation();
        $this->dispatch('openModal', 'modalAksi');
    }

    public function jalankanAksi(AlurNaskah $alur): void
    {
        abort_unless($this->aksiTerpilih, 403);

        $this->naskah = $alur->jalankan($this->naskah, auth()->user(), $this->aksiTerpilih, $this->catatan ?: null, $this->penelitiId);

        $this->reset('aksiTerpilih', 'catatan', 'penelitiId', 'filter');
        $this->dispatch('closeModal', 'modalAksi');
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
    #[On('struktur-berubah')]
    public function segarkan(): void
    {
        $this->naskah->refresh();
    }

    public function render(AlurNaskah $alur)
    {
        $this->naskah->refresh();
        $user = auth()->user();
        $mode = $alur->modeBaris($this->naskah, $user);

        $semuaBaris = $this->naskah->baris()->get(['id', 'naskah_id', 'urutan', 'bagian', 'label', 'status', 'giliran_diubah', 'diubah_oleh']);
        $hitung = $semuaBaris->countBy('status');
        $total = $semuaBaris->count();
        $filter = in_array($this->filter, self::FILTER, true) ? $this->filter : $this->filterAwal($mode, $hitung);

        $terlihat = $semuaBaris->filter(fn (B $b) => match ($filter) {
            'belum' => $b->status === B::BELUM,
            'perlu' => $b->status === B::PERLU_PD,
            'diubah' => in_array($b->status, [B::DIUBAH, B::DIHAPUS, B::PERLU_PD, B::DISESUAIKAN], true),
            'berubah' => $b->giliran_diubah !== null && $b->giliran_diubah >= $this->naskah->giliran - 1 && $b->diubah_oleh !== $user->id,
            default => true,
        })->pluck('id');

        return view('v2.layar-kerja', [
            'mode' => $mode,
            'filterAktif' => $filter,
            'semuaBaris' => $semuaBaris,
            'daftarBaris' => $this->naskah->baris()->whereIn('id', $terlihat->take($this->batas))->get(),
            'sisaBaris' => max(0, $terlihat->count() - $this->batas),
            'penanda' => TemplateNaskah::penanda($this->naskah, $semuaBaris),
            'hitung' => $hitung,
            'total' => $total,
            'diperiksa' => $total - ($hitung[B::BELUM] ?? 0),
            'aksi' => $alur->aksiTersedia($this->naskah, $user),
            'peneliti' => $this->aksiTerpilih === 'tugaskan_peneliti' ? User::role('Peneliti')->orderBy('nama_user')->get(['id', 'nama_user']) : collect(),
            'bolehUnggah' => $this->bolehUnggah($alur),
            'blokNaskah' => $this->tab === 'naskah' ? TataLetakNaskah::susun($this->naskah, $this->naskah->baris()->get()) : [],
            'berkas' => $this->tab === 'berkas' ? $this->naskah->berkas()->with('pengunggah:id,nama_user')->get() : collect(),
            'riwayat' => $this->tab === 'riwayat' ? $this->naskah->riwayat()->with(['user:id,nama_user', 'baris:id,label,bagian'])->limit(200)->get() : collect(),
            'versi' => $this->tab === 'riwayat' ? $this->naskah->versi()->with('user:id,nama_user')->get(['id', 'naskah_id', 'versi', 'tahap', 'user_id', 'created_at']) : collect(),
        ])->title($this->naskah->nomor);
    }
}
