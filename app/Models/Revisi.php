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
        'id_user',
        'revisi_rancangan',
        'revisi_matrik',
        'catatan_revisi',
        'status_revisi',
        'tanggal_revisi',
    ];

    // Relasi ke User (Peneliti)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relasi ke RancanganProdukHukum
    public function rancangan()
    {
        return $this->belongsTo(RancanganProdukHukum::class, 'id_rancangan');
    }

    public function peneliti()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
