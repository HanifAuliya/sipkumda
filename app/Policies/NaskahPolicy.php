<?php

namespace App\Policies;

use App\Models\Naskah;
use App\Models\User;

class NaskahPolicy
{
    /** Bagian Hukum melihat semua; PD hanya miliknya; Peneliti hanya yang ditugaskan kepadanya. */
    public function view(User $user, Naskah $naskah): bool
    {
        return $user->hasAnyRole(['Admin', 'Verifikator', 'Super Admin'])
            || $naskah->pemrakarsa_id === $user->id
            || $naskah->peneliti_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Perangkat Daerah');
    }
}
