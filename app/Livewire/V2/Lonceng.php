<?php

namespace App\Livewire\V2;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Lonceng notifikasi v2. Tanpa state publik supaya polling tetap ringan untuk banyak pengguna. */
class Lonceng extends Component
{
    private const BATAS = 15;

    public function buka(string $id)
    {
        $notifikasi = Auth::user()->notifications()->findOrFail($id);
        $notifikasi->markAsRead();

        if (($notifikasi->data['type'] ?? null) === 'naskah' && filled($notifikasi->data['slug'] ?? null)) {
            $this->dispatch('bukaDetailNaskah', slug: $notifikasi->data['slug']);
        }
    }

    public function tandaiSemua(): void
    {
        Auth::user()->unreadNotifications()->update(['read_at' => now()]);
    }

    public function render()
    {
        $user = Auth::user();

        return view('v2.lonceng', [
            'daftar' => $user->notifications()->latest()->limit(self::BATAS)->get(),
            'belumDibaca' => $user->unreadNotifications()->count(),
        ]);
    }
}
