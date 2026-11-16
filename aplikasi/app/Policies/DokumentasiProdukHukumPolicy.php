<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\DokumentasiProdukHukum;
use App\Models\User;

class DokumentasiProdukHukumPolicy
{
    /**
     * Semua pengguna bisa melihat daftar dokumentasi.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }

    /**
     * Hanya Admin, Verifikator, dan Perangkat Daerah yang bisa melihat detail dokumentasi.
     */
    public function view(User $user, DokumentasiProdukHukum $dokumentasiProdukHukum): bool
    {
        return $user->hasRole('Admin')
            || $user->hasRole('Verifikator')
            || ($user->hasRole('Perangkat Daerah') && $user->perangkat_daerah_id == $dokumentasiProdukHukum->perangkat_daerah_id);
    }


    /**
     * Hanya Admin & Verifikator yang bisa membuat dokumentasi.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }

    /**
     * Hanya Admin & Verifikator yang bisa mengupdate dokumentasi.
     */
    public function update(User $user, DokumentasiProdukHukum $dokumentasiProdukHukum): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }

    /**
     * Hanya Admin yang bisa menghapus dokumentasi.
     */
    public function delete(User $user, DokumentasiProdukHukum $dokumentasiProdukHukum): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }

    /**
     * Hanya Admin yang bisa merestore dokumentasi yang terhapus.
     */
    public function restore(User $user, DokumentasiProdukHukum $dokumentasiProdukHukum): bool
    {
        return $user->hasRole('Admin');
    }

    /**
     * Hanya Admin yang bisa menghapus permanen dokumentasi.
     */
    public function forceDelete(User $user, DokumentasiProdukHukum $dokumentasiProdukHukum): bool
    {
        return $user->hasRole('Admin');
    }
}
