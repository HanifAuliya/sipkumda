<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Revisi extends Model
{
    use HasFactory;

    protected $table = 'revisi';
    protected $primaryKey = 'id_revisi';

    protected $fillable = [
        'id_rancangan',
        'revisi_rancangan',
        'revisi_matrik',
        'catatan_revisi',
        'catatan_validasi',
        'id_user',
        'status_revisi',
        'status_validasi',
        'tanggal_peneliti_ditunjuk',
        'tanggal_revisi',
        'tanggal_validasi',

    ];

    // Relasi ke User (Peneliti)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }


    // Relasi ke RancanganProdukHukum
    public function rancangan()
    {
        return $this->belongsTo(RancanganProdukHukum::class, 'id_rancangan');
    }

    public function peneliti()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }
}
