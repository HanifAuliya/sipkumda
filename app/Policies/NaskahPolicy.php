<?php

namespace App\Policies;

use App\Enums\TahapNaskah;
use App\Models\Naskah;
use App\Models\User;

class NaskahPolicy
{
    /** Bagian Hukum melihat semua; PD hanya miliknya; Peneliti hanya yang ditugaskan kepadanya; Tamu sejak penandatanganan. */
    public function view(User $user, Naskah $naskah): bool
    {
        return $user->hasAnyRole(['Admin', 'Verifikator', 'Super Admin'])
            || $naskah->pemrakarsa_id === $user->id
            || $naskah->peneliti_id === $user->id
            || ($user->hasRole('Tamu') && in_array($naskah->tahap, TahapNaskah::TERLIHAT_TAMU, true));
    }

    /** Tamu boleh membaca naskah di layar, tetapi berkas dan hasil cetaknya tidak untuk diunduh. */
    public function unduh(User $user, Naskah $naskah): bool
    {
        return $this->view($user, $naskah) && ! $user->hasRole('Tamu');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('Perangkat Daerah');
    }
}
