<?php

namespace App\Exports;

use App\Models\DokumentasiProdukHukum;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DokumentasiExport implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles
{
    protected $search;
    protected $jenisRancangan;
    protected $tahun;

    public function __construct($search, $jenisRancangan, $tahun)
    {
        $this->search = $search;
        $this->jenisRancangan = $jenisRancangan;
        $this->tahun = $tahun;
    }

    public function collection()
    {
        return DokumentasiProdukHukum::with(['rancangan', 'perangkatDaerah'])
            ->when($this->search, function ($query) {
                $query->whereHas('rancangan', function ($q) {
                    $q->where('tentang', 'like', '%' . $this->search . '%')
                        ->orWhere('no_rancangan', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->jenisRancangan, function ($query) {
                $query->whereHas('rancangan', function ($q) {
                    $q->whereYear('jenis_rancangan', $this->jenisRancangan);
                });
            })
            ->when($this->tahun, function ($q) { // Filter tahun dari dokumentasi_produk_hukum
                $q->where('tahun', $this->tahun);
            })
            ->get()
            ->map(function ($dokumentasi, $index) {
                return [
                    'No' => $index + 1,
                    'Nomor Produk Hukum' => $dokumentasi->nomor_formatted,
                    'Nomor Fasilitasi Rancangan' => $dokumentasi->rancangan->no_rancangan ?? 'Dokumen sebelum ada sistem',
                    'Jenis Produk Hukum' => $dokumentasi->jenis_dokumentasi,
                    'Tentang' => $dokumentasi->tentang_dokumentasi,
                    'Perangkat Daerah' => $dokumentasi->perangkatDaerah->nama_perangkat_daerah,
                    'File Rancangan' => $dokumentasi->file_produk_hukum ? '=HYPERLINK("' . url('/view-private/dokumentasi/file_produk_hukum/' . basename($dokumentasi->file_produk_hukum)) . '", "Lihat Produk Hukum")' : 'Tidak Ada',
                    'Tanggal Penetapan' => \Carbon\Carbon::parse($dokumentasi->tanggal_penetapan)->translatedFormat('d F Y'),
                    'Nomor Tahun Berita Daerah' => $dokumentasi->nomor_tahun_berita,
                    'Tanggal Pengarsipan' => \Carbon\Carbon::parse($dokumentasi->tanggal_pengarsipan)->translatedFormat('d F Y'),
                ];
            });
    }

    public function headings(): array
    {
        return [
            'No',
            'Nomor Produk Hukum',
            'Nomor Fasilitasi Rancangan',
            'Jenis Produk Hukum',
            'Tentang',
            'Perangkat Daerah',
            'File Rancangan',
            'Tanggal Penetapan',
            'Nomor Tahun Berita Daerah',
            'Tanggal Pengarsipan',
        ];
    }

    public function title(): string
    {
        return "Data Dokumentasi";
    }

    public function columnWidths(): array
    {
        return [
            'A' => 5,  // No
            'B' => 20, // Nomor Produk Hukum
            'C' => 25, // Nomor Fasilitasi
            'D' => 25, // Jenis Fasilitasi
            'E' => 40, // Tentang Fasilitasi
            'F' => 30, // Perangkat Daerah
            'G' => 20, // File Rancangan
            'H' => 30, // Tanggal Penetepan
            'I' => 20, // Nomor Berita Daerah
            'J' => 20, // Tanggal Pengarsipan
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        return [
            'A1:' . $highestColumn . '1' => [
                'font' => ['bold' => true, 'color' => ['rgb' => '000000']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'BDBDBD'],
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            "A1:{$highestColumn}{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ],
            // 🔹 Hyperlink (F-H dan M-N) diberi warna biru & garis bawah miring mulai baris ke-3
            "G2:G{$highestRow}" => [
                'font' => [
                    'underline' => Font::UNDERLINE_SINGLE,
                    'color' => ['rgb' => '0000FF'], // Warna biru
                    'italic' => true, // Garis bawah miring
                ],
            ],
        ];
    }
}
