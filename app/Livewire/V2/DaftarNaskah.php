<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah as T;
use App\Models\Naskah;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Satu halaman daftar untuk semua menu v2, mengikuti bentuk menu lama: kartu dengan tab
 * menunggu/riwayat, atau tabel untuk menu Daftar. Detail dibuka lewat modal (DetailNaskah).
 * Hak lihat tetap dari Naskah::terlihatOleh.
 */
#[Layout('layouts.app')]
class DaftarNaskah extends Component
{
    use WithPagination;

    private const AKHIR_FASILITASI = [T::Disetujui, T::PersetujuanFasilitasi, T::ValidasiFasilitasi, T::PerbaikanFasilitasi, T::CekPeneliti, T::Final, T::Penandatanganan, T::Arsip];

    /** "tab" berisi [ikon, label] untuk tab menunggu dan riwayat; tanpa "tab" berarti tampilan tabel. */
    public const KELOMPOK = [
        // Draf (belum diajukan atau dikembalikan untuk dilengkapi) dipisah supaya tidak dikira sudah terkirim.
        'rancanganku' => ['judul' => 'Rancanganku', 'keterangan' => 'Pengajuan rancangan produk hukum perangkat daerah', 'ikon' => 'clipboard2', 'tahap' => T::RANCANGAN,
            'tab' => [['send', 'Sedang Diajukan'], ['check2-circle', 'Pengajuan Diterima']], 'draf' => ['pencil-square', 'Draf']],
        'rancangan' => ['judul' => 'Daftar Rancangan', 'keterangan' => 'Pengajuan rancangan produk hukum', 'ikon' => 'clipboard2-data', 'tahap' => T::RANCANGAN],
        'persetujuan-berkas' => ['judul' => 'Persetujuan Berkas', 'keterangan' => 'Pengajuan baru yang menunggu pemeriksaan kelengkapan', 'ikon' => 'clipboard2-check', 'tahap' => [T::PersetujuanBerkas],
            'tab' => [['hourglass-split', 'Menunggu Persetujuan'], ['clock-history', 'Riwayat Persetujuan']]],
        'pilih-peneliti' => ['judul' => 'Pilih Peneliti', 'keterangan' => 'Rancangan yang menunggu penugasan peneliti', 'ikon' => 'person-check', 'tahap' => [T::PilihPeneliti],
            'tab' => [['hourglass-split', 'Menunggu Peneliti'], ['clock-history', 'Riwayat Penugasan']]],
        'penelitian' => ['judul' => 'Penelitian Rancangan', 'keterangan' => 'Rancangan produk hukum yang ditugaskan kepada Anda untuk diteliti', 'ikon' => 'pencil-square', 'tahap' => [T::Penelitian],
            'tab' => [['hourglass-split', 'Menunggu Penelitian'], ['clock-history', 'Riwayat Penelitian']]],
        'validasi-rancangan' => ['judul' => 'Validasi Rancangan', 'keterangan' => 'Hasil penelitian yang menunggu validasi', 'ikon' => 'patch-check', 'tahap' => [T::ValidasiRancangan],
            'tab' => [['hourglass-split', 'Menunggu Validasi'], ['clock-history', 'Riwayat Validasi']]],
        'fasilitasiku' => ['judul' => 'Fasilitasiku', 'keterangan' => 'Fasilitasi produk hukum perangkat daerah', 'ikon' => 'files', 'tahap' => self::AKHIR_FASILITASI,
            'tab' => [['send', 'Fasilitasi Berlangsung'], ['check2-circle', 'Riwayat Fasilitasi']]],
        'fasilitasi' => ['judul' => 'Daftar Fasilitasi', 'keterangan' => 'Semua naskah pada tahap fasilitasi dan sesudahnya', 'ikon' => 'collection', 'tahap' => T::SETELAH_RANCANGAN],
        'persetujuan-fasilitasi' => ['judul' => 'Persetujuan Fasilitasi', 'keterangan' => 'Pengajuan fasilitasi yang menunggu persetujuan', 'ikon' => 'file-check', 'tahap' => [T::PersetujuanFasilitasi],
            'tab' => [['hourglass-split', 'Menunggu Persetujuan'], ['clock-history', 'Riwayat Persetujuan']]],
        'cek-peneliti' => ['judul' => 'Pengecekan Fasilitasi', 'keterangan' => 'Perbaikan dari Perangkat Daerah yang perlu Anda cek', 'ikon' => 'ui-checks', 'tahap' => [T::CekPeneliti],
            'tab' => [['hourglass-split', 'Menunggu Pengecekan'], ['clock-history', 'Riwayat Pengecekan']]],
        'validasi-fasilitasi' => ['judul' => 'Validasi Fasilitasi', 'keterangan' => 'Hasil fasilitasi yang menunggu validasi', 'ikon' => 'file-earmark-check', 'tahap' => [T::ValidasiFasilitasi],
            'tab' => [['hourglass-split', 'Menunggu Validasi'], ['clock-history', 'Riwayat Validasi']]],
        'nota-dinas' => ['judul' => 'Daftar Nota Dinas', 'keterangan' => 'Nota dinas fasilitasi rancangan produk hukum', 'ikon' => 'file-text', 'tahap' => [T::Final],
            'tab' => [['hourglass-split', 'Menunggu Terbit'], ['check2-circle', 'Sudah Terbit']]],
        'penandatanganan' => ['judul' => 'Penandatanganan', 'keterangan' => 'Pencatatan paraf dan tanda tangan basah', 'ikon' => 'vector-pen', 'tahap' => [T::Penandatanganan],
            'tab' => [['hourglass-split', 'Sedang Berjalan'], ['check2-circle', 'Sudah Selesai']]],
    ];

