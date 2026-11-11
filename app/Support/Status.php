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
    /**
     * Nama di database => nama resmi yang ditampilkan. Alur lama dan sebagian arsip
     * masih menyimpan "Surat Keputusan"; nilainya dibiarkan karena dipakai penomoran.
     */
    private const NAMA_JENIS = ['Surat Keputusan' => 'Keputusan Bupati'];

    /** Kode singkat jenis produk hukum, sama dengan awalan nomor rancangannya. */
    public const KODE_JENIS = ['Peraturan Bupati' => 'PB', 'Keputusan Bupati' => 'SK'];

    /** Warna garis penanda baris tabel per jenis, senada dengan kodenya. */
    public static function garisJenis(?string $jenis): string
    {
        return match (self::KODE_JENIS[self::label((string) $jenis)] ?? null) {
            'PB' => 'border-l-4 border-l-primary-400',
            'SK' => 'border-l-4 border-l-accent-500',
            default => 'border-l-4 border-l-transparent',
        };
    }

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
        'Keputusan Bupati' => 'amber',
    ];

    public static function color(?string $value): string
    {
        return self::COLORS[self::label($value)] ?? 'slate';
    }

    public static function label(?string $value): ?string
    {
        return self::NAMA_JENIS[$value ?? ''] ?? $value;
    }

    /** Semua nama di database untuk satu jenis, dipakai saat memfilter data. */
    public static function varianJenis(string $jenis): array
    {
        $label = self::label($jenis);

        return [$label, ...array_keys(self::NAMA_JENIS, $label, true)];
    }
}
