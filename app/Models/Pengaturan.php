<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nilai yang bisa berganti tanpa mengubah kode, misalnya nama pejabat. */
class Pengaturan extends Model
{
    public const NAMA_BUPATI = 'nama_bupati';
    public const NAMA_SEKDA = 'nama_sekda';

    /** Cakupan notifikasi WhatsApp: tindakan | semua (lihat App\Support\Wa\AntreanWa). */
    public const WA_CAKUPAN = 'wa_cakupan';

    protected $table = 'pengaturan';

    protected $fillable = ['kunci', 'nilai'];

    public static function ambil(string $kunci, string $bawaan = ''): string
    {
        return self::where('kunci', $kunci)->value('nilai') ?: $bawaan;
    }

    public static function simpan(string $kunci, ?string $nilai): void
    {
        self::updateOrCreate(['kunci' => $kunci], ['nilai' => $nilai]);
    }
}
