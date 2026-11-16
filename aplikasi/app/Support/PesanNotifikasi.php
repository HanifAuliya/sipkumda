<?php

namespace App\Support;

use App\Enums\TahapNaskah as T;
use App\Models\Naskah;
use App\Models\User;

/**
 * Isi notifikasi naskah. Judulnya menyebut hal yang perlu dilakukan penerima,
 * bukan sekadar nama tahap, supaya bisa dipahami tanpa membuka naskahnya.
 */
class PesanNotifikasi
{
    /** Giliran penerima untuk mengerjakan naskah. */
    public const TUGAS = 'tugas';

    /** Naskah dikembalikan kepada penerima, biasanya beserta catatan. */
    public const KEMBALI = 'kembali';

    /** Sekadar kabar posisi naskah; penerima tidak perlu bertindak. */
    public const KABAR = 'kabar';

    public const SELESAI = 'selesai';

    /** Perpindahan yang mengembalikan naskah => judul untuk penerimanya. */
    private const DIKEMBALIKAN = [
        'persetujuan_berkas>draf' => 'Berkas dikembalikan, perlu dilengkapi',
        'penelitian>perbaikan_pd' => 'Peneliti meminta penyesuaian rumusan',
        'validasi_rancangan>penelitian' => 'Verifikator meminta penelitian ulang',
        'persetujuan_fasilitasi>disetujui' => 'Pengajuan fasilitasi dikembalikan',
        'validasi_fasilitasi>perbaikan_fasilitasi' => 'Verifikator meminta perbaikan fasilitasi',
        'cek_peneliti>perbaikan_fasilitasi' => 'Peneliti meminta perbaikan lagi',
        'final>validasi_fasilitasi' => 'Kunci naskah dibuka, perlu validasi ulang',
        'penandatanganan>validasi_fasilitasi' => 'Kunci naskah dibuka, perlu validasi ulang',
    ];

    /** Untuk peran yang kini memegang giliran. */
    public static function untukPemegang(Naskah $naskah, T $dari, User $pelaku, ?string $catatan): array
    {
        $tahap = $naskah->tahap;
        $dikembalikan = self::DIKEMBALIKAN["{$dari->value}>{$tahap->value}"] ?? null;

        $kategori = match (true) {
            $dikembalikan !== null => self::KEMBALI,
            $tahap === T::Selesai => self::SELESAI,
            default => self::TUGAS,
        };

        return self::susun($naskah, $kategori, $dikembalikan ?? self::judulTugas($tahap, $dari), $pelaku, $catatan);
    }

    /** Untuk pemrakarsa yang sedang tidak memegang giliran, supaya tetap tahu posisi naskahnya. */
    public static function untukPemrakarsa(Naskah $naskah, User $pelaku, ?string $catatan): array
    {
        return $naskah->tahap === T::Selesai
            ? self::susun($naskah, self::SELESAI, self::judulTugas(T::Selesai, T::Selesai), $pelaku, $catatan)
            : self::susun($naskah, self::KABAR, "Naskah masuk tahap {$naskah->tahap->label()}", $pelaku, $catatan);
    }

    /** Pengingat otomatis untuk naskah yang tertahan di satu tahap. */
    public static function pengingat(Naskah $naskah, int $hari): array
    {
        return [
            'kategori' => self::TUGAS,
            'title' => 'Pengingat: ' . self::judulTugas($naskah->tahap, $naskah->tahap),
            'message' => "{$naskah->nomor_tampil} · {$naskah->tentang}",
            'catatan' => "Naskah ini sudah {$hari} hari di tahap {$naskah->tahap->label()}.",
            'pelaku' => null,
            'tahap' => $naskah->tahap->value,
            'slug' => $naskah->slug,
        ];
    }

    /** Satu langkah paraf atau tanda tangan basah sudah dicatat. */
    public static function tahapanTtd(Naskah $naskah, User $pelaku, string $tahapan): array
    {
        return self::susun($naskah, self::KABAR, "{$tahapan} sudah selesai", $pelaku);
    }

    /**
     * Verifikator mengganti peneliti. Peneliti baru mendapat tugas bila sedang gilirannya,
     * selain itu sekadar kabar; peneliti lama diberi tahu bahwa naskah ini bukan tugasnya lagi.
     */
    public static function penelitiDiganti(Naskah $naskah, User $pelaku, ?string $catatan, bool $baru): array
    {
        if (! $baru) {
            return self::susun($naskah, self::KABAR, 'Anda tidak lagi menjadi peneliti naskah ini', $pelaku, $catatan);
        }

        return $naskah->tahap->peran() === 'Peneliti'
            ? self::susun($naskah, self::TUGAS, self::judulTugas($naskah->tahap, $naskah->tahap), $pelaku, $catatan)
            : self::susun($naskah, self::KABAR, 'Anda ditugaskan sebagai peneliti naskah ini', $pelaku, $catatan);
    }

    private static function judulTugas(T $tahap, T $dari): string
    {
        return match ($tahap) {
            T::Draf => 'Lanjutkan penyusunan rancangan',
            T::PersetujuanBerkas => 'Periksa kelengkapan berkas',
            T::PilihPeneliti => 'Pilih peneliti untuk rancangan ini',
            T::Penelitian => $dari === T::PerbaikanPd ? 'Teliti tindak lanjut Perangkat Daerah' : 'Anda ditugaskan meneliti rancangan',
            T::PerbaikanPd => 'Sesuaikan rumusan rancangan',
            T::ValidasiRancangan => 'Validasi hasil penelitian',
            T::Disetujui => 'Rancangan disetujui, ajukan fasilitasi',
            T::PersetujuanFasilitasi => 'Periksa pengajuan fasilitasi',
            T::ValidasiFasilitasi => 'Validasi hasil fasilitasi',
            T::PerbaikanFasilitasi => 'Perbaiki hasil fasilitasi',
            T::CekPeneliti => 'Cek perbaikan fasilitasi',
            T::Final => 'Terbitkan nota dinas',
            T::Penandatanganan => 'Catat paraf dan tanda tangan',
            T::Arsip => 'Arsipkan naskah bertanda tangan',
            T::Selesai => 'Naskah selesai dan masuk arsip',
        };
    }

    private static function susun(Naskah $naskah, string $kategori, string $judul, User $pelaku, ?string $catatan = null): array
    {
        return [
            'kategori' => $kategori,
            'title' => $judul,
            'message' => "{$naskah->nomor_tampil} · {$naskah->tentang}",
            'catatan' => filled($catatan) ? $catatan : null,
            'pelaku' => $pelaku->nama_user,
            'tahap' => $naskah->tahap->value,
            'slug' => $naskah->slug,
        ];
    }
}
