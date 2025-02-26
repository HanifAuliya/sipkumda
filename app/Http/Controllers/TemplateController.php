<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class TemplateController extends Controller
{
    /**
     * Download template matrik.
     *
     * @return BinaryFileResponse
     */
    public function downloadMatrik(): BinaryFileResponse
    {
        // Path ke file template matrik
        $filePath = public_path('templates/template_matrik.doc');

        // Nama file yang akan di-download
        $fileName = 'template_matrik.doc';

        // Lakukan proses download
        return response()->download($filePath, $fileName);
    }
}
