<?php

namespace App\Support\Wa;

/** Nomor WhatsApp disimpan sebagai 62xxx; pengguna boleh mengetik 0812..., +62 812-..., atau 812.... */
class NomorWa
{
    public const CONTOH = '081234567890';

    public static function normal(?string $nomor): ?string
    {
        $angka = preg_replace('/\D/', '', (string) $nomor);

        $angka = match (true) {
            str_starts_with($angka, '0') => '62' . substr($angka, 1),
            str_starts_with($angka, '8') => '62' . $angka,
            default => $angka,
        };

        return preg_match('/^628\d{7,12}$/', $angka) ? $angka : null;
    }

    /** Untuk ditampilkan: 0812-3456-7890. */
    public static function tampil(?string $nomor): string
    {
        if (blank($nomor)) {
            return '-';
        }

        $lokal = '0' . substr($nomor, 2);

        return implode('-', array_filter([substr($lokal, 0, 4), substr($lokal, 4, 4), substr($lokal, 8)]));
    }

    /** Aturan validasi untuk isian nomor WA yang boleh kosong. */
    public static function aturan(): array
    {
        return ['nullable', 'string', 'max:25', function (string $kolom, $nilai, \Closure $gagal) {
            if (filled($nilai) && self::normal($nilai) === null) {
                $gagal('Nomor WhatsApp tidak valid. Contoh: ' . self::CONTOH . '.');
            }
        }];
    }
}
