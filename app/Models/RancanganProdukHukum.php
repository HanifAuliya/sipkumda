<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class RancanganProdukHukum extends Model
{
    use HasFactory;

    protected $table = 'rancangan_produk_hukum';
    protected $primaryKey = 'id_rancangan';

    protected $fillable = [
        'no_rancangan',
        'tentang',
        'jenis_rancangan',
        'nota_dinas_pd',
        'rancangan',
        'matrik',
        'bahan_pendukung',
        'tanggal_pengajuan',
        'tanggal_berkas_disetujui',
        'id_user',
        'status_berkas',
        'status_rancangan',
        'catatan_berkas',
        'tanggal_nota',
        'nomor_nota'
    ];

    // Mutator untuk nomor rancangan otomatis
    protected static function boot()
    {
        parent::boot();

        // static::creating(function ($rancangan) {
        //     $tahun = now()->year;
        //     $jenis = $rancangan->jenis_rancangan; // PB atau KP
        //     $count = self::where('jenis_rancangan', $jenis)
        //         ->whereYear('created_at', $tahun)
        //         ->count() + 1;

        //     // Format nomor rancangan: PB-2024-001
        //     $rancangan->no_rancangan = sprintf('%s-%d-%03d', $jenis, $tahun, $count);
        // });
        static::creating(function ($rancangan) {
            $tahun = now()->year;

            // Konversi nilai enum menjadi singkatan
            $jenis = $rancangan->jenis_rancangan;
            $singkatan = $jenis === 'Surat Keputusan' ? 'SK' : 'PB';

            // Hitung jumlah rancangan untuk jenis dan tahun ini
            $count = self::where('jenis_rancangan', $jenis)
                ->whereYear('created_at', $tahun)
                ->count() + 1;

            // Format nomor rancangan: SK-2024-001
            $rancangan->no_rancangan = sprintf('%s-%d-%03d', $singkatan, $tahun, $count);
        });

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::uuid(); // Anda bisa mengganti dengan slug lain, misalnya menggunakan `no_rancangan`
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function perangkatDaerah()
    {
        return $this->belongsTo(PerangkatDaerah::class, 'perangkat_daerah_id');
    }

    public function revisi()
    {
        return $this->hasMany(Revisi::class, 'id_rancangan', 'id_rancangan');
    }

    // Relasi ke tabel fasilitasi_produk_hukum
    public function fasilitasi()
    {
        return $this->hasOne(FasilitasiProdukHukum::class, 'rancangan_id');
    }
}
