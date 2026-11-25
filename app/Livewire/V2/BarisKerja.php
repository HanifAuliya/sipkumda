<?php

namespace App\Livewire\V2;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\NaskahBerkas;
use App\Services\AlurNaskah;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Satu baris matriks sebagai komponen sendiri: menyimpan satu baris tidak merender ulang
 * puluhan baris lainnya. Teks yang diedit hidup di Alpine dan baru dikirim saat disimpan,
 * jadi isi naskah tidak ikut bolak-balik di setiap permintaan.
 */
class BarisKerja extends Component
{
    use WithFileUploads;

    #[Locked]
    public NaskahBaris $baris;

    #[Locked]
    public Naskah $naskah;

    #[Locked]
    public ?string $penanda = null;

    /** PDF lampiran yang sedang diunggah (baris lampiran berbentuk berkas). */
    public $pdfLampiran;

    /**
     * Mengunggah PDF lampiran. Setiap unggahan menjadi berkas tersendiri (versi), lalu disimpan sebagai isi
     * baris sesuai giliran: rumusan awal saat menyusun, perbaikan Peneliti, atau penyesuaian PD.
     */
    public function unggahLampiran(AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);
        abort_unless($this->baris->berkas(), 403);

        $this->validate(['pdfLampiran' => 'required|file|mimes:pdf|max:20480'], [], ['pdfLampiran' => 'PDF lampiran']);

        $mode = $alur->modeBaris($this->naskah, auth()->user());
        abort_unless(in_array($mode, [AlurNaskah::MODE_SUSUN, AlurNaskah::MODE_TELITI, AlurNaskah::MODE_KOREKSI, AlurNaskah::MODE_SESUAIKAN], true), 403);

        $berkas = $this->naskah->berkas()->create([
            'jenis' => NaskahBerkas::LAMPIRAN_BARIS,
            'path' => $this->pdfLampiran->store("naskah/{$this->naskah->slug}/lampiran", 'local'),
            'nama_asli' => $this->pdfLampiran->getClientOriginalName(),
            'diunggah_oleh' => auth()->id(),
        ]);
        $id = (string) $berkas->id;

        $tersimpan = $this->jalankan(fn () => match ($mode) {
            AlurNaskah::MODE_SUSUN => $alur->susun($this->baris, auth()->user(), $this->baris->label, $id),
            AlurNaskah::MODE_SESUAIKAN => $alur->sesuaikan($this->baris, auth()->user(), $id),
            default => $alur->perbaiki($this->baris, auth()->user(), $id, $this->baris->keterangan),
        });

        if (! $tersimpan) {
            Storage::disk('local')->delete($berkas->path);
            $berkas->delete();
        }

        $this->reset('pdfLampiran');
    }

    /** Saat penyusunan: lampiran diketik di editor, atau berupa berkas PDF. */
    public function aturFormat(string $format, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->aturFormatLampiran($this->baris, auth()->user(), $format));
    }

    public function simpanSusun(?string $label, ?string $teks, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->susun($this->baris, auth()->user(), $label, $teks));
    }

    public function tetap(?string $keterangan, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->tetapkan($this->baris, auth()->user(), $keterangan));
        $this->dispatch('baris-selesai', id: $this->baris->id);
    }

    /** Perbaikan dari PD diterima apa adanya. */
    public function terimaSesuaian(?string $keterangan, AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->terimaSesuaian($this->baris, auth()->user(), $keterangan));
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

    /** @return bool berhasil atau tidak, supaya kotak keterangan tetap terbuka saat isiannya ditolak */
    public function simpanKeterangan(?string $keterangan, AlurNaskah $alur): bool
    {
        return $this->jalankan(fn () => $alur->simpanKeterangan($this->baris, auth()->user(), $keterangan));
    }

    /** @return bool berhasil atau tidak, supaya panel arahan tetap terbuka saat isiannya ditolak */
    public function mintaPd(?string $teks, ?string $keterangan, AlurNaskah $alur): bool
    {
        return $this->jalankan(fn () => $alur->mintaPerbaikan($this->baris, auth()->user(), $keterangan, $teks));
    }

    /** @return bool berhasil atau tidak, supaya editor tetap terbuka saat isiannya ditolak */
    public function simpanSesuaian(?string $teks, AlurNaskah $alur, ?string $label = null): bool
    {
        return $this->jalankan(fn () => $alur->sesuaikan($this->baris, auth()->user(), $teks, $label));
    }

    public function usulHapus(AlurNaskah $alur): void
    {
        $this->jalankan(fn () => $alur->usulHapus($this->baris, auth()->user()));
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
            // Lampiran wajib ada: tombol hapus hanya untuk lampiran tambahan.
            'lampiranTambahan' => $this->baris->bagian === 'lampiran' && $alur->lampiranTambahan($this->baris),
            'sisipanSaya' => $alur->sisipanPdTerbuka($this->baris, auth()->user()),
        ]);
    }
}
