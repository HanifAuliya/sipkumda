<?php

namespace App\Support;

use App\Models\DokumentasiProdukHukum;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Data halaman JDIH publik: beranda, pencarian, dan detail produk hukum.
 *
 * Cache hanya menyimpan angka dan daftar ID (cache.serializable_classes = false),
 * lalu model diambil ulang lewat primary key. Kunci cache memuat "versi" yang
 * naik setiap kali dokumentasi berubah, sehingga data lama tidak pernah tersaji.
 */
class Jdih
{
    /** Kata umum yang tidak membantu pencarian dan membuat syarat FULLTEXT terlalu ketat. */
    private const KATA_UMUM = ['dan', 'yang', 'untuk', 'dari', 'dengan', 'tentang', 'pada', 'atau', 'dalam', 'oleh',
        'atas', 'serta', 'nomor', 'tahun', 'peraturan', 'keputusan', 'bupati', 'kabupaten'];

    /**
     * Nama jenis di database => nama yang ditampilkan ke publik. Arsip dari rancangan
     * memakai "Surat Keputusan", arsip manual memakai "Keputusan Bupati": keduanya sama.
     */
    private const JENIS_PUBLIK = ['Surat Keputusan' => 'Keputusan Bupati'];

    public const URUTAN = [
        'terbaru' => 'Terbaru ditetapkan',
        'terlama' => 'Terlama ditetapkan',
        'nomor' => 'Nomor',
        'relevan' => 'Paling relevan',
    ];

    public static function labelJenis(?string $jenis): ?string
    {
        return self::JENIS_PUBLIK[$jenis] ?? $jenis;
    }

    /** Semua nama di database untuk satu jenis publik. */
    private static function varianJenis(string $label): array
    {
        return [$label, ...array_keys(self::JENIS_PUBLIK, $label, true)];
    }

    public static function versi(): int
    {
        return (int) Cache::rememberForever('jdih:versi', fn () => 1);
    }

    /** Dipanggil saat dokumentasi disimpan/dihapus. */
    public static function segarkan(): void
    {
        Cache::increment('jdih:versi') ?: Cache::forever('jdih:versi', 2);
    }

    /** Naikkan bila bentuk data cache atau logika pengelompokan berubah, agar cache lama tidak terpakai setelah deploy. */
    private const SKEMA = 2;

    private static function ingat(string $kunci, Closure $hitung): mixed
    {
        return Cache::flexible('jdih:s' . self::SKEMA . ':v' . self::versi() . ':' . $kunci, config('jdih.cache'), $hitung);
    }

    /** Ambil model sesuai urutan ID, beserta relasi yang ditampilkan. */
    private static function ambil(array $ids): Collection
    {
        if (! $ids) {
            return new Collection();
        }

        $urutan = array_flip($ids);

        return DokumentasiProdukHukum::with('perangkatDaerah:id,nama_perangkat_daerah')
            ->findMany($ids)
            ->sortBy(fn ($d) => $urutan[$d->id])
            ->values();
    }

    public static function beranda(): array
    {
        $data = self::ingat('beranda', fn () => [
            'perJenis' => DokumentasiProdukHukum::selectRaw('jenis_dokumentasi, COUNT(*) as total')
                ->groupBy('jenis_dokumentasi')->pluck('total', 'jenis_dokumentasi')
                ->groupBy(fn ($total, $jenis) => self::labelJenis($jenis), preserveKeys: true)
                ->map->sum()->sortDesc()->all(),
            'perTahun' => DokumentasiProdukHukum::selectRaw('tahun, COUNT(*) as total')
                ->groupBy('tahun')->orderByDesc('tahun')->limit(8)->pluck('total', 'tahun')->all(),
            'terbaru' => DokumentasiProdukHukum::orderByDesc('tanggal_penetapan')->orderByDesc('id')->limit(6)->pluck('id')->all(),
        ]);

        return [
            'total' => array_sum($data['perJenis']),
            'perJenis' => $data['perJenis'],
            'perTahun' => $data['perTahun'],
            'terbaru' => self::ambil($data['terbaru']),
        ];
    }

    /** Pilihan filter jenis & tahun. */
    public static function opsi(): array
    {
        return self::ingat('opsi', fn () => [
            'jenis' => DokumentasiProdukHukum::query()->distinct()->pluck('jenis_dokumentasi')->filter()
                ->map(fn ($jenis) => self::labelJenis($jenis))->unique()->sort()->values()->all(),
            'tahun' => DokumentasiProdukHukum::query()->distinct()->orderByDesc('tahun')->pluck('tahun')->filter()->values()->all(),
        ]);
    }

