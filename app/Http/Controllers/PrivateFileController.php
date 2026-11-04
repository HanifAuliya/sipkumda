<?php

namespace App\Http\Controllers;

use App\Models\FasilitasiProdukHukum;
use App\Models\RancanganProdukHukum;
use App\Models\Revisi;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menampilkan file di storage/app/private lewat /view-private/{folder}/{subfolder}/{filename}.
 *
 * Setiap folder dipetakan ke data pemiliknya; file hanya dikirim bila pengguna
 * boleh melihat data tersebut (policy), sehingga file milik instansi lain tidak
 * bisa dibuka hanya dengan menebak namanya.
 */
class PrivateFileController extends Controller
{
    public function __invoke(string $folder, string $subfolder, string $filename): StreamedResponse
    {
        $path = "{$folder}/{$subfolder}/{$filename}";

        abort_unless($this->bolehMelihat(auth()->user(), $folder, $subfolder, $path, $filename), 403);
        abort_unless(Storage::disk('local')->exists($path), 404, 'File tidak ditemukan.');

        // Dikirim bertahap (stream), tidak dimuat utuh ke memori.
        return Storage::disk('local')->response($path, $filename, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }

    private function bolehMelihat(User $user, string $folder, string $subfolder, string $path, string $filename): bool
    {
        return match ("{$folder}/{$subfolder}") {
            // Produk hukum yang sudah ditetapkan juga tersedia untuk umum (JDIH).
            'dokumentasi/file_produk_hukum' => true,

            'rancangan/rancangan', 'rancangan/matrik', 'rancangan/nota_dinas', 'rancangan/bahan_pendukung' => $this->bolehSalahSatu(
                RancanganProdukHukum::query(),
                ['rancangan', 'matrik', 'nota_dinas_pd', 'bahan_pendukung'],
                $path, $filename, fn ($rancangan) => Gate::forUser($user)->allows('view', $rancangan),
            ),

            'revisi/rancangan', 'revisi/matrik' => $this->bolehSalahSatu(
                Revisi::with('rancangan'),
                ['revisi_rancangan', 'revisi_matrik'],
                $path, $filename, fn ($revisi) => $revisi->rancangan && Gate::forUser($user)->allows('view', $revisi->rancangan),
            ),

            'fasilitasi/rancangan' => $this->bolehSalahSatu(
                FasilitasiProdukHukum::with('rancangan'),
                ['file_rancangan'],
                $path, $filename, fn ($fasilitasi) => Gate::forUser($user)->allows('view', $fasilitasi),
            ),

            // Gambar tanda tangan hanya untuk pengelola nota dinas.
            'tanda_tangan/ttd' => $user->hasRole(['Super Admin', 'Admin', 'Verifikator']),

            default => false,
        };
    }

    /**
     * Cari data yang menyimpan file ini di salah satu kolom, lalu izinkan bila
     * minimal satu data boleh dilihat. Path lama bisa tersimpan tanpa folder,
     * jadi nama file saja juga dicocokkan.
     */
    private function bolehSalahSatu(Builder $query, array $kolom, string $path, string $filename, callable $boleh): bool
    {
        return $query
            ->where(function ($q) use ($kolom, $path, $filename) {
                foreach ($kolom as $k) {
                    $q->orWhere($k, $path)
                        ->orWhere($k, $filename)
                        ->orWhere($k, 'like', '%/' . addcslashes($filename, '%_\\'));
                }
            })
            ->limit(50)
            ->get()
            ->contains($boleh);
    }
}
