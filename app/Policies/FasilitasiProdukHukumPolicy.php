<?php

namespace App\Policies;

use App\Models\FasilitasiProdukHukum;
use App\Models\User;

class FasilitasiProdukHukumPolicy
{
    /**
     * Determine whether the user can view any fasilitasi (list of models).
     */
    public function viewAny(User $user): bool
    {
        // Admin, Verifikator, dan Peneliti bisa melihat semua fasilitasi
        if ($user->hasRole(['Super Admin', 'Admin', 'Verifikator', 'Peneliti'])) {
            return true;
        }

        // Perangkat Daerah hanya bisa melihat fasilitasi yang terkait dengan rancangan miliknya
        if ($user->hasRole('Perangkat Daerah')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can view a specific fasilitasi.
     */
    public function view(User $user, FasilitasiProdukHukum $fasilitasi): bool
    {
        // Admin, Verifikator, dan Peneliti bisa melihat semua fasilitasi
        if ($user->hasRole(['Super Admin', 'Admin', 'Verifikator', 'Peneliti'])) {
            return true;
        }

        // Perangkat Daerah hanya bisa melihat fasilitasi yang terkait dengan rancangan miliknya
        if ($user->hasRole('Perangkat Daerah') && $fasilitasi->rancangan->id_user === $user->id) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create a fasilitasi.
     */
    public function create(User $user): bool
    {
        // Hanya Perangkat Daerah yang bisa mengajukan fasilitasi
        return $user->hasRole('Perangkat Daerah');
    }

    /**
     * Determine whether the user can update fasilitasi.
     */
    public function update(User $user, FasilitasiProdukHukum $fasilitasi): bool
    {
        // Admin dan Verifikator bisa mengupdate fasilitasi
        if ($user->hasRole(['Admin', 'Verifikator'])) {
            return true;
        }

        // Perangkat Daerah hanya bisa mengupdate jika fasilitasi belum divalidasi
        if (
            $user->hasRole('Perangkat Daerah') &&
            $fasilitasi->rancangan->id_user === $user->id &&
            $fasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi'
        ) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete fasilitasi.
     */
    public function delete(User $user, FasilitasiProdukHukum $fasilitasi): bool
    {
        // Hanya Admin yang bisa menghapus fasilitasi
        return $user->hasRole('Admin', 'Verifikator');
    }

    /**
     * Determine whether the user can restore fasilitasi.
     */
    public function restore(User $user, FasilitasiProdukHukum $fasilitasi): bool
    {
        // Hanya Admin yang bisa merestore fasilitasi
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can permanently delete fasilitasi.
     */
    public function forceDelete(User $user, FasilitasiProdukHukum $fasilitasi): bool
    {
        // Hanya Admin yang bisa menghapus fasilitasi secara permanen
        return $user->hasRole('Admin');
    }
}
