<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VerifyApiToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Ambil token dari header Authorization
        $token = $request->bearerToken();

        // Ambil token statis dari konfigurasi aplikasi backend
        $validToken = 'siBBUVRknlE7d42MA2qr5BxRq9GDrBmCEbARQTUAHDdjjOi9BYx64J8uYR3F'; // Pastikan ini sesuai dengan key di config/app.php atau file config lainnya

        // Logika validasi token
        if (!$token || $token !== $validToken) {
            Log::warning('Akses API ditolak: Token tidak valid atau tidak ada.');
            return response()->json(['message' => 'Unauthorized: Invalid or missing API token.'], 401);
        }

        // Jika token valid, lanjutkan request
        return $next($request);
    }
}
