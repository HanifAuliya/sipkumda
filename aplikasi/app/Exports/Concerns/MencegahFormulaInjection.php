<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Pakai bersama WithCustomValueBinder.
 *
 * Teks dari pengguna yang diawali = + - @ akan dijalankan Excel sebagai rumus
 * (formula/CSV injection). Nilai seperti itu disimpan sebagai teks biasa, kecuali
 * rumus HYPERLINK ke aplikasi ini yang memang dibuat oleh ekspor.
 */
trait MencegahFormulaInjection
{
    public function bindValue(Cell $cell, mixed $value): bool
    {
        if (is_string($value) && $value !== '' && str_contains("=+-@\t\r", $value[0])
            && ! str_starts_with($value, '=HYPERLINK("' . url('/'))) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return (new DefaultValueBinder())->bindValue($cell, $value);
    }
}
