<?php

namespace App\Policies;

use App\Models\RancanganProdukHukum;
use App\Models\User;

class RancanganProdukHukumPolicy
{
    /**
     * Determine whether the user can view any models (list of models).
     */
    public function viewAny(User $user): bool
    {
        // Admin, Verifikator, dan Peneliti bisa melihat semua rancangan
        if ($user->hasRole(['Admin', 'Verifikator', 'Peneliti'])) {
            return true;
        }

        // Perangkat Daerah hanya bisa melihat rancangan miliknya
        if ($user->hasRole('Perangkat_Daerah')) {
            return true;
        }

        // Tamu (guest) tidak bisa melihat apa pun
        return false;
    }

    /**
     * Determine whether the user can view the specific model.
     */
    public function view(User $user, RancanganProdukHukum $rancanganProdukHukum): bool
    {
        // Admin, Verifikator, dan Peneliti bisa melihat semua rancangan
        if ($user->hasRole(['Admin', 'Verifikator', 'Peneliti'])) {
            return true;
        }

        // Perangkat Daerah hanya bisa melihat rancangan miliknya
        if ($user->hasRole('Perangkat_Daerah') && $rancanganProdukHukum->id_user === $user->id) {
            return true;
        }

        // Tamu (guest) tidak bisa melihat apa pun
        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Hanya Perangkat Daerah yang bisa menambahkan rancangan
        return $user->hasRole('Perangkat_Daerah');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, RancanganProdukHukum $rancanganProdukHukum): bool
    {
        // Admin bisa mengupdate semua rancangan
        if ($user->hasRole('Admin')) {
            return true;
        }

        // Perangkat Daerah hanya bisa mengupdate rancangan miliknya, dengan status tertentu
        if ($user->hasRole('Perangkat_Daerah') && $rancanganProdukHukum->id_user === $user->id) {
            return true;
        }

        // Verifikator dan Peneliti tidak dapat mengupdate rancangan
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RancanganProdukHukum $rancanganProdukHukum): bool
    {
        // Hanya Admin yang bisa menghapus rancangan
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, RancanganProdukHukum $rancanganProdukHukum): bool
    {
        // Hanya Admin yang bisa merestore rancangan
        return $user->hasRole('Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, RancanganProdukHukum $rancanganProdukHukum): bool
    {
        // Hanya Admin yang bisa menghapus permanen
        return $user->hasRole('Admin');
    }
}
