<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\DokumentasiProdukHukum;

class DokumentasiPdfController extends Controller
{
    public function export(Request $request)
    {
        $dokumentasiList = DokumentasiProdukHukum::with(['rancangan', 'perangkatDaerah'])
            ->when($request->search, function ($query) use ($request) {
                $query->whereHas('rancangan', function ($q) use ($request) {
                    $q->where('tentang', 'like', '%' . $request->search . '%')
                        ->orWhere('no_rancangan', 'like', '%' . $request->search . '%');
                });
            })
            ->when($request->jenisRancangan, function ($query) use ($request) {
                $query->whereHas('rancangan', function ($q) use ($request) {
                    $q->where('jenis_rancangan', $request->jenisRancangan);
                });
            })
            ->when($request->jenisRancangan, function ($query) use ($request) {
                $query->whereHas('rancangan', function ($q) use ($request) {
                    $q->where('jenis_rancangan', $request->jenisRancangan);
                });
            })
            ->when($request->tahun, function ($query) use ($request) { // Filter tahun
                $query->whereHas('rancangan', function ($q) use ($request) {
                    $q->whereYear('tanggal_pengajuan', $request->tahun);
                });
            })
            ->get();

        // Load tampilan PDF dengan data
        $pdf = Pdf::loadView('pdf.dokumentasi', compact('dokumentasiList'));

        // Set ukuran kertas & orientasi
        $pdf->setPaper('A4', 'landscape');

        // Download file PDF
        return $pdf->download('Dokumentasi_Produk_Hukum.pdf');
    }
}
