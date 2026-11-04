<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Naskah;
use App\Models\NaskahBerkas;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class BerkasNaskahController extends Controller
{
    public function __invoke(Naskah $naskah, NaskahBerkas $berkas)
    {
        Gate::authorize('unduh', $naskah);
        abort_unless($berkas->naskah_id === $naskah->id, 404);

        return Storage::disk('local')->download($berkas->path, $berkas->nama_asli);
    }
}
