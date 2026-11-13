<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Periode untuk Laporan & Pemantauan: setahun, semester, triwulan, bulan, atau rentang tanggal sendiri.
 * Tanpa tahun dan tanpa periode berarti semua waktu.
 */
final class PeriodeLaporan
{
    public const PILIHAN = [
        '' => 'Setahun penuh',
        's1' => 'Semester I', 's2' => 'Semester II',
        't1' => 'Triwulan I', 't2' => 'Triwulan II', 't3' => 'Triwulan III', 't4' => 'Triwulan IV',
        'b1' => 'Januari', 'b2' => 'Februari', 'b3' => 'Maret', 'b4' => 'April', 'b5' => 'Mei', 'b6' => 'Juni',
        'b7' => 'Juli', 'b8' => 'Agustus', 'b9' => 'September', 'b10' => 'Oktober', 'b11' => 'November', 'b12' => 'Desember',
        'rentang' => 'Rentang tanggal',
    ];

    private function __construct(
        public readonly ?Carbon $mulai,
        public readonly ?Carbon $akhir,
        public readonly string $label,
        public readonly ?int $tahun,
    ) {}

    public static function dari(string $tahun, string $periode, string $dari = '', string $sampai = ''): self
    {
        $periode = array_key_exists($periode, self::PILIHAN) ? $periode : '';

        if ($periode === 'rentang') {
            $mulai = self::tanggal($dari)?->startOfDay();
            $akhir = self::tanggal($sampai)?->endOfDay();
            $teks = fn (Carbon $c) => $c->translatedFormat('j M Y');
            $label = match (true) {
                $mulai && $akhir => $teks($mulai) . ' – ' . $teks($akhir),
                (bool) $mulai => 'Sejak ' . $teks($mulai),
                (bool) $akhir => 'Sampai ' . $teks($akhir),
                default => 'Semua waktu',
            };

            return new self($mulai, $akhir, $label, null);
        }

        if (! ctype_digit($tahun)) {
            if ($periode === '') {
                return new self(null, null, 'Semua waktu', null);
            }
            $tahun = (string) now()->year;
        }

        $t = (int) $tahun;
        [$bulanAwal, $bulanAkhir] = match (true) {
            $periode === 's1' => [1, 6],
            $periode === 's2' => [7, 12],
            str_starts_with($periode, 't') => [((int) substr($periode, 1) - 1) * 3 + 1, (int) substr($periode, 1) * 3],
            str_starts_with($periode, 'b') => [(int) substr($periode, 1), (int) substr($periode, 1)],
            default => [1, 12],
        };

        return new self(
            Carbon::create($t, $bulanAwal, 1)->startOfDay(),
            Carbon::create($t, $bulanAkhir, 1)->endOfMonth(),
            $periode === '' ? "Tahun {$t}" : self::PILIHAN[$periode] . " {$t}",
            $t,
        );
    }

    /** [mulai, akhir] (salah satunya boleh null), atau null untuk semua waktu. */
    public function rentang(): ?array
    {
        return $this->mulai || $this->akhir ? [$this->mulai, $this->akhir] : null;
    }

    /** Tahun bila periodenya tepat satu tahun penuh (untuk tautan ke Dokumentasi yang hanya bisa disaring per tahun). */
    public function tahunPenuh(): ?int
    {
        return $this->tahun && $this->mulai?->isSameDay(Carbon::create($this->tahun, 1, 1))
            && $this->akhir?->isSameDay(Carbon::create($this->tahun, 12, 31)) ? $this->tahun : null;
    }

    private static function tanggal(string $nilai): ?Carbon
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) && strtotime($nilai) ? Carbon::parse($nilai) : null;
    }
}
