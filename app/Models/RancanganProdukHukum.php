<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
        'nomor_nota',
        'hasil_prediksi_kelengkapan',
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
        static::creating(function ($model) {

            DB::transaction(function () use ($model) {

                $tahun = now()->year;

                $map = [
                    'Surat Keputusan' => 'SK',
                    'Peraturan Bupati' => 'PB',
                ];

                if (!isset($map[$model->jenis_rancangan])) {
                    throw new \Exception("Jenis rancangan tidak valid.");
                }

                $singkatan = $map[$model->jenis_rancangan];

                // Ambil nomor terakhir tahun & jenis yang sama
                $last = self::where('jenis_rancangan', $model->jenis_rancangan)
                    ->whereYear('created_at', $tahun)
                    ->orderByDesc('id_rancangan')
                    ->lockForUpdate()
                    ->first();

                $nextNumber = 1;

                if ($last) {
                    $parts = explode('-', $last->no_rancangan);
                    $nextNumber = (int) end($parts) + 1;
                }

                $model->no_rancangan = sprintf(
                    '%s-%d-%03d',
                    $singkatan,
                    $tahun,
                    $nextNumber
                );

                // Generate slug UUID
                $model->slug = Str::uuid();
            });
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

    public function dokumentasi()
    {
        return $this->hasOne(DokumentasiProdukHukum::class, 'rancangan_id');
    }
}
