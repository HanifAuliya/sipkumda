<?php

use Illuminate\Support\Carbon;

if (! function_exists('tanggal')) {
    /**
     * Format tanggal Indonesia, mis. "21 September 2026" atau "21 September 2026, 09:43".
     */
    function tanggal(mixed $value, bool $time = false, string $empty = '-'): string
    {
        if (blank($value)) {
            return $empty;
        }

        return Carbon::parse($value)->translatedFormat($time ? 'd F Y, H:i' : 'd F Y');
    }
}

if (! function_exists('private_file_url')) {
    /**
     * URL untuk membuka file privat lewat route /view-private/{folder}/{subfolder}/{filename}.
     */
    function private_file_url(string $folder, ?string $path): ?string
    {
        return blank($path) ? null : url('/view-private/' . trim($folder, '/') . '/' . basename($path));
    }
}
