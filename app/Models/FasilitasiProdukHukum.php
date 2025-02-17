<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FasilitasiProdukHukum extends Model
{
    use HasFactory;

    protected $table = 'fasilitasi_produk_hukum';

    protected $fillable = [
        'rancangan_id',
        'tanggal_fasilitasi',
        'file_rancangan',
        'status_berkas_fasilitasi',
        'catatan_persetujuan_fasilitasi',
        'tanggal_persetujuan_berkas',
        'status_validasi_fasilitasi',
        'catatan_validasi_fasilitasi',
        'tanggal_validasi_fasilitasi',
        'status_paraf_koordinasi',
        'tanggal_paraf_koordinasi',
        'status_asisten',
        'tanggal_asisten',
        'status_sekda',
        'tanggal_sekda',
        'status_bupati',
        'tanggal_bupati',

    ];

    // Relasi ke tabel rancangan_produk_hukum
    public function rancangan()
    {
        return $this->belongsTo(RancanganProdukHukum::class, 'rancangan_id');
    }

    // Relasi ke tabel nota_dinas
    public function notaDinas()
    {
        return $this->hasOne(NotaDinas::class, 'fasilitasi_id');
    }

    public function dokumentasi()
    {
        return $this->hasOne(DokumentasiProdukHukum::class, 'rancangan_id', 'rancangan_id');
    }
}
