<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/** Pratinjau tanda tangan di halaman Profil. Tidak menerima ID pengguna, jadi hanya bisa membuka milik sendiri. */
class TandaTanganSayaController extends Controller
{
    public function __invoke(Request $request)
    {
        $path = $request->user()->file_ttd;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        // Alamatnya berubah setiap gambar diganti, jadi aman disimpan lama di browser.
        return Storage::disk('local')->response($path, 'tanda-tangan.png', [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=31536000, immutable',
        ]);
    }
}
