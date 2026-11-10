<?php

namespace App\Models;

use App\Enums\TahapNaskah;
use App\Notifications\NaskahNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Naskah extends Model
{
    public const JENIS = ['Peraturan Bupati', 'Keputusan Bupati'];

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
        'tahapan_ttd',
        'dokumentasi_id',
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
            'tahapan_ttd' => 'array',
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
            $awalan = ['Peraturan Bupati' => 'NPB', 'Keputusan Bupati' => 'NSK'][$naskah->jenis]
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

    public function notaDinas()
    {
        return $this->hasOne(NaskahNotaDinas::class);
    }

    public function dokumentasi()
    {
        return $this->belongsTo(DokumentasiProdukHukum::class, 'dokumentasi_id');
    }

    /** Naskah yang boleh dilihat pengguna: sejalan dengan NaskahPolicy::view. */
    public function scopeTerlihatOleh(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Admin', 'Verifikator', 'Super Admin'])) {
            return $query;
        }

        return $query->where(fn ($q) => $q->where('pemrakarsa_id', $user->id)
            ->orWhere('peneliti_id', $user->id)
            ->when($user->hasRole('Tamu'), fn ($s) => $s->orWhereIn('tahap', TahapNaskah::TERLIHAT_TAMU)));
    }

    /** Naskah yang sedang menunggu tindakan pengguna ini. */
    public function scopeMenunggu(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where(fn ($s) => $s->where('pemrakarsa_id', $user->id)->whereIn('tahap', TahapNaskah::menungguPeran('Perangkat Daerah')))
                ->orWhere(fn ($s) => $s->where('peneliti_id', $user->id)->whereIn('tahap', TahapNaskah::menungguPeran('Peneliti')));

            foreach (['Admin', 'Verifikator', 'Tamu'] as $peran) {
                if ($user->hasRole($peran)) {
                    $q->orWhereIn('tahap', TahapNaskah::menungguPeran($peran));
                }
            }
        });
    }

    /** Apakah giliran kerja saat ini ada pada pengguna ini? */
    /** Notifikasi tentang naskah ini, untuk semua penerima. */
    public function notifikasi(): Builder
    {
        return DatabaseNotification::query()
            ->where('type', NaskahNotification::class)
            ->where('data->slug', $this->slug);
    }

    /** Pengguna sudah membuka naskahnya, jadi notifikasinya tidak perlu lagi menunggu dibaca. */
    public function tandaiNotifikasiDibaca(User $user): void
    {
        $user->notifikasiNaskah()->whereNull('read_at')->where('data->slug', $this->slug)->update(['read_at' => now()]);
    }

    /** Tamu hanya memantau tanda tangan, jadi dibawa ke halaman pantau, bukan layar kerja matriks. */
    public function tautanUntuk(User $user): string
    {
        return $user->pemantau() ? route('v2.naskah.pantau', $this) : route('v2.naskah.kerja', $this);
    }

    public function giliranMilik(User $user): bool
    {
        return match ($this->tahap->peran()) {
            'Perangkat Daerah' => $this->pemrakarsa_id === $user->id,
            'Peneliti' => $this->peneliti_id === $user->id,
            default => $user->hasAnyRole($this->tahap->pemegang()),
        };
    }
}
