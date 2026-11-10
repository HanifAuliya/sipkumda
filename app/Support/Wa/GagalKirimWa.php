<?php

namespace App\Support\Wa;

use RuntimeException;

/**
 * Pengiriman gagal. $putus: sambungan WhatsApp atau programnya tidak tersedia, sehingga pesan
 * tetap menunggu dan dicoba lagi nanti. Selain itu (misalnya nomor tidak terdaftar) dihitung sebagai percobaan.
 */
class GagalKirimWa extends RuntimeException
{
    public function __construct(string $pesan, public readonly bool $putus = false)
    {
        parent::__construct($pesan);
    }
}
