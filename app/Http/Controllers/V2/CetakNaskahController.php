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
                "{$naskah->nomor}-naskah.docx",
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            )->deleteFileAfterSend(),
            'naskah-pdf' => $this->pdf($cetak->pdfNaskah($naskah), "{$naskah->nomor}-naskah.pdf"),
            'matriks-pdf' => $this->pdf($cetak->pdfMatriks($naskah), "{$naskah->nomor}-matriks.pdf"),
            'nota-dinas' => $this->pdf($cetak->pdfNotaDinas($naskah), "{$naskah->nomor}-nota-dinas.pdf"),
        };
    }

    public function template(string $jenis, CetakNaskah $cetak)
    {
        $nama = $jenis === 'surat-keputusan' ? 'Surat Keputusan' : 'Peraturan Bupati';

        return response()->download(
            $cetak->wordTemplate($nama),
            'Template ' . $nama . '.docx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        )->deleteFileAfterSend();
    }

    private function pdf(string $isi, string $nama)
    {
        return response($isi, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$nama}\"",
        ]);
    }
}
