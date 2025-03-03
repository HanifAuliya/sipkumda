<?php

namespace App\Exports;

use App\Models\RancanganProdukHukum;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;


use Carbon\Carbon;

class SingleJenisExport implements FromCollection, WithHeadings, WithTitle, WithColumnWidths, WithStyles, WithColumnFormatting, WithCustomStartCell
{
    protected $tahun;
    protected $jenis;

    public function __construct($tahun, $jenis)
    {
        $this->tahun = $tahun;
        $this->jenis = $jenis;
    }

    public function collection()
    {
        return RancanganProdukHukum::with(['user', 'revisi.user', 'fasilitasi.notaDinas', 'dokumentasi']) // Tambah relasi fasilitasi
            ->when($this->tahun !== "", function ($query) {
                return $query->whereYear('tanggal_pengajuan', $this->tahun);
            })
            ->where('jenis_rancangan', $this->jenis)
            ->get()
            ->flatMap(function ($data) {
                if ($data->revisi->isEmpty()) {
                    return [[
                        'No Rancangan'       => $data->no_rancangan,
                        'Jenis'              => $data->jenis_rancangan,
                        'Tentang'            => $data->tentang,
                        'tanggal_pengajuan' => $data->tanggal_pengajuan ? Carbon::parse($data->tanggal_pengajuan)->format('Y/m/d') : '-',
                        'Perangkat Daerah'   => optional($data->user->PerangkatDaerah)->nama_perangkat_daerah ?? 'Tidak Ada',
                        // 'Perangkat Daerah'   => optional($data->user)->nama_user ?? 'Tidak Ada',

                        'Rancangan'          => $data->rancangan ? '=HYPERLINK("' . url('/view-private/rancangan/rancangan/' . basename($data->rancangan)) . '", "Lihat Rancangan")' : 'Tidak Ada',
                        'Matrik'             => $data->matrik ? '=HYPERLINK("' . url('/view-private/rancangan/matrik/' . basename($data->matrik)) . '", "Lihat Matrik")' : 'Tidak Ada',
                        'Bahan Pendukung'    => $data->bahan_pendukung ? '=HYPERLINK("' . url('/view-private/rancangan/bahan/' . basename($data->bahan_pendukung)) . '", "Lihat Bahan")' : 'Tidak Ada',

                        'Tanggal Peneliti Ditunjuk' => 'Belum Ditunjuk',
                        'Nama Peneliti'      => 'Belum Ditentukan',
                        'Tanggal Revisi'     => 'Belum Direvisi',
                        'Catatan Revisi'     => 'Tidak Ada Catatan',
                        'Revisi Rancangan'   => 'Belum Ada Revisi',
                        'Revisi Matrik'      => 'Belum Ada Revisi',

                        'tanggal_fasilitasi' => optional($data->fasilitasi)->tanggal_fasilitasi ? Carbon::parse($data->fasilitasi->tanggal_fasilitasi)->format('Y/m/d') : '-',
                        'Status Paraf Koordinasi' => optional($data->fasilitasi)->status_paraf_koordinasi ?? 'Belum',
                        'tanggal_paraf_koordinasi' => optional($data->fasilitasi)->tanggal_paraf_koordinasi ? Carbon::parse($data->fasilitasi->tanggal_paraf_koordinasi)->format('Y/m/d') : '-',
                        'Status Asisten' => optional($data->fasilitasi)->status_asisten ?? 'Belum',
                        'tanggal_asisten' => optional($data->fasilitasi)->tanggal_asisten ? Carbon::parse($data->fasilitasi->tanggal_asisten)->format('Y/m/d') : '-',
                        'Status Sekda' => optional($data->fasilitasi)->status_sekda ?? 'Belum',
                        'tanggal_sekda' => optional($data->fasilitasi)->tanggal_sekda ? Carbon::parse($data->fasilitasi->tanggal_sekda)->format('Y/m/d') : '-',
                        'Status Bupati' => optional($data->fasilitasi)->status_bupati ?? 'Belum',
                        'tanggal_bupati' => optional($data->fasilitasi)->tanggal_bupati ? Carbon::parse($data->fasilitasi->tanggal_bupati)->format('Y/m/d') : '-',

                        'Nomor Nota Dinas' => optional(optional($data->fasilitasi)->notaDinas)->nomor_nota ?? '-',

                        'Nomor Dokumen'      => optional($data->dokumentasi)->nomor_formatted ?? '-',
                        'Tahun Dokumen'      => optional($data->dokumentasi)->tahun ?? '-',
                        'Tanggal Penetapan' => optional($data->dokumentasi)->tanggal_penetapan ? Carbon::parse($data->dokumentasi->tanggal_penetapan)->format('Y/m/d') : '-',
                        'File Produk Hukum'  => optional($data->dokumentasi)->file_produk_hukum ? '=HYPERLINK("' . url('/view-private/produk-hukum/' . basename($data->dokumentasi->file_produk_hukum)) . '", "Lihat Produk Hukum")' : 'Tidak Ada',
                        'Nomor Tahun Berita Daerah' => optional($data->dokumentasi)->nomor_tahun_berita ?? '-',
                        'Tanggal Pengarsipan' => optional($data->dokumentasi)->tanggal_pengarsipan ? Carbon::parse($data->dokumentasi->tanggal_pengarsipan)->format('Y/m/d') : '-',

                    ]];
                }

                return $data->revisi->map(function ($revisi) use ($data) {
                    return [
                        'No Rancangan'       => $data->no_rancangan,
                        'Jenis'              => $data->jenis_rancangan,
                        'Tentang'            => $data->tentang,
                        'tanggal_pengajuan' => $data->tanggal_pengajuan ? Carbon::parse($data->tanggal_pengajuan)->format('Y/m/d') : '-',
                        // 'Perangkat Daerah'   => optional($data->user)->nama_user ?? 'Tidak Ada',
                        'Perangkat Daerah'   => optional($data->user->PerangkatDaerah)->nama_perangkat_daerah ?? 'Tidak Ada',

                        'Rancangan'          => $data->rancangan ? '=HYPERLINK("' . url('/view-private/rancangan/rancangan/' . basename($data->rancangan)) . '", "Lihat Rancangan")' : 'Tidak Ada',
                        'Matrik'             => $data->matrik ? '=HYPERLINK("' . url('/view-private/rancangan/matrik/' . basename($data->matrik)) . '", "Lihat Matrik")' : 'Tidak Ada',
                        'Bahan Pendukung'    => $data->bahan_pendukung ? '=HYPERLINK("' . url('/view-private/rancangan/bahan/' . basename($data->bahan_pendukung)) . '", "Lihat Bahan")' : 'Tidak Ada',

                        'tanggal_peneliti_ditunjuk' => $revisi->tanggal_peneliti_ditunjuk ? Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->format('Y/m/d') : '-',
                        'Nama Peneliti'      => optional($revisi->user)->nama_user ?? 'Belum Ditentukan',
                        'tanggal_revisi' => $revisi->tanggal_revisi ? Carbon::parse($revisi->tanggal_revisi)->format('Y/m/d') : '-',
                        'Catatan Revisi'     => $revisi->catatan_revisi ?? 'Tidak Ada Catatan',

                        'Revisi Rancangan'   => $revisi->revisi_rancangan ? '=HYPERLINK("' . url('/view-private/revisi/rancangan/' . basename($revisi->revisi_rancangan)) . '", "Lihat Revisi Rancangan")' : 'Tidak Ada',
                        'Revisi Matrik'      => $revisi->revisi_matrik ? '=HYPERLINK("' . url('/view-private/revisi/matrik/' . basename($revisi->revisi_matrik)) . '", "Lihat Revisi Matrik")' : 'Tidak Ada',

                        'tanggal_fasilitasi' => optional($data->fasilitasi)->tanggal_fasilitasi ? Carbon::parse($data->fasilitasi->tanggal_fasilitasi)->format('Y/m/d') : '-',
                        'Status Paraf Koordinasi' => optional($data->fasilitasi)->status_paraf_koordinasi ?? 'Belum',
                        'tanggal_paraf_koordinasi' => optional($data->fasilitasi)->tanggal_paraf_koordinasi ? Carbon::parse($data->fasilitasi->tanggal_paraf_koordinasi)->format('Y/m/d') : '-',
                        'Status Asisten' => optional($data->fasilitasi)->status_asisten ?? 'Belum',
                        'tanggal_asisten' => optional($data->fasilitasi)->tanggal_asisten ? Carbon::parse($data->fasilitasi->tanggal_asisten)->format('Y/m/d') : '-',
                        'Status Sekda' => optional($data->fasilitasi)->status_sekda ?? 'Belum',
                        'tanggal_sekda' => optional($data->fasilitasi)->tanggal_sekda ? Carbon::parse($data->fasilitasi->tanggal_sekda)->format('Y/m/d') : '-',
                        'Status Bupati' => optional($data->fasilitasi)->status_bupati ?? 'Belum',
                        'tanggal_bupati' => optional($data->fasilitasi)->tanggal_bupati ? Carbon::parse($data->fasilitasi->tanggal_bupati)->format('Y/m/d') : '-',

                        'Nomor Nota Dinas' => optional(optional($data->fasilitasi)->notaDinas)->nomor_nota ?? '-',

                        'Nomor Dokumen'      => optional($data->dokumentasi)->nomor_formatted ?? '-',
                        'Tahun Dokumen'      => optional($data->dokumentasi)->tahun ?? '-',
                        'Tanggal Penetapan' => optional($data->dokumentasi)->tanggal_penetapan ? Carbon::parse($data->dokumentasi->tanggal_penetapan)->format('Y/m/d') : '-',
                        'File Produk Hukum'  => optional($data->dokumentasi)->file_produk_hukum ? '=HYPERLINK("' . url('/view-private/produk-hukum/' . basename($data->dokumentasi->file_produk_hukum)) . '", "Lihat Produk Hukum")' : 'Tidak Ada',
                        'Nomor Tahun Berita Daerah' => optional($data->dokumentasi)->nomor_tahun_berita ?? '-',
                        'Tanggal Pengarsipan' => optional($data->dokumentasi)->tanggal_pengarsipan ? Carbon::parse($data->dokumentasi->tanggal_pengarsipan)->format('Y/m/d') : '-',

                    ];
                });
            });
    }

