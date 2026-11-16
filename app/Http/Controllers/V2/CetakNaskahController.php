<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Naskah;
use App\Services\CetakNaskah;
use Illuminate\Support\Facades\Gate;

class CetakNaskahController extends Controller
{
    public function __invoke(Naskah $naskah, string $berkas, CetakNaskah $cetak)
    {
        Gate::authorize('unduh', $naskah);

        return match ($berkas) {
            'naskah-word' => response()->download(
                $cetak->wordNaskah($naskah),
                "{$naskah->nomor_tampil}-naskah.docx",
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            )->deleteFileAfterSend(),
            'naskah-pdf' => $this->pdf($cetak->pdfNaskah($naskah), "{$naskah->nomor_tampil}-naskah.pdf", request()->boolean('lihat')),
            'matriks-pdf' => $this->pdf($cetak->pdfMatriks($naskah), "{$naskah->nomor_tampil}-matriks.pdf"),
            'nota-dinas' => $this->pdf($cetak->pdfNotaDinas($naskah), "{$naskah->nomor_tampil}-nota-dinas.pdf"),
        };
    }

    public function template(string $jenis, CetakNaskah $cetak)
    {
        $nama = $jenis === 'keputusan-bupati' ? 'Keputusan Bupati' : 'Peraturan Bupati';

        return response()->download(
            $cetak->wordTemplate($nama),
            'Template ' . $nama . '.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend();
    }

    /** ?lihat=1 menampilkan PDF di browser (pratinjau di layar kerja) alih-alih mengunduhnya. */
    private function pdf(string $isi, string $nama, bool $lihat = false)
    {
        return response($isi, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => ($lihat ? 'inline' : 'attachment') . "; filename=\"{$nama}\"",
        ]);
    }
}