    /**
     * @param  array{q: string, jenis: ?string, tahun: ?int, urut: string}  $filter
     */
    public static function cari(array $filter, int $halaman): LengthAwarePaginator
    {
        $perHalaman = config('jdih.per_halaman');

        $query = DokumentasiProdukHukum::query()
            ->when($filter['jenis'], fn ($q, $jenis) => $q->whereIn('jenis_dokumentasi', self::varianJenis($jenis)))
            ->when($filter['tahun'], fn ($q, $tahun) => $q->where('tahun', $tahun));

        $relevan = self::terapkanKataKunci($query, $filter['q']);

        match (true) {
            $filter['urut'] === 'relevan' && $relevan !== null => $relevan($query),
            $filter['urut'] === 'terlama' => $query->orderBy('tanggal_penetapan')->orderBy('id'),
            $filter['urut'] === 'nomor' => $query->orderByDesc('tahun')->orderByRaw('CAST(nomor AS UNSIGNED) DESC'),
            default => $query->orderByDesc('tanggal_penetapan')->orderByDesc('id'),
        };

        $hitung = fn () => [
            'ids' => (clone $query)->forPage($halaman, $perHalaman)->pluck('id')->all(),
            'total' => (clone $query)->reorder()->count(),
        ];

        // Tanpa kata kunci, kombinasi filter terbatas sehingga aman di-cache.
        // Pencarian bebas tidak di-cache (bisa tak terhingga), cukup memakai indeks FULLTEXT.
        $hasil = $filter['q'] === ''
            ? self::ingat('daftar:' . md5(json_encode([$filter, $halaman])), $hitung)
            : $hitung();

        return new LengthAwarePaginator(self::ambil($hasil['ids']), $hasil['total'], $perHalaman, $halaman, [
            'path' => route('jdih.index'),
            'query' => array_filter($filter, fn ($v) => $v !== null && $v !== '' && $v !== 'terbaru'),
        ]);
    }

    /** Produk hukum lain dengan jenis yang sama, terbaru lebih dulu. */
    public static function terkait(DokumentasiProdukHukum $dokumentasi): Collection
    {
        return self::ambil(self::ingat("terkait:{$dokumentasi->id}", fn () => DokumentasiProdukHukum::query()
            ->whereIn('jenis_dokumentasi', self::varianJenis(self::labelJenis($dokumentasi->jenis_dokumentasi)))
            ->whereKeyNot($dokumentasi->id)
            ->orderByDesc('tanggal_penetapan')
            ->limit(4)
            ->pluck('id')
            ->all()));
    }

    /**
     * Kata kunci: nomor ("12", "12/2025", "nomor 12 tahun 2025") atau teks "tentang".
     * Mengembalikan fungsi pengurut "paling relevan" untuk jenis pencarian tersebut.
     */
    private static function terapkanKataKunci(Builder $query, string $q): ?Closure
    {
        if ($q === '') {
            return null;
        }

        // Pencarian yang hanya berisi nomor/tahun cukup memakai kolom terindeks, tanpa FULLTEXT.
        if (preg_match('/^(?:no(?:mor)?\.?\s*)?(\d{1,4})(?:\s*(?:\/|tahun|th\.?)\s*(\d{4}))?$/iu', $q, $m)) {
            [$nomor, $tahun] = [(int) $m[1], isset($m[2]) ? (int) $m[2] : null];

            $query->where(fn (Builder $w) => $w
                ->where(fn ($n) => $n->where('nomor', $nomor)->when($tahun, fn ($t) => $t->where('tahun', $tahun)))
                // Angka empat digit tanpa "/" bisa juga berarti tahun, mis. "2020".
                ->when(! $tahun && $nomor >= 1900 && $nomor <= 2100, fn ($t) => $t->orWhere('tahun', $nomor)));

            // Urut per tahun (indeks tahun, nomor). Urutan tanggal + LIMIT membuat MySQL
            // menelusuri seluruh indeks tanggal bila hasilnya sedikit.
            return fn (Builder $q) => $q->orderByDesc('tahun')->orderByDesc('nomor');
        }

        $kata = collect(preg_split('/\s+/u', preg_replace('/[^\pL\pN\s]+/u', ' ', mb_strtolower($q)), -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($k) => mb_strlen($k) >= 3 && ! in_array($k, self::KATA_UMUM, true))
            ->unique()
            ->take(8);

        $fulltext = $kata->isNotEmpty() && in_array($query->getConnection()->getDriverName(), ['mysql', 'mariadb']);
        $ekspresi = $kata->map(fn ($k) => "+{$k}*")->join(' ');

        $fulltext
            ? $query->whereFullText('tentang_dokumentasi', $ekspresi, ['mode' => 'boolean'])
            : $query->where('tentang_dokumentasi', 'like', '%' . addcslashes($q, '%_\\') . '%');

        return $fulltext
            ? fn (Builder $q) => $q->orderByRaw('MATCH(tentang_dokumentasi) AGAINST (? IN BOOLEAN MODE) DESC', [$ekspresi])
            : null;
    }
}
