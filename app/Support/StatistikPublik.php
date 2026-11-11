<?php

namespace App\Support;

use App\Enums\TahapNaskah;
use App\Models\DokumentasiProdukHukum;
use App\Models\FasilitasiProdukHukum;
use App\Models\Naskah;
use App\Models\PerangkatDaerah;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Facades\Cache;

/**
 * Angka ringkas untuk beranda publik SIFKUMDA. Hanya agregat (tanpa data pribadi),
 * di-cache beberapa menit karena sama untuk semua pengunjung.
 *
 * Menjumlahkan naskah alur sekarang dengan data alur sebelumnya, supaya angka tidak turun
 * saat alur berganti. Draf yang belum diajukan tidak dihitung.
 */
class StatistikPublik
{
    public static function ringkasan(): array
    {
        return Cache::flexible('beranda:statistik:v2', [300, 1800], function () {
            $naskah = Naskah::query()->toBase()->selectRaw('tahap, count(*) as jumlah')->groupBy('tahap')->pluck('jumlah', 'tahap');
            $jumlah = fn (array $tahap) => collect($tahap)->sum(fn (TahapNaskah $t) => (int) ($naskah[$t->value] ?? 0));
            $diajukan = array_filter(TahapNaskah::cases(), fn (TahapNaskah $t) => $t !== TahapNaskah::Draf);

            return [
                'rancangan' => RancanganProdukHukum::count() + $jumlah($diajukan),
                'diproses' => RancanganProdukHukum::where('status_rancangan', 'Dalam Proses')->count()
                    + $jumlah(array_filter($diajukan, fn (TahapNaskah $t) => $t !== TahapNaskah::Selesai)),
                'fasilitasi' => FasilitasiProdukHukum::count() + $jumlah(TahapNaskah::SETELAH_RANCANGAN),
                'ditetapkan' => DokumentasiProdukHukum::count(),
                'instansi' => PerangkatDaerah::count(),
            ];
        });
    }
}
