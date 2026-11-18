<?php

namespace App\Support;

use App\Models\Naskah;
use App\Models\NaskahBaris;
use App\Models\NaskahBerkas;
use Illuminate\Support\Collection;

/**
 * Pemeriksaan rancangan sebelum diajukan PD, untuk ringkasan di layar kerja dan jendela Ajukan:
 * - kosong: baris yang belum diisi;
 * - galat: yang pasti harus dibereskan dan menghalangi pengajuan (isian "…" dari template, Nota Dinas PD
 *   belum ada);
 * - peringatan: hasil deteksi sistem yang bisa keliru, tidak menghalangi (penomoran BAB/Pasal, rujukan pasal
 *   dan ayat, judul berbeda dengan judul pengajuan).
 *
 * Penghalang yang sama diperiksa ulang di AlurNaskah::hambatanAjukan.
 */
class PemeriksaAjukan
{
    /**
     * @param  Collection<int, NaskahBaris>  $baris  semua baris naskah, urut
     * @return array{kosong: list<array{baris_id: ?int, pesan: string}>, galat: list<array{baris_id: ?int, pesan: string}>, peringatan: list<array{baris_id: ?int, pesan: string}>}
     */
    public static function periksa(Naskah $naskah, Collection $baris): array
    {
        $kurang = collect(PemeriksaIsian::kurangLengkap($naskah, $baris));
        $notaAda = $naskah->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS_PD)->where('aktif', true)->exists();

        return [
            'kosong' => $kurang->where('kosong', true)->values()->all(),
            'galat' => [
                ...($notaAda ? [] : [['baris_id' => null, 'pesan' => 'Nota Dinas PD belum diunggah.']]),
                ...$kurang->where('kosong', false)->values()->all(),
            ],
            'peringatan' => [
                ...PemeriksaIsian::periksa($naskah, $baris, false),
                ...PemeriksaPenomoran::periksa($baris),
            ],
        ];
    }

    /** Ada yang menghalangi pengajuan. */
    public static function terhalang(array $hasil): bool
    {
        return $hasil['kosong'] || $hasil['galat'];
    }
}
