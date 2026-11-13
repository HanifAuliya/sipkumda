<?php

namespace App\Models;

use App\Support\TandaTanganPeneliti;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Penanda tangan nota dinas dan kolom Kepala Bagian Hukum di matriks. Bila terhubung ke akun
 * (user_id), pemiliknya, yaitu Verifikator, mengaturnya sendiri di Profil; Admin bisa membantu di
 * Kelola TTD. Baris tanpa akun untuk Kabag lama dan Plt/Plh.
 */
class TandaTangan extends Model
{
    use HasFactory;

    public const FOLDER = 'tanda_tangan/ttd';

    protected $table = 'tanda_tangan';

    protected $fillable = [
        'user_id',
        'nama_ttd',
        'nip_ttd',
        'jabatan',
        'file_ttd',
        'dibuat_oleh',
        'status'
    ];

    public function naskahNotaDinas()
    {
        return $this->hasMany(NaskahNotaDinas::class, 'tanda_tangan_id');
    }

    // Relasi ke tabel users
    public function user()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /** Akun Verifikator pemilik tanda tangan ini. */
    public function pemilik()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Gambar baru digambar ulang menjadi PNG bersih seperti tanda tangan peneliti, lalu disimpan.
     * Nota dinas membaca gambar dari baris ini, jadi file lama tidak dipakai lagi dan dibuang
     * (kecuali masih dipakai baris lain).
     */
    public function gantiGambar(string $sumber): void
    {
        $lama = $this->file_ttd;
        $path = self::FOLDER . '/ttd_' . Str::random(32) . '.png';
        Storage::disk('local')->put($path, TandaTanganPeneliti::png($sumber));

        $this->forceFill(['file_ttd' => $path])->save();

        if ($lama && ! self::where('file_ttd', $lama)->exists()) {
            Storage::disk('local')->delete($lama);
        }
    }
}
