<?php

namespace App\Models;

use App\Enums\TahapNaskah;
use App\Notifications\NaskahNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
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

    /** Awalan nomor rancangan per jenis; berbeda dari alur lama. */
    private const AWALAN = ['Peraturan Bupati' => 'NPB', 'Keputusan Bupati' => 'NSK'];

    protected static function booted(): void
    {
        static::creating(function (Naskah $naskah) {
            if (! isset(self::AWALAN[$naskah->jenis])) {
                throw new \InvalidArgumentException('Jenis naskah tidak valid.');
            }

            $naskah->slug = (string) Str::uuid();
        });

        // Draf belum bernomor; naskah yang keluar dari tahap Draf selalu bernomor, lewat jalur apa pun
        // (aksi Ajukan, seeder, atau naskah yang langsung dibuat di tahap lanjut).
        static::saving(function (Naskah $naskah) {
            if ($naskah->tahap !== TahapNaskah::Draf) {
                $naskah->beriNomor();
            }
        });
    }

    /**
     * Memberi nomor NPB-2026-001 / NSK-2026-001 saat pertama kali diajukan, berurutan per jenis per tahun.
     *
     * Baris penghitung di naskah_nomor_urut dikunci (SELECT ... FOR UPDATE) sampai transaksinya selesai,
     * jadi pengajuan yang bersamaan dari beberapa Perangkat Daerah antre di database dan masing-masing
     * mendapat nomor berikutnya. Penghitung hanya naik, sehingga nomor rancangan yang dihapus Admin tidak
     * dipakai lagi. Indeks unik pada kolom nomor menjadi pengaman terakhir.
     */
    public function beriNomor(): void
    {
        if ($this->nomor !== null) {
            return;
        }

        $awalan = self::AWALAN[$this->jenis] ?? throw new \InvalidArgumentException('Jenis naskah tidak valid.');
        $tahun = now()->year;

        $this->nomor = DB::transaction(function () use ($awalan, $tahun) {
            $kunci = ['awalan' => $awalan, 'tahun' => $tahun];

            // Baris penghitung tahun baru dibuat sekali; pengajuan lain yang bersamaan cukup melewatinya.
            DB::table('naskah_nomor_urut')->insertOrIgnore($kunci + ['terakhir' => 0, 'created_at' => now(), 'updated_at' => now()]);

            $berikutnya = (int) DB::table('naskah_nomor_urut')->where($kunci)->lockForUpdate()->value('terakhir') + 1;
            DB::table('naskah_nomor_urut')->where($kunci)->update(['terakhir' => $berikutnya, 'updated_at' => now()]);

            return sprintf('%s-%d-%03d', $awalan, $tahun, $berikutnya);
        });
    }

    /** Nomor untuk ditampilkan: draf yang belum diajukan belum bernomor. */
    public function getNomorTampilAttribute(): string
    {
        return $this->nomor ?? 'Draf';
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
