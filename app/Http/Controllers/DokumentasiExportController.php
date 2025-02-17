<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\DokumentasiExport;
use Maatwebsite\Excel\Facades\Excel;

class DokumentasiExportController extends Controller
{
    public function export(Request $request)
    {
        return Excel::download(new DokumentasiExport($request->search, $request->jenisRancangan), 'Dokumentasi.xlsx');
    }
}
