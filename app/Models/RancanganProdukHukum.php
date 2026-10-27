<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Jalankan pembuatan rancangan di dalam kunci + transaksi.
     *
     * Nomor rancangan dihitung dari nomor terakhir, jadi dua pengajuan pada saat
     * yang sama bisa mendapat nomor yang sama. Kunci memastikan perhitungan nomor
     * dan penyimpanan terjadi bergiliran; transaksi menjaga rancangan beserta
     * data terkaitnya tersimpan utuh.
     */
    public static function denganNomorTerkunci(callable $buat): mixed
    {
        return Cache::lock('nomor-rancangan', 15)->block(10, fn () => DB::transaction($buat));
    }

    protected static function boot()
    {
        parent::boot();

        // Nomor rancangan otomatis: PB-2026-001 / SK-2026-001, berurutan per jenis per tahun.
        static::creating(function ($model) {
            $singkatan = ['Surat Keputusan' => 'SK', 'Peraturan Bupati' => 'PB'][$model->jenis_rancangan]
                ?? throw new \InvalidArgumentException('Jenis rancangan tidak valid.');

            $tahun = now()->year;

            $terakhir = self::where('jenis_rancangan', $model->jenis_rancangan)
                ->whereYear('created_at', $tahun)
                ->orderByDesc('id_rancangan')
                ->value('no_rancangan');

            $berikutnya = $terakhir ? (int) Str::afterLast($terakhir, '-') + 1 : 1;

            $model->no_rancangan = sprintf('%s-%d-%03d', $singkatan, $tahun, $berikutnya);
            $model->slug = $model->slug ?: (string) Str::uuid();
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
