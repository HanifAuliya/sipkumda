<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\FasilitasiProdukHukum;

class FasilitasiPdfController extends Controller
{
    public function export(Request $request)
    {
        $query = FasilitasiProdukHukum::with(['rancangan']);

        // **Filter Data**
        if ($request->search) {
            $query->whereHas('rancangan', function ($q) use ($request) {
                $q->where('tentang', 'like', '%' . $request->search . '%')
                    ->orWhere('no_rancangan', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->tahun) {
            $query->whereYear('tanggal_fasilitasi', $request->tahun);
        }

        if ($request->jenisRancangan) {
            $query->whereHas('rancangan', function ($q) use ($request) {
                $q->where('jenis_rancangan', $request->jenisRancangan);
            });
        }

        $fasilitasiList = $query->orderByDesc('tanggal_fasilitasi')->get();

        // Load tampilan PDF dengan data
        $pdf = Pdf::loadView('pdf.fasilitasi', compact('fasilitasiList'));

        // Set ukuran kertas & orientasi
        $pdf->setPaper('A4', 'landscape');

        // Download file PDF
        return $pdf->download('Fasilitasi_Produk_Hukum.pdf');
    }
}
