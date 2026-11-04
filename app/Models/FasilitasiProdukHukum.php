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

    /**
     * Hanya fasilitasi dari rancangan milik user tertentu (perangkat daerah).
     */
    public function scopeMilik($query, int $userId)
    {
        return $query->whereHas('rancangan', fn ($q) => $q->where('id_user', $userId));
    }

    /**
     * Tahapan paraf setelah nota dinas: [label, status, tanggal].
     */
    public function tahapanParaf(): array
    {
        return [
            ['Paraf Koordinasi', $this->status_paraf_koordinasi, $this->tanggal_paraf_koordinasi],
            ['Asisten', $this->status_asisten, $this->tanggal_asisten],
            ['Sekda', $this->status_sekda, $this->tanggal_sekda],
            ['Bupati', $this->status_bupati, $this->tanggal_bupati],
        ];
    }

    /**
     * Pesan status untuk perangkat daerah: ['type' => warna alert, 'title' => ..., 'message' => ...],
     * atau null bila tidak ada pesan untuk kombinasi status saat ini.
     */
    public function statusInfo(): ?array
    {
        $berkas = $this->status_berkas_fasilitasi;
        $validasi = $this->status_validasi_fasilitasi;
        $adaNota = $this->notaDinas !== null;

        return match (true) {
            $berkas === 'Menunggu Persetujuan' && $validasi === 'Belum Tahap Validasi' => [
                'type' => 'warning', 'title' => 'Harap sabar.',
                'message' => 'Fasilitasi menunggu persetujuan dari peneliti.',
            ],
            $berkas === 'Ditolak' && $validasi === 'Belum Tahap Validasi' => [
                'type' => 'danger', 'title' => 'Fasilitasi ditolak.',
                'message' => 'Silakan perbaiki lalu pilih Kelola → Upload Ulang Fasilitasi.',
            ],
            $berkas === 'Ditolak' && $validasi === 'Ditolak' => [
                'type' => 'danger', 'title' => 'Fasilitasi ditolak verifikator.',
                'message' => 'Periksa catatan, perbaiki, lalu pilih Kelola → Upload Ulang Fasilitasi.',
            ],
            $berkas === 'Disetujui' && $validasi === 'Menunggu Validasi' => [
                'type' => 'primary', 'title' => 'Disetujui peneliti.',
                'message' => 'Menunggu validasi dari verifikator.',
            ],
            $validasi === 'Diterima' && ! $adaNota => [
                'type' => 'info', 'title' => 'Validasi diterima.',
                'message' => 'Menunggu nota dinas dibuat.',
            ],
            $validasi === 'Diterima' && $adaNota => [
                'type' => 'success', 'title' => 'Nota dinas telah dibuat.',
                'message' => 'Cetak lewat Kelola → Cetak Nota Dinas. Pantau tahapan paraf di detail fasilitasi.',
            ],
            default => null,
        };
    }
}
