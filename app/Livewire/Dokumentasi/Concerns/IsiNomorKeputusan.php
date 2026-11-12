<?php

namespace App\Livewire\Dokumentasi\Concerns;

use App\Support\FormatDokumen;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;

/**
 * Isian nomor Keputusan Bupati yang berformat, mis. [140] / [nomor] / [412.1.6] / TAHUN 2025.
 * Pola dipecah menjadi potongan; bagian teks terisi dan bisa diganti, nomor urut dan tahun memakai
 * properti "nomor" dan "tahun" milik komponen. Peraturan Bupati tidak berpola (Nomor 1 Tahun 2025).
 */
trait IsiNomorKeputusan
{
    /** Pola yang sedang dipecah; kosong = tanpa pola. Diisi server, tidak dari browser. */
    #[Locked]
    public string $polaDasar = '';

    /** Isi bagian teks pola per urutan potongannya, mis. [0 => '140', 4 => '412.1.6']. */
    public array $bagianNomor = [];

    /** Memecah pola untuk formulir; null untuk jenis tanpa pola. */
    protected function siapkanNomor(?string $pola): void
    {
        $this->polaDasar = (string) $pola;
        $this->bagianNomor = [];

        foreach ($this->potonganNomor() as $i => $potongan) {
            if ($potongan['jenis'] === 'teks') {
                $this->bagianNomor[$i] = $potongan['isi'];
            }
        }
    }

    protected function polaKeputusanBawaan(): string
    {
        return FormatDokumen::naskah(FormatDokumen::KEPBUP)['nomor_arsip'];
    }

    public function potonganNomor(): array
    {
        return filled($this->polaDasar) ? FormatDokumen::potonganNomorArsip($this->polaDasar) : [];
    }

    /** Pola dari isian sekarang, mis. "800/{nomor}/412.1.6/TAHUN {tahun}"; null bila tanpa pola. */
    protected function polaNomorTerisi(string $kunciGalat = 'nomor'): ?string
    {
        $potongan = $this->potonganNomor();

        if (! $potongan) {
            return null;
        }

        $pola = '';

        foreach ($potongan as $i => $bagian) {
            if ($bagian['jenis'] !== 'teks') {
                $pola .= $bagian['isi'];

                continue;
            }

            $isi = trim((string) ($this->bagianNomor[$i] ?? ''));

            if ($isi === '' || mb_strlen($isi) > 30 || ! preg_match('/^[A-Za-z0-9 .\-]+$/', $isi)) {
                throw ValidationException::withMessages([$kunciGalat => 'Bagian nomor wajib diisi; hanya huruf, angka, titik, dan tanda hubung, paling banyak 30 karakter.']);
            }

            $pola .= $isi;
        }

        return mb_strlen($pola) <= 100 ? $pola : throw ValidationException::withMessages([$kunciGalat => 'Susunan nomor terlalu panjang.']);
    }
}
