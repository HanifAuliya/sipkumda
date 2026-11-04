<?php

namespace App\Models;

use App\Enums\TahapNaskah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Naskah extends Model
{
    public const JENIS = ['Peraturan Bupati', 'Surat Keputusan'];

    protected $table = 'naskah';

    protected $fillable = [
        'jenis',
        'tentang',
        'pemrakarsa_id',
        'peneliti_id',
        'tahap',
        'giliran',
        'catatan_tahap',
        'nomor_nota',
        'tanggal_nota',
        'diajukan_at',
        'terkunci_at',
    ];

    protected $attributes = [
        'tahap' => 'draf',
        'giliran' => 1,
    ];

    protected function casts(): array
    {
        return [
            'tahap' => TahapNaskah::class,
            'tanggal_nota' => 'date',
            'diajukan_at' => 'datetime',
            'terkunci_at' => 'datetime',
        ];
    }

    /** Jalankan pembuatan naskah bergiliran supaya dua pengajuan tidak mendapat nomor yang sama. */
    public static function denganNomorTerkunci(callable $buat): mixed
    {
        return Cache::lock('nomor-naskah', 15)->block(10, fn () => DB::transaction($buat));
    }

    protected static function booted(): void
    {
        // Nomor NPB-2026-001 / NSK-2026-001, berurutan per jenis per tahun. Awalan berbeda dari alur lama.
        static::creating(function (Naskah $naskah) {
            $awalan = ['Peraturan Bupati' => 'NPB', 'Surat Keputusan' => 'NSK'][$naskah->jenis]
                ?? throw new \InvalidArgumentException('Jenis naskah tidak valid.');
            $tahun = now()->year;

            $terakhir = self::where('nomor', 'like', "{$awalan}-{$tahun}-%")->orderByDesc('id')->value('nomor');
            $berikutnya = $terakhir ? (int) Str::afterLast($terakhir, '-') + 1 : 1;

            $naskah->nomor = sprintf('%s-%d-%03d', $awalan, $tahun, $berikutnya);
            $naskah->slug = (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function pemrakarsa()
    {
        return $this->belongsTo(User::class, 'pemrakarsa_id');
    }

    public function peneliti()
    {
        return $this->belongsTo(User::class, 'peneliti_id');
    }

    public function baris()
    {
        return $this->hasMany(NaskahBaris::class)->orderBy('urutan');
    }

    public function berkas()
    {
        return $this->hasMany(NaskahBerkas::class)->latest('id');
    }

    public function riwayat()
    {
        return $this->hasMany(NaskahRiwayat::class)->latest('id');
    }

    public function versi()
    {
        return $this->hasMany(NaskahVersi::class)->latest('versi');
    }

    /** Naskah yang boleh dilihat pengguna: sejalan dengan NaskahPolicy::view. */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Admin', 'Verifikator', 'Super Admin'])) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('pemrakarsa_id', $user->id)->orWhere('peneliti_id', $user->id));
    }

    /** Naskah yang sedang menunggu tindakan pengguna ini. */
    public function scopeMenunggu(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where(fn ($s) => $s->where('pemrakarsa_id', $user->id)->whereIn('tahap', TahapNaskah::menungguPeran('Perangkat Daerah')))
                ->orWhere(fn ($s) => $s->where('peneliti_id', $user->id)->whereIn('tahap', TahapNaskah::menungguPeran('Peneliti')));

            foreach (['Admin', 'Verifikator'] as $peran) {
                if ($user->hasRole($peran)) {
                    $q->orWhereIn('tahap', TahapNaskah::menungguPeran($peran));
                }
            }
        });
    }

    /** Apakah giliran kerja saat ini ada pada pengguna ini? */
    public function giliranMilik(User $user): bool
    {
        return match ($this->tahap->peran()) {
            'Perangkat Daerah' => $this->pemrakarsa_id === $user->id,
            'Peneliti' => $this->peneliti_id === $user->id,
            default => $user->hasRole($this->tahap->peran()),
        };
    }
}
