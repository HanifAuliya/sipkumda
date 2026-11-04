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
        // Selesai / berhasil
        'Disetujui' => 'success',
        'Diterima' => 'success',
        'Direvisi' => 'success',
        'Selesai' => 'success',
        'Aktif' => 'success',

        // Ditolak / belum dimulai
        'Ditolak' => 'danger',
        'Belum Tahap Revisi' => 'danger',
        'Belum Tahap Validasi' => 'danger',
        'Tidak Aktif' => 'danger',
        'Belum' => 'danger',

        // Menunggu pihak lain
        'Menunggu Peneliti' => 'info',

        // Sedang berjalan
        'Menunggu Persetujuan' => 'warning',
        'Menunggu Validasi' => 'warning',
        'Proses Revisi' => 'warning',
        'Dalam Proses' => 'warning',

        // Jenis produk hukum
        'Peraturan Bupati' => 'primary',
        'Surat Keputusan' => 'amber',
        'Keputusan Bupati' => 'amber',
    ];

    public static function color(?string $value): string
    {
        return self::COLORS[$value] ?? 'slate';
    }
}
