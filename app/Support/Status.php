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

    /** Warna titik penanda jenis produk hukum; null bila nilainya bukan jenis. */
    public static function titikJenis(?string $jenis): ?string
    {
        return match (self::label((string) $jenis)) {
            'Peraturan Bupati' => 'bg-primary-500',
            'Keputusan Bupati' => 'bg-accent-500',
            default => null,
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
