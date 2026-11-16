<?php

namespace App\Livewire\V2\Concerns;

use App\Services\AlurNaskah;

/**
 * Menandai, mengubah tanggal, dan membatalkan tahapan tanda tangan dari daftar tahapan
 * (v2/partials/tahapan-ttd). Dipakai layar kerja dan halaman pantau Tamu; komponennya punya $naskah.
 */
trait KelolaTahapanTtd
{
    public ?string $tahapanDipilih = null;

    /** tandai | ubah */
    public string $modeTahapan = 'tandai';

    public string $tanggalTtd = '';

    public function bukaTahapan(string $kunci, string $mode): void
    {
        abort_unless(isset(AlurNaskah::tahapanTtd()[$kunci]) && in_array($mode, ['tandai', 'ubah'], true), 404);
        abort_unless(app(AlurNaskah::class)->bolehKelolaTtd($this->naskah->fresh(), auth()->user()), 403);

        $this->tahapanDipilih = $kunci;
        $this->modeTahapan = $mode;
        $this->tanggalTtd = $mode === 'ubah'
            ? ($this->naskah->tahapan_ttd[$kunci] ?? now()->toDateString())
            : now()->toDateString();
        $this->resetValidation();
        $this->dispatch('openModal', 'modalTahapan');
    }

    public function simpanTahapan(): void
    {
        abort_unless($this->tahapanDipilih, 400);
        $this->resetValidation();
        $alur = app(AlurNaskah::class);
        $nama = AlurNaskah::tahapanTtd()[$this->tahapanDipilih];

        $this->naskah = $this->modeTahapan === 'ubah'
            ? $alur->ubahTanggalTahapan($this->naskah, auth()->user(), $this->tahapanDipilih, $this->tanggalTtd)
            : $alur->tandaiTahapan($this->naskah, auth()->user(), $this->tanggalTtd, $this->tahapanDipilih);

        $this->dispatch('closeModal', 'modalTahapan');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => $this->modeTahapan === 'ubah' ? "Tanggal {$nama} diperbarui." : "{$nama} ditandai selesai."]);
        $this->reset('tahapanDipilih');
    }

    public function batalkanTahapan(string $kunci): void
    {
        $this->naskah = app(AlurNaskah::class)->batalkanTahapan($this->naskah, auth()->user(), $kunci);

        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Tanda ' . AlurNaskah::tahapanTtd()[$kunci] . ' dibatalkan.']);
    }
}
