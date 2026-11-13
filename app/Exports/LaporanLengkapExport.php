<?php

namespace App\Exports;

use App\Enums\TahapNaskah as T;
use App\Support\KalimatRiwayat;
use App\Support\LaporanNaskah;
use App\Support\PeriodeLaporan;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * Excel Laporan & Pemantauan, sesuai periode dan filter di halaman: Ringkasan, Per Perangkat Daerah,
 * Daftar Naskah, dan Aktivitas.
 */
class LaporanLengkapExport implements Export, WithMultipleSheets
{
    /** Batas baris sheet Aktivitas supaya berkas tetap ringan. */
    public const BATAS_AKTIVITAS = 5000;

    public function __construct(
        private PeriodeLaporan $periode,
        private ?string $jenis,
        private ?int $pd,
        private ?string $namaPd,
    ) {}

    public function sheets(): array
    {
        $rentang = $this->periode->rentang();
        $data = LaporanNaskah::susun($rentang, $this->jenis, $this->pd);
        $judul = [
            ['Laporan Fasilitasi Produk Hukum Daerah'],
            ['Periode', $this->periode->label],
            ['Jenis', $this->jenis ?? 'Semua jenis'],
            ['Perangkat Daerah', $this->namaPd ?? 'Semua Perangkat Daerah'],
            ['Dicetak', now()->translatedFormat('j F Y H.i')],
            [],
        ];

        $r = $data['ringkasan'];
        $d = $data['ditetapkan'];
        $ringkasan = [
            ...$judul,
            ['Ringkasan', 'Jumlah'],
            ['Naskah diajukan', $r['diajukan']],
            ['  Peraturan Bupati', $r['peraturan']],
            ['  Keputusan Bupati', $r['keputusan']],
            ['Sedang berjalan', $r['berjalan']],
            ['Selesai (diarsipkan)', $r['selesai']],
            ['Rata-rata lama proses (hari)', $r['rata_proses'] ?? '-'],
            ['Produk hukum ditetapkan', $d['total']],
            ['  Peraturan Bupati', $d['peraturan']],
            ['  Keputusan Bupati', $d['keputusan']],
            ['  Lewat SIFKUMDA', $d['dari_sistem']],
            [],
            ['Tahap', 'Naskah sekarang', 'Rata-rata lama (hari)'],
            ...$data['perTahap']->map(fn ($t) => [$t['tahap']->label(), $t['sekarang'], $t['rata_hari'] ?? '-'])->all(),
            [],
            ["Tertahan lebih dari {$data['batas']} hari"],
            ['Nomor', 'Tentang', 'Tahap', 'Lama (hari)'],
            ...$data['tertahan']->map(fn ($b) => [$b['naskah']->nomor_tampil, $b['naskah']->tentang, $b['naskah']->tahap->label(), $b['lama_tahap']])->all(),
        ];

        $matriks = LaporanNaskah::matriksPd($data['baris'], ! $this->pd);
        $perPd = [
            ...$judul,
            ['Perangkat Daerah', ...T::LANGKAH, 'Total'],
            ...$matriks->map(fn ($b) => [$b['nama'], ...$b['jumlah'], $b['total']])->all(),
        ];

        $aktivitas = [
            ...$judul,
            ['Waktu', 'Pelaku', 'Peran', 'Kegiatan', 'Nomor', 'Tentang', 'Perangkat Daerah', 'Catatan'],
            ...LaporanNaskah::aktivitasQuery($this->jenis, $this->pd, null, $rentang, false)->limit(self::BATAS_AKTIVITAS)->get()
                ->map(fn ($a) => [
                    $a->created_at->format('Y-m-d H:i'),
                    $a->user->nama_user ?? 'Sistem',
                    $a->user?->getRoleNames()->first() ?? '-',
                    KalimatRiwayat::untuk($a)['teks'],
                    $a->naskah?->nomor_tampil,
                    $a->naskah?->tentang,
                    $a->naskah?->pemrakarsa?->perangkatDaerah?->nama_perangkat_daerah ?? '-',
                    $a->aksi === 'tahapan_tanggal' ? null : $a->catatan,
                ])->all(),
        ];

        // Baris judul kolom ada setelah blok keterangan (6 baris).
        return [
            new LaporanLembar('Ringkasan', $ringkasan, [7]),
            new LaporanLembar('Per Perangkat Daerah', $perPd, [7]),
            new LaporanNaskahExport($rentang, $this->jenis, $this->pd),
            new LaporanLembar('Aktivitas', $aktivitas, [7]),
        ];
    }
}
