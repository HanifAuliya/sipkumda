<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaskahNotaDinas extends Model
{
    protected $table = 'naskah_nota_dinas';

    protected $fillable = ['naskah_id', 'nomor_inputan', 'nomor_nota', 'tanggal_nota', 'kepada', 'tanda_tangan_id', 'file_ttd'];

    protected function casts(): array
    {
        return ['tanggal_nota' => 'date'];
    }

    public function naskah()
    {
        return $this->belongsTo(Naskah::class);
    }

    public function tandaTangan()
    {
        return $this->belongsTo(TandaTangan::class, 'tanda_tangan_id');
    }

    /**
     * Path gambar tanda tangan, atau null bila filenya tidak ada. Tanda tangan yang digambar langsung
     * saat menerbitkan (file_ttd) didahulukan dari gambar tersimpan penanda tangannya.
     */
    public function pathTandaTangan(): ?string
    {
        $file = $this->file_ttd ?: $this->tandaTangan?->file_ttd;
        $path = $file ? \Illuminate\Support\Facades\Storage::disk('local')->path(TandaTangan::FOLDER . '/' . basename($file)) : null;

        return $path && is_file($path) ? $path : null;
    }
}
