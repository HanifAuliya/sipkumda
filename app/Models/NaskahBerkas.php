<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaskahBerkas extends Model
{
    public const NOTA_DINAS_PD = 'nota_dinas_pd';
    public const WORD_ASLI = 'word_asli';
    public const LAMPIRAN = 'lampiran';
    public const BAHAN_PENDUKUNG = 'bahan_pendukung';

    /**
     * Naskah yang sudah direvisi PD sesuai hasil penelitian, diunggah saat Ajukan fasilitasi sebagai bukti
     * semua perubahan sudah diterapkan. Yang terbaru berlaku; yang lama tersimpan sebagai versi lama.
     */
    public const NASKAH_FASILITASI = 'naskah_fasilitasi';

    /** PDF nota dinas yang dibekukan saat terbit; versi yang diterbitkan ulang menggantikan yang aktif. */
    public const NOTA_DINAS = 'nota_dinas';

    /** PDF lampiran yang menjadi isi satu baris lampiran di matriks (NaskahBaris::FORMAT_BERKAS); satu berkas per versi. */
    public const LAMPIRAN_BARIS = 'lampiran_baris';

    public const LABEL = [
        self::NOTA_DINAS_PD => 'Nota Dinas PD',
        self::WORD_ASLI => 'Word asli',
        self::LAMPIRAN => 'Lampiran naskah',
        self::BAHAN_PENDUKUNG => 'Bahan pendukung',
        self::NASKAH_FASILITASI => 'Naskah hasil perbaikan',
        self::NOTA_DINAS => 'Nota Dinas',
        self::LAMPIRAN_BARIS => 'Lampiran (PDF di matriks)',
    ];

    protected $table = 'naskah_berkas';

    protected $fillable = ['naskah_id', 'jenis', 'path', 'nama_asli', 'aktif', 'keterangan', 'diunggah_oleh'];

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
