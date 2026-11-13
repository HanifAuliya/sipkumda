<?php

namespace App\Exports;

use App\Enums\TahapNaskah as T;
use App\Exports\Concerns\MencegahFormulaInjection;
use App\Support\LaporanNaskah;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Daftar naskah beserta tahap dan lama prosesnya, sesuai filter di halaman Laporan. */
class LaporanNaskahExport implements FromCollection, WithHeadings, WithTitle, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    use MencegahFormulaInjection;

    public function __construct(private ?int $tahun, private ?string $jenis, private ?int $pd = null) {}

    public function collection(): Collection
    {
        return LaporanNaskah::baris($this->tahun, $this->jenis, $this->pd)->map(fn (array $b) => [
            $b['naskah']->nomor_tampil,
            $b['naskah']->jenis,
            $b['naskah']->tentang,
            $b['naskah']->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $b['naskah']->pemrakarsa->nama_user ?? '-',
            $b['naskah']->tahap->label(),
            LaporanNaskah::formatTanggal($b['naskah']->diajukan_at),
            $b['naskah']->tahap === T::Selesai ? '-' : $b['lama_tahap'],
            LaporanNaskah::formatTanggal($b['selesai_at']),
            $b['lama_proses'] ?? '-',
        ]);
    }

    public function headings(): array
    {
        return ['Nomor', 'Jenis', 'Tentang', 'Perangkat Daerah', 'Tahap', 'Diajukan', 'Hari di Tahap Ini', 'Selesai', 'Lama Proses (hari)'];
    }

    public function title(): string
    {
        return 'Laporan Naskah';
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
