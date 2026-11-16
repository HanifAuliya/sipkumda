<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nomor rancangan kembali ke format aplikasi lama: PB-2026-001 / SK-2026-001 (sebelumnya NPB / NSK).
 *
 * Aplikasi lama sudah memakai nomor PB/SK pada tahun berjalan, jadi naskah yang sudah bernomor
 * dinomori ulang sesudah nomor tertinggi di sana (NPB-2026-001 menjadi PB-2026-012 bila PB terakhir 011).
 * Penomoran berikutnya melanjutkan dari situ; lihat Naskah::beriNomor.
 */
return new class extends Migration
{
    private const AWALAN = ['NPB' => ['PB', 'Peraturan Bupati'], 'NSK' => ['SK', 'Surat Keputusan']];

    public function up(): void
    {
        DB::transaction(function () {
            foreach (DB::table('naskah')->whereNotNull('nomor')->orderBy('id')->get(['id', 'nomor']) as $naskah) {
                if (! preg_match('/^(NPB|NSK)-(\d{4})-(\d+)$/', $naskah->nomor, $m)) {
                    continue;
                }

                [$baru] = self::AWALAN[$m[1]];
                $urut = (int) $m[3] + $this->tertinggiLama($m[1], (int) $m[2]);

                DB::table('naskah')->where('id', $naskah->id)->update(['nomor' => sprintf('%s-%s-%03d', $baru, $m[2], $urut)]);
            }

            foreach (DB::table('naskah_nomor_urut')->whereIn('awalan', array_keys(self::AWALAN))->get() as $hitung) {
                DB::table('naskah_nomor_urut')->where('id', $hitung->id)->update([
                    'awalan' => self::AWALAN[$hitung->awalan][0],
                    'terakhir' => $hitung->terakhir + $this->tertinggiLama($hitung->awalan, (int) $hitung->tahun),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /** Awalan dikembalikan ke NPB / NSK; nomor urut yang sudah digeser tidak dikembalikan. */
    public function down(): void
    {
        DB::transaction(function () {
            foreach (self::AWALAN as $lama => [$baru]) {
                foreach (DB::table('naskah')->where('nomor', 'like', "{$baru}-%")->get(['id', 'nomor']) as $naskah) {
                    DB::table('naskah')->where('id', $naskah->id)->update(['nomor' => $lama . substr($naskah->nomor, strlen($baru))]);
                }

                DB::table('naskah_nomor_urut')->where('awalan', $baru)->update(['awalan' => $lama]);
            }
        });
    }

    private function tertinggiLama(string $awalanV2, int $tahun): int
    {
        [$awalan, $jenis] = self::AWALAN[$awalanV2];
        $depan = "{$awalan}-{$tahun}-";

        return (int) DB::table('rancangan_produk_hukum')
            ->where('jenis_rancangan', $jenis)
            ->where('no_rancangan', 'like', "{$depan}%")
            ->pluck('no_rancangan')
            ->map(fn (string $nomor) => (int) substr($nomor, strlen($depan)))
            ->max();
    }
};
