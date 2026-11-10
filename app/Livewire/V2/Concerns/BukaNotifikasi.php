<?php

namespace App\Livewire\V2\Concerns;

use App\Models\Naskah;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/** Membuka notifikasi: langsung ke layar kerja bila giliran pengguna, selain itu ke detail naskah. */
trait BukaNotifikasi
{
    public function buka(string $id)
    {
        $user = Auth::user();
        $notifikasi = $user->notifikasiNaskah()->findOrFail($id);
        $notifikasi->markAsRead();
        $this->dispatch('notifikasi-berubah');

        // Pemberitahuan sistem, misalnya backup gagal, tidak terkait naskah mana pun.
        $slug = $notifikasi->data['slug'] ?? null;
        if (! $slug) {
            return null;
        }

        $naskah = Naskah::where('slug', $slug)->first();

        if (! $naskah || Gate::denies('view', $naskah)) {
            $this->dispatch('swal:toast', ['type' => 'info', 'title' => 'Naskah tidak tersedia', 'message' => 'Naskah ini sudah dibuang atau tidak bisa Anda buka.']);

            return null;
        }

        if ($naskah->giliranMilik($user) || $user->pemantau()) {
            return $this->redirect($naskah->tautanUntuk($user));
        }

        $this->dispatch('bukaDetailNaskah', slug: $naskah->slug);

        return null;
    }
}
