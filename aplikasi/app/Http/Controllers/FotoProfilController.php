<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Tidak menerima ID pengguna, jadi hanya bisa membuka foto milik sendiri. */
class FotoProfilController extends Controller
{
    public function __invoke(Request $request)
    {
        $path = $request->user()->foto_profil;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            return redirect(asset('assets/img/theme/profile.png'));
        }

        // Alamatnya berubah setiap foto diganti, jadi aman disimpan lama di browser.
        return Storage::disk('local')->response($path, 'foto-profil.jpg', [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }
}
