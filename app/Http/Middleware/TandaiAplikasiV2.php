<?php

namespace App\Http\Middleware;

use App\Support\Navigation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Pengguna yang masuk v2 lewat tautan langsung tetap dianggap memilih v2. */
class TandaiAplikasiV2
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->session()->put(Navigation::SESI_APLIKASI, Navigation::V2);

        return $next($request);
    }
}
