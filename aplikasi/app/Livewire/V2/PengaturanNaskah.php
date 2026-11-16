<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Models\Naskah;
use App\Models\Pengaturan;
use App\Services\AlurNaskah;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengaturan')]
class PengaturanNaskah extends Component
{
    #[Validate('required|string|max:150')]
    public string $namaBupati = '';

    #[Validate('required|string|max:150')]
    public string $namaSekda = '';

    /** Data tampil sebagai bacaan dulu; form baru terbuka lewat tombol Ubah. */
    public bool $mengubah = false;

    public function mount(): void
    {
        $this->isiDariPengaturan();
    }

    private function isiDariPengaturan(): void
    {
        $this->namaBupati = Pengaturan::ambil(Pengaturan::NAMA_BUPATI);
        $this->namaSekda = Pengaturan::ambil(Pengaturan::NAMA_SEKDA);
    }

    public function mulaiUbah(): void
    {
        $this->mengubah = true;
    }

    public function batal(): void
    {
        $this->isiDariPengaturan();
        $this->resetValidation();
        $this->mengubah = false;
    }

    public function simpan(): void
    {
        $this->validate();

        Pengaturan::simpan(Pengaturan::NAMA_BUPATI, $this->namaBupati);
        Pengaturan::simpan(Pengaturan::NAMA_SEKDA, $this->namaSekda);

        $this->mengubah = false;
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Nama pejabat tersimpan.']);
    }

    // ---------- Urutan tanda tangan ----------

    /** Daftar [['kunci' => ..., 'nama' => ...]] yang sedang diubah; kunci lama dipertahankan agar catatan tanggal tetap terbaca. */
    public array $tahapan = [];

    public bool $mengubahTahapan = false;

    private function bolehAturTahapan(): bool
    {
        return auth()->user()->hasAnyRole(['Admin', 'Super Admin']);
    }

    private function muatTahapan(array $daftar): void
    {
        $this->tahapan = collect($daftar)->map(fn ($nama, $kunci) => ['kunci' => (string) $kunci, 'nama' => $nama])->values()->all();
    }

    public function mulaiUbahTahapan(): void
    {
        abort_unless($this->bolehAturTahapan(), 403);
        $this->muatTahapan(AlurNaskah::tahapanTtd());
        $this->resetValidation();
        $this->mengubahTahapan = true;
    }

    public function batalTahapan(): void
    {
        $this->reset('tahapan', 'mengubahTahapan');
        $this->resetValidation();
    }

    public function tambahTahapan(): void
    {
        abort_unless($this->bolehAturTahapan() && count($this->tahapan) < 8, 403);
        $this->tahapan[] = ['kunci' => '', 'nama' => ''];
    }

    public function hapusTahapan(int $i): void
    {
        abort_unless($this->bolehAturTahapan(), 403);
        unset($this->tahapan[$i]);
        $this->tahapan = array_values($this->tahapan);
    }

    /** Menukar urutan dengan tetangganya; $arah -1 naik, 1 turun. */
    public function geserTahapan(int $i, int $arah): void
    {
        abort_unless($this->bolehAturTahapan(), 403);
        $j = $i + ($arah < 0 ? -1 : 1);

        if (isset($this->tahapan[$i], $this->tahapan[$j])) {
            [$this->tahapan[$i], $this->tahapan[$j]] = [$this->tahapan[$j], $this->tahapan[$i]];
        }
    }

    public function bawaanTahapan(): void
    {
        abort_unless($this->bolehAturTahapan(), 403);
        $this->muatTahapan(AlurNaskah::TAHAPAN_TTD);
    }

    public function simpanTahapan(): void
    {
        abort_unless($this->bolehAturTahapan(), 403);

        $this->tahapan = array_values(array_map(fn ($t) => ['kunci' => (string) ($t['kunci'] ?? ''), 'nama' => Str::squish((string) ($t['nama'] ?? ''))], $this->tahapan));

        $this->validate([
            'tahapan' => 'required|array|min:1|max:8',
            'tahapan.*.nama' => 'required|string|max:60|distinct:ignore_case',
        ], [
            'tahapan.required' => 'Minimal satu tahapan.',
            'tahapan.min' => 'Minimal satu tahapan.',
            'tahapan.max' => 'Paling banyak 8 tahapan.',
            'tahapan.*.nama.required' => 'Nama tahapan wajib diisi.',
            'tahapan.*.nama.distinct' => 'Nama tahapan tidak boleh sama.',
        ], ['tahapan.*.nama' => 'nama tahapan']);

        $daftar = [];

        foreach ($this->tahapan as $t) {
            // Tahapan lama memakai kuncinya sendiri; tahapan baru diberi kunci dari namanya.
            $kunci = preg_match('/^[a-z0-9_]{1,40}$/', $t['kunci']) ? $t['kunci'] : Str::limit(Str::slug($t['nama'], '_'), 30, '');
            $kunci = $kunci !== '' ? $kunci : 'tahapan';
            $dasar = $kunci;

            for ($n = 2; isset($daftar[$kunci]); $n++) {
                $kunci = "{$dasar}_{$n}";
            }

            $daftar[$kunci] = $t['nama'];
        }

        Pengaturan::simpan(Pengaturan::TAHAPAN_TTD, $daftar === AlurNaskah::TAHAPAN_TTD ? null : json_encode($daftar));

        $this->reset('tahapan', 'mengubahTahapan');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Urutan tanda tangan tersimpan.']);
    }

    public function render()
    {
        return view('v2.pengaturan', [
            'urutanTtd' => AlurNaskah::tahapanTtd(),
            'bolehAturTahapan' => $this->bolehAturTahapan(),
            'sedangBerjalan' => Naskah::whereIn('tahap', [TahapNaskah::Penandatanganan, TahapNaskah::Arsip])->count(),
        ]);
    }
}
