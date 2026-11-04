<?php

namespace App\Support;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use Illuminate\Support\Collection;

/**
 * Memecah baris matriks menjadi bagian-bagian yang dikerjakan satu per satu:
 * Menimbang, Mengingat, dan seterusnya; Batang Tubuh dipecah per BAB.
 * Bagian yang terlalu panjang dibagi lagi supaya halaman tetap ringan.
 */
class BlokNaskah
{
    public const MAKS_BARIS = 25;

    /**
     * @param  Collection<int, NaskahBaris>  $baris  urut menurut kolom urutan
     * @return list<array{kunci: string, bagian: string, nama: string, sub: ?string, anak: bool, ids: list<int>}>
     */
    public static function susun(Naskah $naskah, Collection $baris): array
    {
        $hasil = [];

        foreach (TemplateNaskah::bagian($naskah->jenis) as $bagian => $namaBagian) {
            $isi = $baris->where('bagian', $bagian)->values();

            if ($isi->isEmpty()) {
                continue;
            }

            if ($bagian === 'batang_tubuh') {
                array_push($hasil, ...self::batangTubuh($isi));

                continue;
            }

            foreach ($isi->chunk(self::MAKS_BARIS)->values() as $i => $potongan) {
                $hasil[] = [
                    'kunci' => $i === 0 ? $bagian : "{$bagian}-{$potongan->first()->id}",
                    'bagian' => $bagian,
                    'nama' => $i === 0 ? $namaBagian : "{$namaBagian} (lanjutan)",
                    'sub' => null,
                    'anak' => false,
                    'ids' => $potongan->pluck('id')->values()->all(),
                ];
            }
        }

        return $hasil;
    }

    /** Baris sebelum BAB pertama, atau naskah tanpa BAB, dinamai menurut rentang pasalnya. */
    private static function batangTubuh(Collection $isi): array
    {
        $kelompok = [];

        foreach ($isi as $item) {
            if ($kelompok === [] || preg_match('/^BAB\b/i', (string) $item->label)) {
                $kelompok[] = collect();
            }

            $kelompok[array_key_last($kelompok)]->push($item);
        }

        $hasil = [];

        foreach ($kelompok as $anggota) {
            $bab = preg_match('/^BAB\b/i', (string) $anggota->first()->label) ? $anggota->first() : null;

            foreach ($anggota->chunk(self::MAKS_BARIS)->values() as $i => $potongan) {
                $hasil[] = [
                    'kunci' => 'bt-'.$potongan->first()->id,
                    'bagian' => 'batang_tubuh',
                    'nama' => match (true) {
                        $bab !== null && $i === 0 => $bab->label,
                        $bab !== null => "{$bab->label} (lanjutan)",
                        default => self::rentang($potongan),
                    },
                    'sub' => $bab ? self::judulBab($bab) : null,
                    'anak' => true,
                    'ids' => $potongan->pluck('id')->values()->all(),
                ];
            }
        }

        return $hasil;
    }

    private static function judulBab(NaskahBaris $bab): ?string
    {
        $teks = trim(strtok((string) ($bab->cuplikan ?? $bab->rumusan_awal), "\n") ?: '');

        return $teks !== '' ? $teks : null;
    }

    private static function rentang(Collection $potongan): string
    {
        $label = $potongan->pluck('label')->filter();

        return match (true) {
            $label->isEmpty() => TemplateNaskah::BAGIAN['batang_tubuh'],
            $label->count() === 1 => $label->first(),
            default => $label->first().' – '.$label->last(),
        };
    }
}
