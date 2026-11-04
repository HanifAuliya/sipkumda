<?php

namespace App\Http\Controllers;

use App\Support\Navigation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Setelah login pengguna memilih aplikasi lama atau v2, selama masa uji coba v2. */
class PilihAplikasiController extends Controller
{
    public function create(): View
    {
        return view('pilih-aplikasi');
    }

    public function store(Request $request): RedirectResponse
    {
        $pilihan = $request->validate([
            'aplikasi' => ['required', Rule::in([Navigation::LAMA, Navigation::V2])],
        ])['aplikasi'];

        $request->session()->put(Navigation::SESI_APLIKASI, $pilihan);

        return redirect()->route($pilihan === Navigation::V2 ? 'v2.beranda' : 'dashboard');
    }
}
