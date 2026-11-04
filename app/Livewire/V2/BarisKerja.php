<?php

namespace App\Livewire\V2;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Services\AlurNaskah;
use App\Support\TataLetakNaskah;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * Satu baris matriks sebagai komponen sendiri: menyimpan satu baris tidak merender ulang
 * puluhan baris lainnya. Teks yang diedit hidup di Alpine dan baru dikirim saat disimpan,
 * jadi isi naskah tidak ikut bolak-balik di setiap permintaan.
 */
class BarisKerja extends Component
{
    #[Locked]
    public NaskahBaris $baris;

    #[Locked]
    public Naskah $naskah;

    #[Locked]
    public ?string $penanda = null;

    public function simpanSusun(?string $label, ?string $teks, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->susun($this->baris, auth()->user(), $label, $teks));
    }

    public function tetap(?string $keterangan, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->tetapkan($this->baris, auth()->user(), $keterangan));
        $this->dispatch('baris-selesai', id: $this->baris->id);
    }

    /** @return bool berhasil atau tidak, supaya editor tetap terbuka saat isiannya ditolak */
    public function simpanPerbaikan(?string $teks, ?string $keterangan, AlurNaskah $alur, ?string $label = null): bool
    {
        return $this->jalankan(fn () => $alur->perbaiki($this->baris, auth()->user(), $teks, $keterangan, $label));
    }

    public function hapus(?string $keterangan, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->hapus($this->baris, auth()->user(), $keterangan));
    }

    public function mintaPd(?string $teks, ?string $keterangan, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->mintaPerbaikan($this->baris, auth()->user(), $keterangan, $teks));
    }

    public function simpanSesuaian(?string $teks, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->sesuaikan($this->baris, auth()->user(), $teks));
    }

    /** Hanya mengolah teks di editor; tersimpan saat pengguna menyimpan barisnya. */
    #[Renderless]
    public function rapikanNomor(?string $teks): string
    {
        return TataLetakNaskah::rapikanNomor($teks);
    }

    public function tambah(AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);
        $alur->tambahSetelah($this->baris, auth()->user());
        $this->dispatch('struktur-berubah');
    }

    public function buang(AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);
        $alur->buangSaatMenyusun($this->baris, auth()->user());
        $this->dispatch('struktur-berubah');
    }

    public function buangSisipan(AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);

        try {
            $alur->buangSisipan($this->baris, auth()->user());
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return;
        }

        $this->dispatch('struktur-berubah');
    }

    public function geser(int $arah, AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);
        $alur->geser($this->baris, auth()->user(), $arah);
        $this->dispatch('struktur-berubah');
    }

    private function jalankan(callable $aksi): bool
    {
        Gate::authorize('view', $this->naskah);

        try {
            $this->baris = $aksi();
        } catch (ValidationException $e) {
            $this->setErrorBag($e->validator->errors());

            return false;
        }

        $this->resetErrorBag();
        $this->dispatch('baris-berubah');

        return true;
    }

    public function render(AlurNaskah $alur)
    {
        // Saat halaman dibuka semua baris memakai model yang sama dari induk, jadi tidak ada query per baris.
        // Setiap aksi tetap memeriksa giliran terbaru di AlurNaskah.
        return view('v2.baris-kerja', [
            'mode' => $alur->modeBaris($this->naskah, auth()->user()),
        ]);
    }
}
