<?php

namespace App\Support;

/**
 * Pemetaan nilai status/jenis ke warna badge.
 *
 * Satu sumber kebenaran untuk warna status di seluruh aplikasi, dipakai oleh
 * komponen <x-ui.status-badge>.
 */
class Status
{
    private const COLORS = [
        'Aktif' => 'success',
        'Tidak Aktif' => 'danger',

        // Jenis produk hukum
        'Peraturan Bupati' => 'primary',
        'Keputusan Bupati' => 'amber',
    ];

    public static function color(?string $value): string
    {
        return self::COLORS[self::label($value)] ?? 'slate';
    }

    /** Nama yang ditampilkan; sama dengan yang tersimpan. */
    public static function label(?string $value): ?string
    {
        return $value;
    }

    /** Nilai di database untuk satu jenis, dipakai saat memfilter data. */
    public static function varianJenis(string $jenis): array
    {
        return [$jenis];
    }
}