    /**
     * Menu tempat tahap ini dikerjakan, sebagai [label, url]. Tombol kerja hanya tampil di menu itu (dan
     * Giliran Anda di Dashboard); di tab riwayat menu lain, naskah yang sedang giliran Anda diberi
     * tautan ke menu ini supaya "Terbitkan Nota Dinas" tidak muncul di, misalnya, Pilih Peneliti.
     */
    public static function menuKerja(T $tahap): ?array
    {
        if ($tahap === T::Arsip) {
            return ['Dokumentasi', route('dokumentasi.main', ['tab' => 'menunggu'])];
        }

        $kelompok = match ($tahap) {
            T::Draf, T::PerbaikanPd => 'rancanganku',
            T::PersetujuanBerkas => 'persetujuan-berkas',
            T::PilihPeneliti => 'pilih-peneliti',
            T::Penelitian => 'penelitian',
            T::ValidasiRancangan => 'validasi-rancangan',
            T::Disetujui, T::PerbaikanFasilitasi => 'fasilitasiku',
            T::PersetujuanFasilitasi => 'persetujuan-fasilitasi',
            T::CekPeneliti => 'cek-peneliti',
            T::ValidasiFasilitasi => 'validasi-fasilitasi',
            T::Final => 'nota-dinas',
            T::Penandatanganan => 'penandatanganan',
            T::Arsip, T::Selesai => null,
        };

        return $kelompok ? [
            self::KELOMPOK[$kelompok]['judul'],
            route('daftar', ['kelompok' => $kelompok, ...($tahap === T::Draf ? ['tab' => 'draf'] : [])]),
        ] : null;
    }

    #[Locked]
    public string $kelompok;

    #[Url(as: 'tab', except: 'menunggu')]
    public string $tabAktif = 'menunggu';

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $tahap = '';

    /** Peraturan Bupati | Keputusan Bupati; kosong = semua. */
    #[Url(except: '')]
    public string $jenis = '';

    public int $perPage = 10;

    public function mount(string $kelompok): void
    {
        abort_unless(isset(self::KELOMPOK[$kelompok]), 404);
        $this->kelompok = $kelompok;
    }

    public function pilihTab(string $tab): void
    {
        $this->tabAktif = in_array($tab, ['riwayat', 'draf'], true) ? $tab : 'menunggu';
        $this->resetPage();
    }

    public function updated(string $nama): void
    {
        if (in_array($nama, ['search', 'tahap', 'jenis', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    private function dasar()
    {
        return Naskah::terlihatOleh(auth()->user());
    }

    public function render()
    {
        $info = self::KELOMPOK[$this->kelompok];
        $menunggu = $info['tahap'];
        $riwayat = isset($info['tab']) ? end($menunggu)->sesudahnya() : [];
        $draf = isset($info['draf']) ? [T::Draf] : [];
        if ($draf) {
            $menunggu = array_values(array_filter($menunggu, fn (T $t) => $t !== T::Draf));
        }
        if ($this->tabAktif === 'draf' && ! $draf || $this->tabAktif === 'riwayat' && ! isset($info['tab'])) {
            $this->tabAktif = 'menunggu';
        }
        $tahapTab = match ($this->tabAktif) {
            'riwayat' => $riwayat,
            'draf' => $draf,
            default => $menunggu,
        };

        $halaman = $this->dasar()
            ->with(['pemrakarsa.perangkatDaerah', 'peneliti'])
            ->whereIn('tahap', isset($info['tab']) ? $tahapTab : $menunggu)
            ->when($this->search !== '', fn ($q) => $q->where(fn ($s) => $s
                ->where('tentang', 'like', "%{$this->search}%")
                ->orWhere('nomor', 'like', "%{$this->search}%")))
            ->when(T::tryFrom($this->tahap), fn ($q, T $tahap) => $q->where('tahap', $tahap))
            ->when(in_array($this->jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true), fn ($q) => $q->where('jenis', $this->jenis))
            ->latest('updated_at')
            ->paginate(max(3, min(50, $this->perPage)));

        return view('v2.daftar-naskah', [
            'info' => $info,
            'halaman' => $halaman,
            'jumlah' => isset($info['tab']) ? array_filter([
                'draf' => $draf ? $this->dasar()->whereIn('tahap', $draf)->count() : null,
                'menunggu' => $this->dasar()->whereIn('tahap', $menunggu)->count(),
                'riwayat' => $this->dasar()->whereIn('tahap', $riwayat)->count(),
            ], fn ($n) => $n !== null) : [],
            // [kunci => [ikon, label]] sesuai urutan tampil.
            'daftarTab' => isset($info['tab']) ? array_filter([
                'draf' => $info['draf'] ?? null,
                'menunggu' => $info['tab'][0],
                'riwayat' => $info['tab'][1],
            ]) : [],
            'bisaBuat' => $this->kelompok === 'rancanganku' && Gate::allows('create', Naskah::class),
        ])->title($info['judul']);
    }
}
