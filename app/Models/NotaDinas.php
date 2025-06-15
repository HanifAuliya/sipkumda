<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaDinas extends Model
{
    use HasFactory;

    protected $table = 'nota_dinas';

    protected $fillable = [
        'fasilitasi_id',
        'nomor_nota',
        'tanggal_nota',
        'file_nota_dinas',
        'tanda_tangan_id',
        'kepada',
        'nomor_inputan'
    ];

    // Mutator untuk membuat format nomor nota otomatis
    public static function generateNomorNota($rancangan)
    {
        $jenisKode = ($rancangan->jenis_rancangan === 'Peraturan Bupati') ? 'PB' : 'SK';
        $tahun = now()->year;
        $nomorUrut = self::getNextNomor(); // Fungsi untuk mendapatkan nomor urut terbaru

        return "180/{$nomorUrut}/ND{$jenisKode}/KUM/{$tahun}";
    }

    // Fungsi untuk mendapatkan nomor urut terakhir
    public static function getNextNomor()
    {
        $lastNota = self::latest()->first();
        return $lastNota ? (intval(explode('/', $lastNota->nomor_nota)[1]) + 1) : 1;
    }

    // Relasi ke tabel fasilitasi_produk_hukum
    public function fasilitasi()
    {
        return $this->belongsTo(FasilitasiProdukHukum::class, 'fasilitasi_id');
    }

    // Relasi ke tabel tanda_tangan
    public function tandaTangan()
    {
        return $this->belongsTo(TandaTangan::class, 'tanda_tangan_id');
    }
}
