<?php

namespace App\Support;

use App\Models\NaskahBaris;
use Illuminate\Support\HtmlString;

/**
 * Rumusan awal dan penyempurnaan satu baris untuk ditampilkan bertumpuk: di rumusan awal yang dihapus ditandai
 * <del>, di penyempurnaan yang ditambah ditandai <ins>. Tabel ditandai per sel.
 */
class BandingRumusan
{
    /**
     * Potongannya ['html' => HtmlString], atau tabel seperti TataLetakNaskah::potongan() dengan sel HtmlString.
     *
     * @return array{awal: list<array>, sempurna: list<array>, hapus: int, tambah: int}
     */
    public static function susun(NaskahBaris $baris): array
    {
        $awal = (string) $baris->rumusan_awal;
        $sempurna = match (true) {
            $baris->terhapus() => '',
            // Baris Tetap dan yang belum disempurnakan: tidak ada yang berubah.
            blank($baris->rumusan_penyempurnaan) || $baris->status === NaskahBaris::TETAP => $awal,
            default => (string) $baris->rumusan_penyempurnaan,
        };

        $a = self::potongan($awal);
        $b = self::potongan($sempurna);
        $hasil = ['awal' => [], 'sempurna' => [], ...($awal === $sempurna ? ['hapus' => 0, 'tambah' => 0] : DiffKata::jumlah($awal, $sempurna))];

        foreach (['teks', 'tabel'] as $jenis) {
            $x = array_filter($a, fn (array $p) => isset($p[$jenis]));
            $y = array_filter($b, fn (array $p) => isset($p[$jenis]));
            $kunciX = array_keys($x);
            $kunciY = array_keys($y);

            // Teks dipasangkan dengan teks dan tabel dengan tabel, menurut urutannya.
            for ($i = 0; $i < max(count($x), count($y)); $i++) {
                $lama = isset($kunciX[$i]) ? $x[$kunciX[$i]] : null;
                $baru = isset($kunciY[$i]) ? $y[$kunciY[$i]] : null;
                [$sisiLama, $sisiBaru] = $jenis === 'teks'
                    ? self::teks($lama['teks'] ?? '', $baru['teks'] ?? '')
                    : self::tabel($lama, $baru);

                if ($lama !== null) {
                    $hasil['awal'][$kunciX[$i]] = $sisiLama;
                }
                if ($baru !== null) {
                    $hasil['sempurna'][$kunciY[$i]] = $sisiBaru;
                }
            }
        }

        ksort($hasil['awal']);
        ksort($hasil['sempurna']);

        return [...$hasil, 'awal' => array_values($hasil['awal']), 'sempurna' => array_values($hasil['sempurna'])];
    }

    private static function potongan(string $teks): array
    {
        return $teks === '' ? [] : TataLetakNaskah::potongan($teks);
    }

    private static function teks(string $lama, string $baru): array
    {
        $html = $lama === $baru ? e($lama) : DiffKata::html($lama, $baru);

        return [
            ['html' => new HtmlString(DiffKata::sisi($html, true))],
            ['html' => new HtmlString(DiffKata::sisi($html, false))],
        ];
    }

    private static function tabel(?array $lama, ?array $baru): array
    {
        $gabung = BandingNaskah::gabungTabel($lama['tabel'] ?? [], $baru['tabel'] ?? []);

        $sisi = function (?array $asal, bool $sisiLama) use ($gabung): ?array {
            if ($asal === null) {
                return null;
            }

            $tag = $sisiLama ? '<ins>' : '<del>';
            $isi = [];

            foreach ($gabung as $sel) {
                $utuh = array_map('strval', $sel);
                $bersih = array_map(fn (string $s) => DiffKata::sisi($s, $sisiLama), $utuh);

                // Baris tabel yang hanya ada di sisi lain.
                if (implode('', $bersih) === '' && str_contains(implode('', $utuh), $tag)) {
                    continue;
                }

                $isi[] = array_map(fn (string $s) => new HtmlString($s), $bersih);
            }

            return [...$asal, 'tabel' => $isi];
        };

        return [$sisi($lama, true), $sisi($baru, false)];
    }
}
