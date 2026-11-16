<?php

namespace App\Livewire\V2;

use App\Exports\LaporanLengkapExport;
use App\Models\PerangkatDaerah;
use App\Support\FormatDokumen;
use App\Support\LaporanNaskah;
use App\Support\PeriodeLaporan;
use Barryvdh\DomPDF\Facade\Pdf;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan & Pemantauan untuk Bagian Hukum, tiga tab:
 * - ringkasan  : angka utama, grafik per bulan dan per langkah, naskah tertahan, rata-rata per tahap;
 * - perangkat  : sebaran naskah per Perangkat Daerah dan langkah, bisa dibuka per PD;
 * - aktivitas  : catatan kegiatan semua naskah dari riwayat naskah.
 * Periode, jenis, dan Perangkat Daerah berlaku di semua tab, Unduh Excel, dan Unduh PDF.
 */
#[Layout('layouts.app')]
#[Title('Laporan & Pemantauan')]
class Laporan extends Component
{
    use WithPagination;

    public const TAB = ['ringkasan', 'perangkat', 'aktivitas'];

    public const PERAN = ['Perangkat Daerah', 'Peneliti', 'Verifikator', 'Admin', 'Tamu'];

    #[Url(except: 'ringkasan')]
    public string $tab = 'ringkasan';

    #[Url(except: '')]
    public string $tahun = '';

    /** Kunci PeriodeLaporan::PILIHAN; kosong = setahun penuh (atau semua waktu bila tahun kosong). */
    #[Url(except: '')]
    public string $periode = '';

    #[Url(except: '')]
    public string $dari = '';

    #[Url(except: '')]
    public string $sampai = '';

    #[Url(except: '')]
    public string $jenis = '';

    #[Url(except: '')]
    public string $pd = '';

    /** Tab Perangkat Daerah: ikut tampilkan PD yang belum pernah mengajukan. */
    #[Url(except: false)]
    public bool $semuaPd = false;

    // Tab Aktivitas
    #[Url(except: '')]
    public string $peran = '';

    #[Url(except: false)]
    public bool $rumusan = false;

    public function updated($properti): void
    {
        $this->resetPage('halamanAktivitas');
    }

    public function updatedPeriode(string $nilai): void
    {
        // Periode dalam tahun tanpa tahun terpilih: pakai tahun ini.
        if ($nilai !== '' && $nilai !== 'rentang' && $this->tahun === '') {
            $this->tahun = (string) now()->year;
        }
    }

    /** Nilai dari URL dibatasi ke pilihan yang ada. */
    private function filter(): array
    {
        $jenis = in_array($this->jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true) ? $this->jenis : null;
        $pd = ctype_digit($this->pd) ? (int) $this->pd : null;

        return [PeriodeLaporan::dari($this->tahun, $this->periode, $this->dari, $this->sampai), $jenis, $pd];
    }

    private function namaBerkas(PeriodeLaporan $periode, ?string $jenis, ?string $namaPd, string $ekstensi): string
    {
        $nama = 'Laporan Naskah ' . $periode->label . ($jenis ? " {$jenis}" : '') . ($namaPd ? " {$namaPd}" : '');

        return str_replace(['/', '\\', ':', '–'], '-', $nama) . ".{$ekstensi}";
    }

    public function unduhExcel()
    {
        [$periode, $jenis, $pd] = $this->filter();
        $namaPd = $pd ? PerangkatDaerah::find($pd)?->nama_perangkat_daerah : null;

        return Excel::download(new LaporanLengkapExport($periode, $jenis, $pd, $namaPd), $this->namaBerkas($periode, $jenis, $namaPd, 'xlsx'));
    }

    public function unduhPdf()
    {
        [$periode, $jenis, $pd] = $this->filter();
        $namaPd = $pd ? PerangkatDaerah::find($pd)?->nama_perangkat_daerah : null;
        $data = LaporanNaskah::susun($periode->rentang(), $jenis, $pd);
        $f = FormatDokumen::nota();

        $pdf = Pdf::loadView('pdf.laporan', [
            'periode' => $periode->label,
            'jenis' => $jenis,
            'namaPd' => $namaPd,
            'ringkasan' => $data['ringkasan'],
            'ditetapkan' => $data['ditetapkan'],
            'matriks' => LaporanNaskah::matriksPd($data['baris'])->filter(fn ($b) => $b['total'] > 0),
            'tertahan' => $data['tertahan'],
            'batas' => $data['batas'],
            'perTahap' => $data['perTahap'],
            'logo' => $f['kop']['tampil'] ? FormatDokumen::pathLogoNota($f) : null,
            'kop' => $f['kop']['tampil'] ? array_values(array_filter($f['kop']['baris'], fn ($b) => filled($b['teks'] ?? null))) : [],
            'pencetak' => auth()->user()->nama_user,
        ])->setPaper('A4', 'portrait');

        return response()->streamDownload(fn () => print($pdf->output()), $this->namaBerkas($periode, $jenis, $namaPd, 'pdf'));
    }

    public function render()
    {
        if (! in_array($this->tab, self::TAB, true)) {
            $this->tab = 'ringkasan';
        }
        if (! in_array($this->peran, self::PERAN, true)) {
            $this->peran = '';
        }
        if (! array_key_exists($this->periode, PeriodeLaporan::PILIHAN)) {
            $this->periode = '';
        }

        [$periode, $jenis, $pd] = $this->filter();
        $rentang = $periode->rentang();
        $data = [
            'labelPeriode' => $periode->label,
            'tahunPenuh' => $periode->tahunPenuh(),
            'daftarTahun' => LaporanNaskah::daftarTahun(),
            'daftarPd' => PerangkatDaerah::orderBy('nama_perangkat_daerah')->get(['id', 'nama_perangkat_daerah']),
            'peranList' => self::PERAN,
        ];

        if ($this->tab === 'aktivitas') {
            return view('v2.laporan', $data + [
                'aktivitas' => LaporanNaskah::aktivitas($jenis, $pd, $this->peran ?: null, $rentang, $this->rumusan),
            ]);
        }

        $susun = LaporanNaskah::susun($rentang, $jenis, $pd);

        if ($this->tab === 'perangkat') {
            $susun['matriks'] = LaporanNaskah::matriksPd($susun['baris'], $this->semuaPd && ! $pd);
        } else {
            $susun['grafik'] = LaporanNaskah::grafik($rentang, $jenis, $pd, $susun['baris']);
        }

        return view('v2.laporan', $data + $susun);
    }
}