    public function startCell(): string
    {
        return 'A2'; // Data akan dimulai dari sel A3
    }

    // 🔹 Judul kolom dalam file Excel
    public function headings(): array
    {
        return [
            'No Rancangan',
            'Jenis',
            'Tentang',
            'Tanggal Pengajuan',
            'Perangkat Daerah',
            'Rancangan',
            'Matrik',
            'Bahan Pendukung',
            'Tanggal Peneliti Ditunjuk',
            'Nama Peneliti',
            'Tanggal Revisi',
            'Catatan Revisi',
            'Revisi Rancangan',
            'Revisi Matrik',
            'Tanggal Fasilitasi',
            'Status Paraf Koordinasi',
            'Tanggal Paraf Koordinasi',
            'Status Asisten',
            'Tanggal Asisten',
            'Status Sekda',
            'Tanggal Sekda',
            'Status Bupati',
            'Tanggal Bupati',
            'Nomor Nita Dinas Dikeluarkan',
            'Nomor Produk Hukum',
            'Tahun Produk Hukum',
            'Tanggal Penetepan',
            'File Produk Hukum',
            'Nomor Tahun Berita Daerah',
            'Tanggal Pengarsipan',
        ];
    }


    // 🔹 Judul Sheet
    public function title(): string
    {
        return "Data " . $this->jenis;
    }

