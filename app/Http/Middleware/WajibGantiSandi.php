<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna yang masuk dengan kata sandi sementara dari Admin diarahkan ke Profil sampai ia
 * menggantinya sendiri. Permintaan Livewire dibiarkan, supaya formulir di Profil tetap bekerja.
 */
class WajibGantiSandi
{
    private const BOLEH = ['profile.edit', 'panduan', 'logout', 'foto-profil', 'tanda-tangan-saya'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->wajib_ganti_sandi
            && ! $request->routeIs(...self::BOLEH)
            && ! $request->hasHeader('X-Livewire')
            && $request->isMethod('GET')) {
            return redirect()->route('profile.edit')->with('pesan', 'Ganti kata sandi sementara Anda dulu sebelum memakai SIFKUMDA.');
        }

        return $next($request);
    }
}
