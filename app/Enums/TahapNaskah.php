<?php

namespace App\Enums;

/** Posisi naskah dalam alur, sekaligus penentu siapa yang memegang giliran. */
enum TahapNaskah: string
{
    case Draf = 'draf';
    case PersetujuanBerkas = 'persetujuan_berkas';
    case PilihPeneliti = 'pilih_peneliti';
    case Penelitian = 'penelitian';
    case PerbaikanPd = 'perbaikan_pd';
    case ValidasiRancangan = 'validasi_rancangan';
    case Disetujui = 'disetujui';
    case PersetujuanFasilitasi = 'persetujuan_fasilitasi';
    case ValidasiFasilitasi = 'validasi_fasilitasi';
    case PerbaikanFasilitasi = 'perbaikan_fasilitasi';
    case CekPeneliti = 'cek_peneliti';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draf => 'Penyusunan',
            self::PersetujuanBerkas => 'Persetujuan Berkas',
            self::PilihPeneliti => 'Menunggu Peneliti',
            self::Penelitian => 'Penelitian',
            self::PerbaikanPd => 'Perbaikan PD',
            self::ValidasiRancangan => 'Validasi Rancangan',
            self::Disetujui => 'Rancangan Disetujui',
            self::PersetujuanFasilitasi => 'Persetujuan Fasilitasi',
            self::ValidasiFasilitasi => 'Validasi Fasilitasi',
            self::PerbaikanFasilitasi => 'Perbaikan Fasilitasi',
            self::CekPeneliti => 'Pengecekan Peneliti',
            self::Final => 'Naskah Final',
        };
    }

    /** Peran yang memegang giliran pada tahap ini. */
    public function peran(): string
    {
        return match ($this) {
            self::Draf, self::PerbaikanPd, self::Disetujui, self::PerbaikanFasilitasi => 'Perangkat Daerah',
            self::PersetujuanBerkas, self::PersetujuanFasilitasi, self::Final => 'Admin',
            self::PilihPeneliti, self::ValidasiRancangan, self::ValidasiFasilitasi => 'Verifikator',
            self::Penelitian, self::CekPeneliti => 'Peneliti',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Draf => 'slate',
            self::PerbaikanPd, self::PerbaikanFasilitasi => 'warning',
            self::Penelitian, self::CekPeneliti => 'info',
            self::Disetujui, self::Final => 'success',
            default => 'primary',
        };
    }

    /** Nomor langkah pada penanda progres (lihat self::LANGKAH). */
    public function langkah(): int
    {
        return match ($this) {
            self::Draf, self::PersetujuanBerkas => 0,
            self::PilihPeneliti, self::Penelitian, self::PerbaikanPd => 1,
            self::ValidasiRancangan => 2,
            self::Disetujui, self::PersetujuanFasilitasi, self::ValidasiFasilitasi,
            self::PerbaikanFasilitasi, self::CekPeneliti => 3,
            self::Final => 4,
        };
    }

    public const LANGKAH = ['Penyusunan', 'Penelitian', 'Validasi', 'Fasilitasi', 'Final'];

    /** Tahap yang menunggu tindakan suatu peran. Naskah final sudah tidak menunggu siapa pun. */
    public static function menungguPeran(string $peran): array
    {
        return array_values(array_filter(self::cases(), fn (self $t) => $t !== self::Final && $t->peran() === $peran));
    }
}