    // 🔹 Atur Lebar Kolom
    public function columnWidths(): array
    {
        return [
            'A' => 15, // No Rancangan
            'B' => 20, // Jenis Rancangan
            'C' => 50, // Tentang
            'D' => 20, // Tanggal Pengajuan
            'E' => 30, // Perangkat Daerah
            'F' => 20, // Status Berkas
            'G' => 20, // Status Rancangan
            'H' => 20, // Status Rancangan
            'I' => 20, // Status Rancangan
            'J' => 20, // Status Rancangan
            'K' => 20, // Status Rancangan
            'L' => 20, // Status Rancangan
            'M' => 20, // Status Rancangan
            'N' => 20, // Status Rancangan
            'O' => 20, // Status Rancangan
            'P' => 20, // Status Rancangan
            'Q' => 20, // Status Rancangan
            'R' => 20, // Status Rancangan
            'S' => 20, // Status Rancangan
            'T' => 20, // Status Rancangan
            'U' => 20, // Status Rancangan
            'V' => 20, // Status Rancangan
            'W' => 20, // Status Rancangan
            'X' => 30, // Status Rancangan
            'Y' => 25, // Status Rancangan
            'Z' => 20, // Status Rancangan
            'AA' => 20, // Status Rancangan
            'AB' => 20, // Status Rancangan
            'AC' => 20, // Status Rancangan
            'AD' => 20, // Status Rancangan
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => '[$-id-ID]dd mmmm yyyy', // Tanggal Pengajuan
            'I' => '[$-id-ID]dd mmmm yyyy', // Tanggal Pengajuan
            'J' => '[$-id-ID]dd mmmm yyyy', // Tanggal Peneliti Ditunjuk
            'K' => '[$-id-ID]dd mmmm yyyy', // Tanggal Revisi
            'O' => '[$-id-ID]dd mmmm yyyy', // Tanggal Fasilitasi
            'Q' => '[$-id-ID]dd mmmm yyyy', // Tanggal Paraf Koordinasi
            'S' => '[$-id-ID]dd mmmm yyyy', // Tanggal Asisten
            'U' => '[$-id-ID]dd mmmm yyyy', // Tanggal Sekda
            'W' => '[$-id-ID]dd mmmm yyyy', // Tanggal Bupati
            'AA' => '[$-id-ID]dd mmmm yyyy', // Tanggal Publikasi
            'J' => NumberFormat::FORMAT_TEXT, // Rancangan (Link)
            'K' => NumberFormat::FORMAT_TEXT, // Rancangan (Link)
            'L' => NumberFormat::FORMAT_TEXT, // Matrik (Link)
            'M' => NumberFormat::FORMAT_TEXT, // Bahan Pendukung (Link)
            'N' => NumberFormat::FORMAT_TEXT, // Bahan Pendukung (Link)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // 🔹 Merge cells untuk header kategori di baris ke-1
        $sheet->mergeCells('A1:H1');  // Tahap Rancangan Produk Hukum
        $sheet->mergeCells('I1:N1');  // Tahap Revisi/Penelitian
        $sheet->mergeCells('O1:X1');  // Tahap Fasilitasi Produk Hukum
        $sheet->mergeCells('Y1:AD1'); // Tahap Dokumentasi Produk Hukum

        // 🔹 Menuliskan judul kategori pada baris ke-1
        $sheet->setCellValue('A1', 'TAHAP RANCANGAN PRODUK HUKUM');
        $sheet->setCellValue('I1', 'TAHAP REVISI/PENELITIAN');
        $sheet->setCellValue('O1', 'TAHAP FASILITASI PRODUK HUKUM');
        $sheet->setCellValue('Y1', 'TAHAP DOKUMENTASI PRODUK HUKUM');

        return [
            // 🔹 Warna berbeda untuk setiap kategori (Baris 1)
            'A1:H1' => [ // Warna Biru
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'], // Biru
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'I1:N1' => [ // Warna Hijau
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47'], // Hijau
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'O1:X1' => [ // Warna Orange
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'ED7D31'], // Orange
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'Y1:AD1' => [ // Warna Ungu
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '8064A2'], // Ungu
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],

            // 🔹 Warna untuk heading (Baris 2), disamakan dengan warna kategori di atasnya
            'A2:H2' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Teks putih
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F81BD'], // Sama dengan kategori Rancangan (Biru)
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'I2:N2' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Teks putih
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '70AD47'], // Sama dengan kategori Revisi (Hijau)
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'O2:X2' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Teks putih
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'ED7D31'], // Sama dengan kategori Fasilitasi (Orange)
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],
            'Y2:AD2' => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], // Teks putih
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '8064A2'], // Sama dengan kategori Dokumentasi (Ungu)
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                ],
            ],

            // 🔸 Border hanya untuk sel data mulai baris ke-3
            "A1:{$highestColumn}{$highestRow}" => [
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'], // Warna border hitam
                    ],
                ],
            ],

            // 🔸 Rata Tengah untuk beberapa kolom
            'A'  => ['alignment' => ['horizontal' => 'center']],
            // 🔹 Wrap Text untuk kolom C
            'C' => [
                'alignment' => [
                    'wrapText' => true, // Mengaktifkan wrap text
                    'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP, // Menyesuaikan teks ke atas
                ],
            ],

            // 🔹 Hyperlink (F-H dan M-N) diberi warna biru & garis bawah miring mulai baris ke-3
            "F3:H{$highestRow}" => [
                'font' => [
                    'underline' => Font::UNDERLINE_SINGLE,
                    'color' => ['rgb' => '0000FF'], // Warna biru
                    'italic' => true, // Garis bawah miring
                ],
            ],
            "M3:N{$highestRow}" => [
                'font' => [
                    'underline' => Font::UNDERLINE_SINGLE,
                    'color' => ['rgb' => '0000FF'], // Warna biru
                    'italic' => true, // Garis bawah miring
                ],
            ],
        ];
    }
}
