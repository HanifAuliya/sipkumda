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

    public const LABEL_STATUS = [
        self::BELUM => 'Belum diperiksa',
        self::TETAP => 'Tetap',
        self::DIUBAH => 'Diubah',
        self::DIHAPUS => 'Dihapus',
        self::PERLU_PD => 'Perlu perbaikan PD',
        self::DISESUAIKAN => 'Sudah disesuaikan',
    ];

    public const WARNA_STATUS = [
        self::BELUM => 'slate',
        self::TETAP => 'success',
        self::DIUBAH => 'primary',
        self::DIHAPUS => 'danger',
        self::PERLU_PD => 'warning',
        self::DISESUAIKAN => 'info',
    ];

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

    /** Teks yang masuk ke naskah: kolom 4 kalau terisi, selain itu kolom 3; baris dihapus tidak dicetak. */
    public function teksAkhir(): ?string
    {
        if ($this->status === self::DIHAPUS) {
            return null;
        }

        return filled($this->rumusan_penyempurnaan) ? $this->rumusan_penyempurnaan : $this->rumusan_awal;
    }
}
