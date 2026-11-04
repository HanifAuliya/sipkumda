<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaskahBerkas extends Model
{
    public const NOTA_DINAS_PD = 'nota_dinas_pd';
    public const WORD_ASLI = 'word_asli';
    public const LAMPIRAN = 'lampiran';
    public const BAHAN_PENDUKUNG = 'bahan_pendukung';

    public const LABEL = [
        self::NOTA_DINAS_PD => 'Nota Dinas PD',
        self::WORD_ASLI => 'Word asli',
        self::LAMPIRAN => 'Lampiran naskah',
        self::BAHAN_PENDUKUNG => 'Bahan pendukung',
    ];

    protected $table = 'naskah_berkas';

    protected $fillable = ['naskah_id', 'jenis', 'path', 'nama_asli', 'aktif', 'diunggah_oleh'];

    protected function casts(): array
    {
        return ['aktif' => 'boolean'];
    }

    public function naskah()
    {
        return $this->belongsTo(Naskah::class);
    }

    public function pengunggah()
    {
        return $this->belongsTo(User::class, 'diunggah_oleh');
    }
}
