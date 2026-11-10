<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Satu pesan WhatsApp di antrean (lihat App\Support\Wa\AntreanWa). */
class PesanWa extends Model
{
    public const MENUNGGU = 'menunggu';
    public const TERKIRIM = 'terkirim';
    public const DILEWATI = 'dilewati';
    public const GAGAL = 'gagal';

    public const LABEL = [
        self::MENUNGGU => 'Menunggu',
        self::TERKIRIM => 'Terkirim',
        self::DILEWATI => 'Dilewati',
        self::GAGAL => 'Gagal',
    ];

    public const WARNA = [
        self::MENUNGGU => 'warning',
        self::TERKIRIM => 'success',
        self::DILEWATI => 'slate',
        self::GAGAL => 'danger',
    ];

    protected $table = 'pesan_wa';

    protected $fillable = ['user_id', 'nomor', 'notification_id', 'isi', 'status', 'percobaan', 'galat', 'dikirim_at'];

    protected function casts(): array
    {
        return ['dikirim_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeMenunggu(Builder $query): Builder
    {
        return $query->where('status', self::MENUNGGU);
    }

    /** Pesan yang sudah lama menunggu: tanda sambungan putus atau penjadwal tidak berjalan. */
    public static function tertundaLama(int $menit = 10): int
    {
        return self::menunggu()->where('created_at', '<', now()->subMinutes($menit))->count();
    }
}
