<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Naskah;
use App\Models\NaskahBerkas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BerkasNaskahController extends Controller
{
    /** ?lihat=1 menampilkan PDF di dalam halaman (pratinjau), selain itu diunduh. */
    public function __invoke(Request $request, Naskah $naskah, NaskahBerkas $berkas)
    {
        Gate::authorize('unduh', $naskah);
        abort_unless($berkas->naskah_id === $naskah->id, 404);

        if ($request->boolean('lihat') && str_ends_with(strtolower($berkas->nama_asli), '.pdf')) {
            return Storage::disk('local')->response($berkas->path, $berkas->nama_asli, [
                'Content-Type' => 'application/pdf',
                'X-Content-Type-Options' => 'nosniff',
                'Cache-Control' => 'private, max-age=600',
            ]);
        }

        return Storage::disk('local')->download($berkas->path, $berkas->nama_asli);
    }
}
