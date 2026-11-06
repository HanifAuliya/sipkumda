<?php

namespace App\Http\Controllers;

use App\Models\DokumentasiProdukHukum;
use App\Support\Jdih;
use App\Support\StatistikPublik;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman publik: beranda SIFKUMDA serta sub-halaman JDIH (pencarian, detail,
 * dan dokumen produk hukum).
 *
 * Route-nya berjalan tanpa sesi/cookie (lihat routes/web.php), sehingga tidak ada
 * penulisan sesi per kunjungan dan respons bisa di-cache browser maupun proxy.
 */
class JdihController extends Controller
{
    /** Beranda SIFKUMDA; JDIH hanya tampil sebagai cuplikan. */
    public function beranda(): Response
    {
        return $this->publik(view('welcome', [
            'statistik' => StatistikPublik::ringkasan(),
            'terbaru' => Jdih::beranda()['terbaru']->take(3),
            'opsi' => Jdih::opsi(),
        ]));
    }

    public function index(Request $request): Response
    {
        $opsi = Jdih::opsi();

        // Semua parameter divalidasi ke nilai yang dikenal; selain itu diabaikan.
        $q = Str::of((string) $request->query('q', ''))->squish()->limit(100, '')->value();
        $jenis = in_array($request->query('jenis'), $opsi['jenis'], true) ? $request->query('jenis') : null;
        $tahun = in_array((int) $request->query('tahun'), array_map('intval', $opsi['tahun']), true) ? (int) $request->query('tahun') : null;
        $urut = array_key_exists((string) $request->query('urut'), Jdih::URUTAN) ? $request->query('urut') : ($q !== '' ? 'relevan' : 'terbaru');
        $halaman = min(max((int) $request->query('page', 1), 1), 500);

        $hasil = Jdih::cari(compact('q', 'jenis', 'tahun', 'urut'), $halaman);

        return $this->publik(view('jdih.index', compact('hasil', 'opsi', 'q', 'jenis', 'tahun', 'urut')));
    }

    public function show(DokumentasiProdukHukum $dokumentasi): Response
    {
        $dokumentasi->load('perangkatDaerah:id,nama_perangkat_daerah');

        return $this->publik(view('jdih.show', [
            'dokumen' => $dokumentasi,
            'terkait' => Jdih::terkait($dokumentasi),
        ]));
    }

    /** File PDF produk hukum; ?unduh=1 untuk mengunduh, selain itu ditampilkan di browser. */
    public function dokumen(Request $request, DokumentasiProdukHukum $dokumentasi): StreamedResponse
    {
        $path = $dokumentasi->file_produk_hukum;
        abort_unless($path && Storage::disk('local')->exists($path), 404, 'Dokumen tidak ditemukan.');

        $nama = Str::slug(Jdih::labelJenis($dokumentasi->jenis_dokumentasi) . " nomor {$dokumentasi->nomor} tahun {$dokumentasi->tahun}") . '.pdf';

        return Storage::disk('local')->response($path, $nama, [
            'Cache-Control' => 'public, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ], $request->boolean('unduh') ? 'attachment' : 'inline');
    }

    private function publik($view): Response
    {
        return response($view)->header('Cache-Control', 'public, max-age=' . config('jdih.http_cache'));
    }
}
