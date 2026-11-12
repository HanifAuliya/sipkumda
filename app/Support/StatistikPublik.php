<?php

namespace App\Support;

use App\Enums\TahapNaskah;
use App\Models\DokumentasiProdukHukum;
use App\Models\Naskah;
use App\Models\PerangkatDaerah;
use Illuminate\Support\Facades\Cache;

/**
 * Angka ringkas untuk beranda publik SIFKUMDA. Hanya agregat (tanpa data pribadi),
 * di-cache beberapa menit karena sama untuk semua pengunjung. Draf yang belum diajukan tidak dihitung.
 */
class StatistikPublik
{
    public static function ringkasan(): array
    {
        return Cache::flexible('beranda:statistik:v3', [300, 1800], function () {
            $naskah = Naskah::query()->toBase()->selectRaw('tahap, count(*) as jumlah')->groupBy('tahap')->pluck('jumlah', 'tahap');
            $jumlah = fn (array $tahap) => collect($tahap)->sum(fn (TahapNaskah $t) => (int) ($naskah[$t->value] ?? 0));
            $diajukan = array_filter(TahapNaskah::cases(), fn (TahapNaskah $t) => $t !== TahapNaskah::Draf);

            return [
                'rancangan' => $jumlah($diajukan),
                'diproses' => $jumlah(array_filter($diajukan, fn (TahapNaskah $t) => $t !== TahapNaskah::Selesai)),
                'fasilitasi' => $jumlah(TahapNaskah::SETELAH_RANCANGAN),
                'ditetapkan' => DokumentasiProdukHukum::count(),
                'instansi' => PerangkatDaerah::count(),
            ];
        });
    }
}
