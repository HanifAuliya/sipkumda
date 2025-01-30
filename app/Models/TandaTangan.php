<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TandaTangan extends Model
{
    use HasFactory;

    protected $table = 'tanda_tangan';

    protected $fillable = [
        'nama_ttd',
        'file_ttd',
        'dibuat_oleh',
        'status',
    ];

    // Relasi ke tabel nota_dinas
    public function notaDinas()
    {
        return $this->hasMany(NotaDinas::class, 'tanda_tangan_id');
    }

    // Relasi ke tabel users
    public function user()
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }
}
