<?php

namespace App\Policies;

use Illuminate\Auth\Access\Response;
use App\Models\NotaDinas;
use App\Models\User;

class NotaDinasPolicy
{
    /**
     * Perangkat Daerah hanya bisa melihat Nota Dinas miliknya sendiri.
     * Admin dan Verifikator bisa melihat semua.
     */
    public function view(User $user, NotaDinas $notaDinas): bool
    {
        if ($user->hasRole('Perangkat Daerah')) {
            return $notaDinas->fasilitasi->rancangan->id_user == $user->id;
        }
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }

    /**
     * Admin & Verifikator bisa membuat Nota Dinas.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }

    /**
     * Admin & Verifikator bisa memperbarui Nota Dinas.
     */
    public function update(User $user, NotaDinas $notaDinas): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }

    /**
     * Admin & Verifikator bisa menghapus Nota Dinas.
     */
    public function delete(User $user,  NotaDinas $notaDinas = null): bool
    {
        return $user->hasRole('Admin') || $user->hasRole('Verifikator');
    }
}
