<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class DokumentasiProdukHukum extends Model
{
    use HasFactory;

    protected $table = 'dokumentasi_produk_hukum';

    protected $fillable = [
        'rancangan_id',
        'nomor',
        'tahun',
        'tanggal',
        'file_produk_hukum',
        'nomor_berita_daerah',
        'tanggal_berita_daerah',
        'berita_daerah',
        'perangkat_daerah_id',
    ];

    // Relasi ke Rancangan Produk Hukum
    public function rancangan()
    {
        return $this->belongsTo(RancanganProdukHukum::class, 'rancangan_id');
    }

    // Relasi ke Perangkat Daerah
    public function perangkatDaerah()
    {
        return $this->belongsTo(PerangkatDaerah::class, 'perangkat_daerah_id');
    }

    /**
     * ✅ Mutator untuk menampilkan nomor dalam format: "Nomor 003 Tahun 2025"
     */
    public function getNomorFormattedAttribute()
    {
        return "Nomor " . str_pad($this->nomor, 3, '0', STR_PAD_LEFT) . " Tahun " . $this->tahun;
    }

    /**
     * ✅ Event untuk membuat nomor otomatis saat creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dokumentasi) {
            $tahun = now()->year; // Tahun saat ini

            // Pastikan nomor hanya berisi 3 digit angka
            $nomor = str_pad($dokumentasi->nomor, 3, '0', STR_PAD_LEFT);

            // Cek apakah nomor sudah ada di tahun ini
            $existing = self::where('tahun', $tahun)
                ->where('nomor', $nomor)
                ->exists();

            if ($existing) {
                // Simpan pesan error di session agar bisa diakses di Livewire atau Controller
                session()->flash('error_nomor', "Nomor {$nomor} untuk tahun {$tahun} sudah ada! Pilih nomor lain.");
                return false; // Mencegah penyimpanan
            }

            // Simpan dalam format 3 digit
            $dokumentasi->nomor = $nomor;
            $dokumentasi->tahun = $tahun;
        });
    }
}
