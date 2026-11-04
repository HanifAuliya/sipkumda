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
    case Penandatanganan = 'penandatanganan';
    case Arsip = 'arsip';
    case Selesai = 'selesai';

    public const LANGKAH = ['Penyusunan', 'Penelitian', 'Validasi', 'Fasilitasi', 'Penandatanganan', 'Selesai'];

    /** Tahap ketika isi naskah sudah dikunci dan yang berjalan hanya proses di luar sistem. */
    public const TERKUNCI = [self::Final, self::Penandatanganan, self::Arsip, self::Selesai];

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
            self::Final => 'Menunggu Nota Dinas',
            self::Penandatanganan => 'Penandatanganan',
            self::Arsip => 'Menunggu Arsip',
            self::Selesai => 'Selesai',
        };
    }

    /** Peran utama yang memegang giliran pada tahap ini. */
    public function peran(): string
    {
        return match ($this) {
            self::Draf, self::PerbaikanPd, self::Disetujui, self::PerbaikanFasilitasi => 'Perangkat Daerah',
            self::PersetujuanBerkas, self::PersetujuanFasilitasi, self::Final,
            self::Penandatanganan, self::Arsip, self::Selesai => 'Admin',
            self::PilihPeneliti, self::ValidasiRancangan, self::ValidasiFasilitasi => 'Verifikator',
            self::Penelitian, self::CekPeneliti => 'Peneliti',
        };
    }

    /** Semua peran yang boleh bertindak; nota dinas dan arsip bisa oleh Admin maupun Verifikator. */
    public function pemegang(): array
    {
        return match ($this) {
            self::Final, self::Arsip => ['Admin', 'Verifikator'],
            self::Selesai => [],
            default => [$this->peran()],
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Draf => 'slate',
            self::PerbaikanPd, self::PerbaikanFasilitasi => 'warning',
            self::Penelitian, self::CekPeneliti => 'info',
            self::Disetujui, self::Selesai => 'success',
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
            self::Final, self::Penandatanganan, self::Arsip => 4,
            self::Selesai => 5,
        };
    }

    public function terkunci(): bool
    {
        return in_array($this, self::TERKUNCI, true);
    }

    /** Tahap yang menunggu tindakan suatu peran. */
    public static function menungguPeran(string $peran): array
    {
        return array_values(array_filter(self::cases(), fn (self $t) => in_array($peran, $t->pemegang(), true)));
    }
}
