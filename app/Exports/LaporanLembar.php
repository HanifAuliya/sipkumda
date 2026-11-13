<?php

namespace App\Exports;

use App\Exports\Concerns\MencegahFormulaInjection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Satu sheet berisi baris apa adanya; baris pertama dan baris judul kolom dicetak tebal. */
class LaporanLembar implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    use MencegahFormulaInjection;

    public function __construct(private string $judul, private array $baris, private array $tebal = []) {}

    public function array(): array
    {
        return $this->baris;
    }

    public function title(): string
    {
        return $this->judul;
    }

    public function styles(Worksheet $sheet): array
    {
        return collect([1, ...$this->tebal])->mapWithKeys(fn ($n) => [$n => ['font' => ['bold' => true]]])->all();
    }
}
