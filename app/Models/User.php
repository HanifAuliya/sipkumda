<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\NaskahNotification;
use App\Support\FotoProfil;
use App\Support\TandaTanganPeneliti;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama_user',
        'NIP',
        'email',
        'email_verified_at', // Pastikan kolom ini ada di properti fillable
        'password',
        'perangkat_daerah_id',
        'no_wa',
    ];

    /** Sama dengan nilai bawaan kolomnya, supaya akun yang baru dibuat langsung menerima WhatsApp. */
    protected $attributes = ['wa_aktif' => true];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::deleted(function (self $user) {
            FotoProfil::buang($user->foto_profil);
            TandaTanganPeneliti::buang($user->file_ttd);
        });
    }

    /** Tamu memantau tahapan tanda tangan; akun yang juga punya peran kerja tetap memakai layar kerja. */
    public function pemantau(): bool
    {
        return $this->hasRole('Tamu') && ! $this->hasAnyRole(['Admin', 'Super Admin', 'Verifikator', 'Peneliti', 'Perangkat Daerah']);
    }

    /** Sama seperti foto: nama file acak berganti setiap unggah. */
    public function ttdUrl(): ?string
    {
        return $this->file_ttd
            ? route('tanda-tangan-saya', ['v' => substr(pathinfo($this->file_ttd, PATHINFO_FILENAME), 0, 12)])
            : null;
    }

    /** Nama filenya acak dan berganti setiap unggah, jadi browser tidak menampilkan foto lama. */
    public function fotoUrl(): string
    {
        return $this->foto_profil
            ? route('foto-profil', ['v' => substr(pathinfo($this->foto_profil, PATHINFO_FILENAME), 0, 12)])
            : asset('assets/img/theme/profile.png');
    }

    /** Notifikasi alur v2; notifikasi alur lama punya loncengnya sendiri. */
    public function notifikasiNaskah()
    {
        return $this->notifications()->where('type', NaskahNotification::class);
    }

    public function perangkatDaerah()
    {
        return $this->belongsTo(PerangkatDaerah::class, 'perangkat_daerah_id', 'id');
    }

    // Relasi ke Rancangan Produk Hukum
    public function rancangan()
    {
        return $this->hasMany(RancanganProdukHukum::class, 'id_user');
    }

    public function revisi()
    {
        return $this->hasMany(Revisi::class, 'id_user', 'id');
    }



    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wa_aktif' => 'boolean',
        ];
    }
}
