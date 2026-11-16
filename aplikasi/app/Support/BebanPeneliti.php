<?php

namespace App\Support;

use App\Enums\TahapNaskah as T;
use App\Models\Naskah;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Rancangan yang sedang dipegang tiap peneliti, supaya penugasan bisa dibagi rata. Dipakai halaman
 * Pilih Peneliti dan jendela Tugaskan/Ganti Peneliti.
 */
class BebanPeneliti
{
    /** Sejak ditugaskan sampai fasilitasi divalidasi: naskah masih bisa kembali kepada penelitinya. */
    public const BERJALAN = [T::Penelitian, T::PerbaikanPd, T::ValidasiRancangan, T::Disetujui,
        T::PersetujuanFasilitasi, T::ValidasiFasilitasi, T::PerbaikanFasilitasi, T::CekPeneliti];

    /** Giliran peneliti sendiri. */
    public const MENUNGGU = [T::Penelitian, T::PersetujuanFasilitasi, T::CekPeneliti];

    /**
     * Peneliti beserta relasi naskahAktif (terbaru dulu), beban_aktif, dan beban_menunggu.
     *
     * @return Collection<int, User>
     */
    public static function daftar(?int $kecuali = null): Collection
    {
        $naskah = Naskah::with('pemrakarsa.perangkatDaerah')
            ->whereIn('tahap', self::BERJALAN)->whereNotNull('peneliti_id')
            ->latest('updated_at')->get()
            ->groupBy('peneliti_id');

        return User::role('Peneliti')->when($kecuali, fn ($q) => $q->whereKeyNot($kecuali))
            ->orderBy('nama_user')->get(['id', 'nama_user'])
            ->each(function (User $u) use ($naskah) {
                $milik = $naskah->get($u->id, collect())->values();
                $u->setRelation('naskahAktif', $milik);
                $u->beban_aktif = $milik->count();
                $u->beban_menunggu = $milik->filter(fn (Naskah $n) => in_array($n->tahap, self::MENUNGGU, true))->count();
            });
    }
}
