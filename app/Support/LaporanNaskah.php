<?php

namespace App\Support;

use App\Enums\TahapNaskah as T;
use App\Models\Naskah;
use App\Models\NaskahRiwayat;
use App\Models\PerangkatDaerah;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap naskah untuk halaman Laporan, ekspor Excel, dan pengingat naskah tertahan.
 *
 * Waktu tiap tahap dihitung dari riwayat perpindahan tahap (aksi "tahap"): naskah masuk suatu tahap
 * pada catatan yang "sesudah"-nya tahap itu, dan keluar pada catatan berikutnya. Draf yang belum
 * pernah diajukan tidak dihitung.
 */
class LaporanNaskah
{
    /** Batas hari sebelum naskah dianggap tertahan di satu tahap. */
    public static function batasTertahan(): int
    {
        return max(1, (int) config('sifkumda.hari_tertahan', 7));
    }

    /**
     * Naskah yang sudah diajukan beserta waktu prosesnya. Tiap baris: naskah, masuk (waktu masuk tahap
     * sekarang), lama_tahap (hari di tahap sekarang), selesai_at, lama_proses (hari dari diajukan sampai
     * selesai), dan durasi (detik per label tahap yang sudah dilewati).
     */
    public static function baris(?int $tahun = null, ?string $jenis = null, ?int $pd = null): Collection
    {
        $naskah = Naskah::query()
            ->with('pemrakarsa.perangkatDaerah')
            ->where('tahap', '!=', T::Draf)
            ->whereNotNull('diajukan_at')
            ->when($tahun, fn ($q) => $q->whereYear('diajukan_at', $tahun))
            ->when(in_array($jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true), fn ($q) => $q->where('jenis', $jenis))
            ->when($pd, fn ($q) => $q->whereHas('pemrakarsa', fn ($u) => $u->where('perangkat_daerah_id', $pd)))
            ->orderByDesc('diajukan_at')
            ->get();

        $riwayat = NaskahRiwayat::query()
            ->whereIn('naskah_id', $naskah->pluck('id'))
            ->where('aksi', 'tahap')
            ->orderBy('id')
            ->get(['naskah_id', 'sesudah', 'created_at'])
            ->groupBy('naskah_id');

        $sekarang = now();

        return $naskah->map(function (Naskah $n) use ($riwayat, $sekarang) {
            $catatan = $riwayat->get($n->id, collect())->values();
            $durasi = [];

            foreach ($catatan as $i => $c) {
                $keluar = $catatan->get($i + 1)?->created_at;

                if ($keluar) {
                    $durasi[$c->sesudah] = ($durasi[$c->sesudah] ?? 0) + $c->created_at->diffInSeconds($keluar);
                }
            }

            $masuk = $catatan->last()?->created_at ?? $n->diajukan_at;
            $selesaiAt = $n->tahap === T::Selesai ? $catatan->last()?->created_at : null;

            return [
                'naskah' => $n,
                'masuk' => $masuk,
                'lama_tahap' => $n->tahap === T::Selesai ? null : (int) floor($masuk->diffInDays($sekarang)),
                'selesai_at' => $selesaiAt,
                'lama_proses' => $selesaiAt ? (int) round($n->diajukan_at->diffInDays($selesaiAt)) : null,
                'durasi' => $durasi,
            ];
        });
    }

    /** Ringkasan untuk halaman Laporan. */
    public static function susun(?int $tahun = null, ?string $jenis = null, ?int $pd = null): array
    {
        $baris = self::baris($tahun, $jenis, $pd);
        $selesai = $baris->filter(fn ($b) => $b['naskah']->tahap === T::Selesai);
        $berjalan = $baris->reject(fn ($b) => $b['naskah']->tahap === T::Selesai);
        $batas = self::batasTertahan();

        // Rata-rata lama per tahap, hanya dari kunjungan yang sudah selesai.
        $perTahap = collect(T::cases())
            ->reject(fn (T $t) => in_array($t, [T::Draf, T::Selesai], true))
            ->map(function (T $t) use ($baris) {
                $lama = $baris->map(fn ($b) => $b['durasi'][$t->label()] ?? null)->filter(fn ($d) => $d !== null);

                return [
                    'tahap' => $t,
                    'sekarang' => $baris->filter(fn ($b) => $b['naskah']->tahap === $t)->count(),
                    'rata_hari' => $lama->isNotEmpty() ? round($lama->avg() / 86400, 1) : null,
                ];
            })
            ->values();

        $perPd = $baris
            ->groupBy(fn ($b) => $b['naskah']->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $b['naskah']->pemrakarsa->nama_user ?? '-')
            ->map(fn (Collection $isi, string $nama) => [
                'nama' => $nama,
                'diajukan' => $isi->count(),
                'berjalan' => $isi->reject(fn ($b) => $b['naskah']->tahap === T::Selesai)->count(),
                'selesai' => $isi->filter(fn ($b) => $b['naskah']->tahap === T::Selesai)->count(),
            ])
            ->sortByDesc('diajukan')
            ->values();

        return [
            'ringkasan' => [
                'diajukan' => $baris->count(),
                'berjalan' => $berjalan->count(),
                'selesai' => $selesai->count(),
                'rata_proses' => $selesai->isNotEmpty() ? (int) round($selesai->avg('lama_proses')) : null,
                'peraturan' => $baris->where('naskah.jenis', 'Peraturan Bupati')->count(),
                'keputusan' => $baris->where('naskah.jenis', 'Keputusan Bupati')->count(),
            ],
            'perTahap' => $perTahap,
            'perPd' => $perPd,
            'tertahan' => $berjalan->filter(fn ($b) => $b['lama_tahap'] >= $batas)->sortByDesc('lama_tahap')->values(),
            'batas' => $batas,
        ];
    }

