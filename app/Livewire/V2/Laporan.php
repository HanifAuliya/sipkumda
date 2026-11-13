<?php

namespace App\Livewire\V2;

use App\Exports\LaporanNaskahExport;
use App\Models\PerangkatDaerah;
use App\Support\LaporanNaskah;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Laporan & Pemantauan untuk Bagian Hukum, tiga tab:
 * - ringkasan  : jumlah, lama proses, naskah tertahan, rata-rata per tahap;
 * - perangkat  : sebaran naskah per Perangkat Daerah dan langkah, bisa dibuka per PD;
 * - aktivitas  : catatan kegiatan semua naskah dari riwayat naskah.
 * Filter jenis dan Perangkat Daerah berlaku di semua tab.
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

    #[Url(except: '')]
    public string $dari = '';

    #[Url(except: '')]
    public string $sampai = '';

    #[Url(except: false)]
    public bool $rumusan = false;

    public function updated($properti): void
    {
        if (in_array($properti, ['jenis', 'pd', 'peran', 'dari', 'sampai', 'rumusan', 'tab'], true)) {
            $this->resetPage('halamanAktivitas');
        }
    }

    public function pilihPd(string $id): void
    {
        $this->pd = $id;
        $this->tab = 'ringkasan';
        $this->resetPage('halamanAktivitas');
    }

    /** Nilai dari URL dibatasi ke pilihan yang ada. */
    private function filter(): array
    {
        $tahun = ctype_digit($this->tahun) ? (int) $this->tahun : null;
        $jenis = in_array($this->jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true) ? $this->jenis : null;
        $pd = ctype_digit($this->pd) ? (int) $this->pd : null;

        return [$tahun, $jenis, $pd];
    }

    private function tanggal(string $nilai): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $nilai) && strtotime($nilai) ? $nilai : null;
    }

    public function unduhExcel()
    {
        [$tahun, $jenis, $pd] = $this->filter();
        $namaPd = $pd ? PerangkatDaerah::find($pd)?->nama_perangkat_daerah : null;
        $nama = 'Laporan Naskah' . ($tahun ? " {$tahun}" : '') . ($jenis ? " {$jenis}" : '') . ($namaPd ? " {$namaPd}" : '') . '.xlsx';

        return Excel::download(new LaporanNaskahExport($tahun, $jenis, $pd), str_replace(['/', '\\'], '-', $nama));
    }

    public function render()
    {
        if (! in_array($this->tab, self::TAB, true)) {
            $this->tab = 'ringkasan';
        }
        if (! in_array($this->peran, self::PERAN, true)) {
            $this->peran = '';
        }

        [$tahun, $jenis, $pd] = $this->filter();
        $data = [
            'daftarTahun' => LaporanNaskah::daftarTahun(),
            'daftarPd' => PerangkatDaerah::orderBy('nama_perangkat_daerah')->get(['id', 'nama_perangkat_daerah']),
            'peranList' => self::PERAN,
        ];

        if ($this->tab === 'aktivitas') {
            return view('v2.laporan', $data + [
                'aktivitas' => LaporanNaskah::aktivitas($jenis, $pd, $this->peran ?: null, $this->tanggal($this->dari), $this->tanggal($this->sampai), $this->rumusan),
            ]);
        }

        $susun = LaporanNaskah::susun($tahun, $jenis, $pd);

        if ($this->tab === 'perangkat') {
            $susun['matriks'] = LaporanNaskah::matriksPd(LaporanNaskah::baris($tahun, $jenis, $pd), $this->semuaPd && ! $pd);
            $susun['idPd'] = $data['daftarPd']->pluck('id', 'nama_perangkat_daerah');
        }

        return view('v2.laporan', $data + $susun);
    }
}
