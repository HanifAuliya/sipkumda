<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaskahVersi extends Model
{
    protected $table = 'naskah_versi';

    protected $fillable = ['naskah_id', 'versi', 'tahap', 'user_id', 'isi'];

    protected function casts(): array
    {
        return ['isi' => 'array'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
