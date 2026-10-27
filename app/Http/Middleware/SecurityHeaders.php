<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan HTTP untuk semua respons web.
 *
 * CSP hanya dipasang di luar lingkungan lokal karena dev server Vite (port 5173)
 * memuat script dari origin lain. 'unsafe-eval' dibutuhkan Alpine (bawaan Livewire)
 * untuk mengevaluasi ekspresi di atribut x-*.
 */
class SecurityHeaders
{
    private const CSP = [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
        "style-src 'self' 'unsafe-inline'",
        "img-src 'self' data: blob: https://ik.imagekit.io",
        "font-src 'self' data:",
        "connect-src 'self'",
        "frame-src 'self' https://www.google.com",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "base-uri 'self'",
        "form-action 'self'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $headers->remove('X-Powered-By');
        header_remove('X-Powered-By'); // dipasang langsung oleh PHP (expose_php)

        if (! app()->isLocal()) {
            $headers->set('Content-Security-Policy', implode('; ', self::CSP));
        }

        if ($request->isSecure()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