    /**
     * Sebaran naskah per Perangkat Daerah dan langkah (TahapNaskah::LANGKAH): tiap baris berisi nama,
     * jumlah per langkah, total, dan naskahnya. $semua = true ikut menampilkan PD yang belum mengajukan.
     */
    public static function matriksPd(Collection $baris, bool $semua = false): Collection
    {
        $kosong = array_fill(0, count(T::LANGKAH), 0);
        $nama = fn (Naskah $n) => $n->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $n->pemrakarsa->nama_user ?? '-';

        $isi = $baris->groupBy(fn ($b) => $nama($b['naskah']))->map(function (Collection $isi, string $pd) use ($kosong) {
            $jumlah = $kosong;
            foreach ($isi as $b) {
                $jumlah[$b['naskah']->tahap->langkah()]++;
            }

            return ['nama' => $pd, 'jumlah' => $jumlah, 'total' => $isi->count(), 'naskah' => $isi->pluck('naskah')->sortByDesc('diajukan_at')->values()];
        });

        if ($semua) {
            PerangkatDaerah::orderBy('nama_perangkat_daerah')->pluck('nama_perangkat_daerah')
                ->reject(fn ($pd) => $isi->has($pd))
                ->each(fn ($pd) => $isi->put($pd, ['nama' => $pd, 'jumlah' => $kosong, 'total' => 0, 'naskah' => collect()]));
        }

        return $isi->sortBy([['total', 'desc'], ['nama', 'asc']])->values();
    }

    /** Aksi yang tampil di Aktivitas bila "perubahan rumusan" tidak ikut ditampilkan. */
    public const AKTIVITAS_PENTING = ['tahap', 'tahapan', 'tahapan_tanggal', 'tahapan_batal', 'ganti_peneliti', 'terbit_ulang_nota'];

    /**
     * Catatan kegiatan semua naskah, terbaru di atas. Filter: jenis, perangkat daerah pengaju, peran
     * pelaku, rentang tanggal, dan apakah perubahan rumusan per baris ikut ditampilkan.
     */
    public static function aktivitas(?string $jenis, ?int $pd, ?string $peran, ?string $dari, ?string $sampai, bool $rumusan, int $perHalaman = 25): LengthAwarePaginator
    {
        return NaskahRiwayat::query()
            ->with(['user.roles', 'naskah.pemrakarsa.perangkatDaerah', 'baris'])
            ->whereHas('naskah', fn ($q) => $q
                ->when(in_array($jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true), fn ($q) => $q->where('jenis', $jenis))
                ->when($pd, fn ($q) => $q->whereHas('pemrakarsa', fn ($u) => $u->where('perangkat_daerah_id', $pd))))
            ->when(! $rumusan, fn ($q) => $q->whereIn('aksi', self::AKTIVITAS_PENTING))
            ->when($peran, fn ($q) => $q->whereHas('user', fn ($u) => $u->role($peran)))
            ->when($dari, fn ($q) => $q->whereDate('created_at', '>=', $dari))
            ->when($sampai, fn ($q) => $q->whereDate('created_at', '<=', $sampai))
            ->orderByDesc('created_at')->orderByDesc('id')
            ->paginate($perHalaman, pageName: 'halamanAktivitas');
    }

    /** Tahun pengajuan yang ada datanya, terbaru di depan. */
    public static function daftarTahun(): array
    {
        return Naskah::query()->whereNotNull('diajukan_at')->get(['diajukan_at'])
            ->map(fn (Naskah $n) => (int) $n->diajukan_at->format('Y'))
            ->unique()->sortDesc()->values()->all();
    }

    public static function formatTanggal(?Carbon $waktu): string
    {
        return $waktu ? $waktu->translatedFormat('d M Y') : '-';
    }
}
