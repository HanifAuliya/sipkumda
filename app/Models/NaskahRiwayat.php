<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaskahRiwayat extends Model
{
    protected $table = 'naskah_riwayat';

    protected $fillable = ['naskah_id', 'baris_id', 'user_id', 'aksi', 'catatan', 'sebelum', 'sesudah'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function baris()
    {
        return $this->belongsTo(NaskahBaris::class, 'baris_id');
    }
}
