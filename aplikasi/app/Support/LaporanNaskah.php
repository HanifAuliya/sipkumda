<?php

namespace App\Support;

use App\Enums\TahapNaskah as T;
use App\Models\DokumentasiProdukHukum;
use App\Models\Naskah;
use App\Models\NaskahRiwayat;
use App\Models\PerangkatDaerah;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Rekap naskah untuk halaman Laporan, ekspor Excel, dan pengingat naskah tertahan.
 *
 * Waktu tiap tahap dihitung dari riwayat perpindahan tahap (aksi "tahap"): naskah masuk suatu tahap
 * pada catatan yang "sesudah"-nya tahap itu, dan keluar pada catatan berikutnya. Draf yang belum
 * pernah diajukan tidak dihitung.
 *
 * $rentang adalah [mulai, akhir] dari PeriodeLaporan (salah satunya boleh null), atau null untuk
 * semua waktu. Naskah masuk periode menurut tanggal diajukan; produk hukum menurut tanggal penetapan.
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
    public static function baris(?array $rentang = null, ?string $jenis = null, ?int $pd = null): Collection
    {
        $naskah = self::naskahDiajukan($rentang, $jenis, $pd)
            ->with('pemrakarsa.perangkatDaerah')
            ->where('tahap', '!=', T::Draf)
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

    /** Naskah yang diajukan dalam periode, sesuai jenis dan Perangkat Daerah pengaju. */
    private static function naskahDiajukan(?array $rentang, ?string $jenis, ?int $pd): Builder
    {
        [$mulai, $akhir] = $rentang ?? [null, null];

        return Naskah::query()
            ->whereNotNull('diajukan_at')
            ->when($mulai, fn ($q) => $q->where('diajukan_at', '>=', $mulai))
            ->when($akhir, fn ($q) => $q->where('diajukan_at', '<=', $akhir))
            ->when(self::jenisSah($jenis), fn ($q) => $q->where('jenis', $jenis))
            ->when($pd, fn ($q) => $q->whereHas('pemrakarsa', fn ($u) => $u->where('perangkat_daerah_id', $pd)));
    }

    /** Produk hukum yang ditetapkan dalam periode (dari Dokumentasi, termasuk arsip lama). */
    private static function produkDitetapkan(?array $rentang, ?string $jenis, ?int $pd): Builder
    {
        [$mulai, $akhir] = $rentang ?? [null, null];

        return DokumentasiProdukHukum::query()
            ->when($mulai, fn ($q) => $q->whereDate('tanggal_penetapan', '>=', $mulai))
            ->when($akhir, fn ($q) => $q->whereDate('tanggal_penetapan', '<=', $akhir))
            ->when(self::jenisSah($jenis), fn ($q) => $q->where('jenis_dokumentasi', $jenis))
            ->when($pd, fn ($q) => $q->where('perangkat_daerah_id', $pd));
    }

    private static function jenisSah(?string $jenis): bool
    {
        return in_array($jenis, ['Peraturan Bupati', 'Keputusan Bupati'], true);
    }

    /** Jumlah produk hukum yang ditetapkan, per jenis, dan berapa yang lewat SIFKUMDA. */
    public static function ditetapkan(?array $rentang = null, ?string $jenis = null, ?int $pd = null): array
    {
        $produk = self::produkDitetapkan($rentang, $jenis, $pd)->get(['id', 'jenis_dokumentasi']);
        $perJenis = $produk->countBy(fn ($d) => Status::label($d->jenis_dokumentasi));

        return [
            'total' => $produk->count(),
            'peraturan' => $perJenis['Peraturan Bupati'] ?? 0,
            'keputusan' => $perJenis['Keputusan Bupati'] ?? 0,
            'dari_sistem' => $produk->isEmpty() ? 0 : Naskah::whereIn('dokumentasi_id', $produk->pluck('id'))->count(),
        ];
    }

    /**
     * Data grafik: naskah diajukan dan produk hukum ditetapkan per bulan (per tahun bila periodenya
     * lebih dari dua tahun), per jenis; serta sebaran naskah yang sedang berjalan per langkah.
     * Tanpa periode, grafik bulanan menampilkan 12 bulan terakhir.
     */
    public static function grafik(?array $rentang, ?string $jenis, ?int $pd, Collection $baris): array
    {
        [$mulai, $akhir] = $rentang ?? [null, null];
        $akhir = ($akhir ?? now())->copy()->endOfMonth();
        $mulai = ($mulai ?? $akhir->copy()->subMonths(11))->copy()->startOfMonth();
        $tahunan = $mulai->diffInMonths($akhir) > 24;
        $kunci = fn (Carbon $t) => $tahunan ? $t->format('Y') : $t->format('Y-m');

        $label = [];
        for ($t = $mulai->copy(); $t <= $akhir; $tahunan ? $t->addYear() : $t->addMonth()) {
            $label[$kunci($t)] = $tahunan ? $t->format('Y') : $t->translatedFormat('M y');
        }

        $isi = function (Collection $baris, callable $waktu, callable $jenisNya) use ($label, $kunci) {
            $hasil = ['Peraturan Bupati' => array_fill_keys(array_keys($label), 0), 'Keputusan Bupati' => array_fill_keys(array_keys($label), 0)];
            foreach ($baris as $b) {
                $k = $waktu($b) ? $kunci($waktu($b)) : null;
                $j = $jenisNya($b);
                if ($k !== null && isset($hasil[$j][$k])) {
                    $hasil[$j][$k]++;
                }
            }

            return array_map('array_values', $hasil);
        };

        $diajukan = self::naskahDiajukan([$mulai, $akhir], $jenis, $pd)->get(['jenis', 'diajukan_at']);
        $ditetapkan = self::produkDitetapkan([$mulai, $akhir], $jenis, $pd)->whereNotNull('tanggal_penetapan')->get(['jenis_dokumentasi', 'tanggal_penetapan']);

        $berjalan = $baris->reject(fn ($b) => $b['naskah']->tahap === T::Selesai);
        $langkah = array_slice(T::LANGKAH, 0, -1); // tanpa "Selesai"

        return [
            'label' => array_values($label),
            'tahunan' => $tahunan,
            'diajukan' => $isi($diajukan, fn ($n) => $n->diajukan_at, fn ($n) => $n->jenis),
            'ditetapkan' => $isi($ditetapkan, fn ($d) => Carbon::parse($d->tanggal_penetapan), fn ($d) => Status::label($d->jenis_dokumentasi)),
            'langkah' => [
                'label' => $langkah,
                'jumlah' => array_map(fn ($i) => $berjalan->filter(fn ($b) => $b['naskah']->tahap->langkah() === $i)->count(), array_keys($langkah)),
            ],
        ];
    }

    /** Ringkasan untuk halaman Laporan. */
    public static function susun(?array $rentang = null, ?string $jenis = null, ?int $pd = null): array
    {
        $baris = self::baris($rentang, $jenis, $pd);
        $selesai = $baris->filter(fn ($b) => $b['naskah']->tahap === T::Selesai);
        $berjalan = $baris->reject(fn ($b) => $b['naskah']->tahap === T::Selesai);
        $batas = self::batasTertahan();

        // Rata-rata lama per tahap, hanya dari kunjungan yang sudah selesai.
        $perTahap = collect(T::cases())
            ->reject(fn (T $t) => in_array($t, [T::Draf, T::Selesai], true))
            ->map(function (T $t) use ($baris) {
                $lama = $baris->map(fn ($b) => $b['durasi'][$t->label()] ?? null)->filter(fn ($d) => $d !== null);

                $sekarang = $baris->filter(fn ($b) => $b['naskah']->tahap === $t);

                return [
                    'tahap' => $t,
                    'sekarang' => $sekarang->count(),
                    'naskah' => $sekarang->pluck('naskah')->values(),
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
            'ditetapkan' => self::ditetapkan($rentang, $jenis, $pd),
            'baris' => $baris,
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
    public static function aktivitasQuery(?string $jenis, ?int $pd, ?string $peran, ?array $rentang, bool $rumusan): Builder
    {
        [$mulai, $akhir] = $rentang ?? [null, null];

        return NaskahRiwayat::query()
            ->with(['user.roles', 'naskah.pemrakarsa.perangkatDaerah', 'baris'])
            ->whereHas('naskah', fn ($q) => $q
                ->when(self::jenisSah($jenis), fn ($q) => $q->where('jenis', $jenis))
                ->when($pd, fn ($q) => $q->whereHas('pemrakarsa', fn ($u) => $u->where('perangkat_daerah_id', $pd))))
            ->when(! $rumusan, fn ($q) => $q->whereIn('aksi', self::AKTIVITAS_PENTING))
            ->when($peran, fn ($q) => $q->whereHas('user', fn ($u) => $u->role($peran)))
            ->when($mulai, fn ($q) => $q->where('created_at', '>=', $mulai))
            ->when($akhir, fn ($q) => $q->where('created_at', '<=', $akhir))
            ->orderByDesc('created_at')->orderByDesc('id');
    }

    public static function aktivitas(?string $jenis, ?int $pd, ?string $peran, ?array $rentang, bool $rumusan, int $perHalaman = 25): LengthAwarePaginator
    {
        return self::aktivitasQuery($jenis, $pd, $peran, $rentang, $rumusan)->paginate($perHalaman, pageName: 'halamanAktivitas');
    }

    /** Tahun yang ada datanya (pengajuan naskah atau penetapan produk hukum), terbaru di depan. */
    public static function daftarTahun(): array
    {
        // toBase(): koleksi Eloquent yang kosong tetap Eloquent setelah map(), dan merge()-nya hanya menerima model.
        return Naskah::query()->whereNotNull('diajukan_at')->get(['diajukan_at'])->toBase()
            ->map(fn (Naskah $n) => (int) $n->diajukan_at->format('Y'))
            ->merge(DokumentasiProdukHukum::query()->distinct()->pluck('tahun')->map(fn ($t) => (int) $t))
            ->push(now()->year)
            ->filter()->unique()->sortDesc()->values()->all();
    }

    public static function formatTanggal(?Carbon $waktu): string
    {
        return $waktu ? $waktu->translatedFormat('d M Y') : '-';
    }
}
