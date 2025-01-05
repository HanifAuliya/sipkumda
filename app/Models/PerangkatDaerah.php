<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerangkatDaerah extends Model
{
    use HasFactory;

    // Nama tabel secara eksplisit
    protected $table = 'perangkat_daerah';

    protected $fillable = ['nama_perangkat_daerah'];

    public function users()
    {
        return $this->hasMany(User::class, 'perangkat_daerah_id', 'id');
    }
    public function rancanganProdukHukum()
    {
        return $this->hasMany(RancanganProdukHukum::class, 'perangkat_daerah_id', 'id');
    }
}
