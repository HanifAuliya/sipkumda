<?php

namespace App\Support;

use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Facades\Cache;

/**
 * Angka ringkas untuk beranda publik SIFKUMDA. Hanya agregat (tanpa data pribadi),
 * di-cache beberapa menit karena sama untuk semua pengunjung.
 */
class StatistikPublik
{
    public static function ringkasan(): array
    {
        return Cache::flexible('beranda:statistik:v1', [300, 1800], fn () => [
            'rancangan' => RancanganProdukHukum::count(),
            'diproses' => RancanganProdukHukum::where('status_rancangan', 'Dalam Proses')->count(),
            'fasilitasi' => FasilitasiProdukHukum::count(),
            'ditetapkan' => DokumentasiProdukHukum::count(),
            'instansi' => PerangkatDaerah::count(),
        ]);
    }
}
