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

    /** Pengelompokan untuk menu: dokumen masih rancangan, atau sudah melewatinya. */
    public const RANCANGAN = [self::Draf, self::PersetujuanBerkas, self::PilihPeneliti, self::Penelitian, self::PerbaikanPd, self::ValidasiRancangan];

    public const SETELAH_RANCANGAN = [self::Disetujui, self::PersetujuanFasilitasi, self::ValidasiFasilitasi, self::PerbaikanFasilitasi, self::CekPeneliti, ...self::TERKUNCI];

    /** Tamu mencatat tahapan tanda tangan, jadi hanya melihat naskah sejak tahap itu. */
    public const TERLIHAT_TAMU = [self::Penandatanganan, self::Arsip, self::Selesai];

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

    /** Penjelasan posisi naskah untuk kartu daftar. */
    public function keterangan(): string
    {
        return match ($this) {
            self::Draf => 'Rancangan masih disusun Perangkat Daerah dan belum diajukan.',
            self::PersetujuanBerkas => 'Menunggu persetujuan. Admin sedang memeriksa berkas yang diajukan.',
            self::PilihPeneliti => 'Menunggu dipilihkan peneliti untuk rancangan ini.',
            self::Penelitian => 'Rancangan sedang dalam proses penelitian.',
            self::PerbaikanPd => 'Peneliti meminta Perangkat Daerah menyesuaikan rumusan.',
            self::ValidasiRancangan => 'Hasil penelitian menunggu validasi Verifikator.',
            self::Disetujui => 'Rancangan disetujui. Perangkat Daerah dapat mengajukan fasilitasi.',
            self::PersetujuanFasilitasi => 'Pengajuan fasilitasi menunggu persetujuan Peneliti.',
            self::ValidasiFasilitasi => 'Hasil fasilitasi menunggu validasi Verifikator.',
            self::PerbaikanFasilitasi => 'Perangkat Daerah diminta memperbaiki hasil fasilitasi.',
            self::CekPeneliti => 'Perbaikan fasilitasi menunggu pengecekan Peneliti.',
            self::Final => 'Fasilitasi selesai. Nota dinas menunggu diterbitkan.',
            self::Penandatanganan => 'Sedang dalam proses paraf dan tanda tangan basah.',
            self::Arsip => 'Naskah bertanda tangan menunggu diarsipkan.',
            self::Selesai => 'Naskah selesai dan sudah masuk arsip.',
        };
    }

    /** Tahap yang berada setelah tahap ini, untuk tab riwayat. */
    public function sesudahnya(): array
    {
        $semua = self::cases();

        return array_slice($semua, array_search($this, $semua, true) + 1);
    }

    /** Peran utama yang memegang giliran pada tahap ini. */
    public function peran(): string
    {
        return match ($this) {
            self::Draf, self::PerbaikanPd, self::Disetujui, self::PerbaikanFasilitasi => 'Perangkat Daerah',
            self::PersetujuanBerkas, self::Final,
            self::Penandatanganan, self::Arsip, self::Selesai => 'Admin',
            self::PilihPeneliti, self::ValidasiRancangan, self::ValidasiFasilitasi => 'Verifikator',
            self::Penelitian, self::PersetujuanFasilitasi, self::CekPeneliti => 'Peneliti',
        };
    }

    /** Semua peran yang boleh bertindak; nota dinas dan arsip juga oleh Verifikator, tahapan tanda tangan juga oleh Tamu. */
    public function pemegang(): array
    {
        return match ($this) {
            self::Final, self::Arsip => ['Admin', 'Verifikator'],
            self::Penandatanganan => ['Admin', 'Tamu'],
            self::Selesai => [],
            default => [$this->peran()],
        };
    }

    /**
     * Warna badge tahap: tiap langkah punya warna sendiri supaya posisi naskah terbaca sekilas.
     * Perbaikan (dikembalikan ke Perangkat Daerah) selalu oranye; disetujui dan selesai hijau.
     */
    public function warna(): string
    {
        return match ($this) {
            self::Draf => 'slate',
            self::PersetujuanBerkas => 'sky',
            self::PilihPeneliti, self::Penelitian => 'teal',
            self::PerbaikanPd, self::PerbaikanFasilitasi => 'warning',
            self::ValidasiRancangan, self::ValidasiFasilitasi => 'violet',
            self::Disetujui, self::Selesai => 'success',
            self::PersetujuanFasilitasi, self::CekPeneliti => 'primary',
            // Bukan amber: sudah dipakai badge Keputusan Bupati di kartu yang sama.
            self::Final => 'info',
            self::Penandatanganan => 'fuchsia',
            self::Arsip => 'indigo',
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

    /** Isi sudah dikunci dan masih ada yang dikerjakan: nota dinas, tanda tangan, atau arsip. */
    public function tahapAkhir(): bool
    {
        return in_array($this, [self::Final, self::Penandatanganan, self::Arsip], true);
    }

    /** Nama tombol pembuka naskah bagi yang sedang mendapat giliran. */
    public function labelKerjakan(): string
    {
        return match ($this) {
            self::Final => 'Terbitkan Nota Dinas',
            self::Penandatanganan => 'Catat Tanda Tangan',
            self::Arsip => 'Arsipkan',
            default => 'Kerjakan',
        };
    }

    /** Ikon tombol pembuka naskah, sesuai labelKerjakan(). */
    public function ikonKerjakan(): string
    {
        return match ($this) {
            self::Final => 'file-earmark-text',
            self::Penandatanganan => 'vector-pen',
            self::Arsip => 'archive',
            default => 'pencil-square',
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
