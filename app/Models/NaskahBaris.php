<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Satu baris matriks: kolom 3 (rumusan_awal), kolom 4 (rumusan_penyempurnaan), kolom 5 (keterangan).
 */
class NaskahBaris extends Model
{
    public const BELUM = 'belum';
    public const TETAP = 'tetap';
    public const DIUBAH = 'diubah';
    public const DIHAPUS = 'dihapus';
    public const PERLU_PD = 'perlu_pd';
    public const DISESUAIKAN = 'disesuaikan';
    /** PD mengusulkan baris dihapus; berlaku setelah Peneliti menerimanya (menjadi Dihapus). */
    public const USUL_HAPUS = 'usul_hapus';

    public const LABEL_STATUS = [
        self::BELUM => 'Belum diperiksa',
        self::TETAP => 'Tetap',
        self::DIUBAH => 'Diubah',
        self::DIHAPUS => 'Dihapus',
        self::PERLU_PD => 'Perlu perbaikan PD',
        self::DISESUAIKAN => 'Sudah disesuaikan',
        self::USUL_HAPUS => 'Diusulkan dihapus PD',
    ];

    public const WARNA_STATUS = [
        self::BELUM => 'slate',
        self::TETAP => 'success',
        self::DIUBAH => 'primary',
        self::DIHAPUS => 'danger',
        self::PERLU_PD => 'warning',
        self::DISESUAIKAN => 'info',
        self::USUL_HAPUS => 'danger',
    ];

    /** Status yang berarti rumusannya berbeda dari usulan awal, atau diminta diperbaiki. */
    public const BERUBAH = [self::DIUBAH, self::DIHAPUS, self::PERLU_PD, self::DISESUAIKAN, self::USUL_HAPUS];

    /** Bagi Peneliti: belum diperiksa, termasuk perbaikan PD yang belum diterima (Terima Perbaikan). */
    public const BELUM_DIPERIKSA = [self::BELUM, self::DISESUAIKAN, self::USUL_HAPUS];

    /** Perubahan PD yang menunggu keputusan Peneliti. */
    public const USULAN_PD = [self::DISESUAIKAN, self::USUL_HAPUS];

    /** Baris yang tidak ikut dicetak: dihapus, atau diusulkan dihapus PD (naskah mengikuti usulan terakhir). */
    public const TERHAPUS = [self::DIHAPUS, self::USUL_HAPUS];

    /** Bagian yang barisnya bisa lebih dari satu. Judul, Penutup, dan Lampiran selalu satu baris. */
    public const BAGIAN_BERULANG = ['menimbang', 'mengingat', 'memperhatikan', 'diktum', 'batang_tubuh'];

    /**
     * Bagian yang barisnya bisa ditambah. Lampiran wajib ada, tetapi satu baris berarti satu lampiran,
     * jadi Lampiran I, II, dst. ditambah per baris; baris lampiran terakhir tidak bisa dihapus.
     */
    public const BAGIAN_BISA_DITAMBAH = [...self::BAGIAN_BERULANG, 'lampiran'];

    protected $table = 'naskah_baris';

    protected $fillable = [
        'naskah_id',
        'urutan',
        'bagian',
        'label',
        'rumusan_awal',
        'rumusan_penyempurnaan',
        'keterangan',
        'status',
        'giliran_diubah',
        'diubah_oleh',
    ];

    protected $attributes = [
        'status' => self::BELUM,
    ];

    public function naskah()
    {
        return $this->belongsTo(Naskah::class);
    }

    public function pengubah()
    {
        return $this->belongsTo(User::class, 'diubah_oleh');
    }

    /** Baris tanpa rumusan awal: tambahan saat penelitian, jadi boleh dibuang tanpa menghilangkan usulan PD. */
    public function sisipan(): bool
    {
        return blank($this->rumusan_awal) && in_array($this->bagian, self::BAGIAN_BISA_DITAMBAH, true);
    }

    public function terhapus(): bool
    {
        return in_array($this->status, self::TERHAPUS, true);
    }

    /** Teks yang masuk ke naskah: kolom 4 kalau terisi, selain itu kolom 3; baris dihapus tidak dicetak. */
    public function teksAkhir(): ?string
    {
        if ($this->terhapus()) {
            return null;
        }

        return filled($this->rumusan_penyempurnaan) ? $this->rumusan_penyempurnaan : $this->rumusan_awal;
    }
}
