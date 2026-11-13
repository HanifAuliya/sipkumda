<?php

namespace App\Support;

use App\Enums\TahapNaskah as T;
use App\Models\NaskahBaris as B;
use App\Models\NaskahRiwayat;

/**
 * Kalimat untuk satu catatan riwayat naskah, mis. "menerbitkan nota dinas", dipakai di halaman
 * Laporan & Pemantauan (tab Aktivitas). Perpindahan tahap yang umum diberi kata kerja sendiri,
 * sisanya ditulis "memindahkan naskah: A → B".
 */
class KalimatRiwayat
{
    /** @return array{teks: string, ikon: string, warna: string} warna: primary | success | danger | slate */
    public static function untuk(NaskahRiwayat $r): array
    {
        return match ($r->aksi) {
            'tahap' => self::tahap($r->sebelum, $r->sesudah),
            'tahapan' => ['teks' => "mencatat {$r->sesudah} selesai", 'ikon' => 'vector-pen', 'warna' => 'success'],
            'tahapan_tanggal' => ['teks' => "mengubah tanggal {$r->catatan}", 'ikon' => 'calendar3', 'warna' => 'slate'],
            'tahapan_batal' => ['teks' => "membatalkan tanda {$r->sebelum}", 'ikon' => 'x-circle', 'warna' => 'danger'],
            'ganti_peneliti' => ['teks' => 'mengganti peneliti: ' . ($r->sebelum ?? '-') . " → {$r->sesudah}", 'ikon' => 'person-check', 'warna' => 'slate'],
            'terbit_ulang_nota' => ['teks' => 'menerbitkan ulang nota dinas', 'ikon' => 'arrow-repeat', 'warna' => 'slate'],
            'tambah_baris' => ['teks' => 'menambah baris ' . (self::sasaran($r) ?? $r->sesudah), 'ikon' => 'plus-lg', 'warna' => 'slate'],
            'buang_baris' => ['teks' => "menghapus baris sisipan {$r->sesudah}", 'ikon' => 'dash-lg', 'warna' => 'slate'],
            default => ['teks' => 'menandai ' . (self::sasaran($r) ?? 'baris') . ': ' . (B::LABEL_STATUS[json_decode((string) $r->sesudah, true)['status'] ?? 'belum'] ?? 'diubah'),
                'ikon' => 'pencil-square', 'warna' => 'slate'],
        };
    }

    private static function tahap(?string $dari, ?string $ke): array
    {
        $label = fn (T $t) => $t->label();

        return match (true) {
            $ke === $label(T::PersetujuanBerkas) && $dari === $label(T::Draf) => ['teks' => 'mengajukan rancangan', 'ikon' => 'send', 'warna' => 'primary'],
            $ke === $label(T::Penelitian) && $dari === $label(T::PilihPeneliti) => ['teks' => 'menugaskan peneliti', 'ikon' => 'person-check', 'warna' => 'primary'],
            $ke === $label(T::Disetujui) => ['teks' => 'menyetujui rancangan', 'ikon' => 'patch-check', 'warna' => 'success'],
            $ke === $label(T::PersetujuanFasilitasi) && $dari === $label(T::Disetujui) => ['teks' => 'mengajukan fasilitasi', 'ikon' => 'send', 'warna' => 'primary'],
            $ke === $label(T::Final) && $dari === $label(T::ValidasiFasilitasi) => ['teks' => 'menyetujui fasilitasi (naskah final)', 'ikon' => 'patch-check', 'warna' => 'success'],
            $ke === $label(T::Penandatanganan) && $dari === $label(T::Final) => ['teks' => 'menerbitkan nota dinas', 'ikon' => 'file-earmark-text', 'warna' => 'primary'],
            $ke === $label(T::PilihPeneliti) && $dari === $label(T::PersetujuanBerkas) => ['teks' => 'menyetujui berkas pengajuan', 'ikon' => 'clipboard2-check', 'warna' => 'success'],
            $ke === $label(T::ValidasiRancangan) => ['teks' => 'menyelesaikan penelitian, diteruskan ke Verifikator', 'ikon' => 'check2-all', 'warna' => 'primary'],
            $ke === $label(T::Penelitian) && $dari === $label(T::PerbaikanPd) => ['teks' => 'mengirim tindak lanjut perbaikan ke Peneliti', 'ikon' => 'send', 'warna' => 'primary'],
            $ke === $label(T::ValidasiFasilitasi) && $dari === $label(T::PersetujuanFasilitasi) => ['teks' => 'menyetujui kelengkapan berkas fasilitasi', 'ikon' => 'file-check', 'warna' => 'success'],
            $ke === $label(T::ValidasiFasilitasi) && $dari === $label(T::CekPeneliti) => ['teks' => 'selesai mengecek perbaikan fasilitasi', 'ikon' => 'check2-all', 'warna' => 'primary'],
            $ke === $label(T::CekPeneliti) => ['teks' => 'mengirim perbaikan fasilitasi ke Peneliti', 'ikon' => 'send', 'warna' => 'primary'],
            $ke === $label(T::Arsip) && $dari === $label(T::Penandatanganan) => ['teks' => 'melengkapi tahapan tanda tangan; menunggu arsip', 'ikon' => 'vector-pen', 'warna' => 'success'],
            $ke === $label(T::PerbaikanPd) => ['teks' => 'meminta Perangkat Daerah menyesuaikan rumusan', 'ikon' => 'chat-left-text', 'warna' => 'slate'],
            $ke === $label(T::Selesai) => ['teks' => 'mengarsipkan ke JDIH', 'ikon' => 'archive', 'warna' => 'success'],
            self::mundur($dari, $ke) => ['teks' => "mengembalikan naskah: {$dari} → {$ke}", 'ikon' => 'arrow-return-left', 'warna' => 'danger'],
            default => ['teks' => "memindahkan naskah: {$dari} → {$ke}", 'ikon' => 'arrow-right', 'warna' => 'slate'],
        };
    }

    /** Pindah ke langkah yang lebih awal, mis. dikembalikan ke Perangkat Daerah. */
    private static function mundur(?string $dari, ?string $ke): bool
    {
        $cari = fn (?string $label) => collect(T::cases())->first(fn (T $t) => $t->label() === $label);
        [$a, $b] = [$cari($dari), $cari($ke)];

        return $a && $b && ($b->langkah() < $a->langkah() || in_array($b, [T::PerbaikanFasilitasi, T::Draf], true));
    }

    private static function sasaran(NaskahRiwayat $r): ?string
    {
        return $r->baris ? ($r->baris->label ?: (TemplateNaskah::BAGIAN[$r->baris->bagian] ?? null)) : null;
    }
}
