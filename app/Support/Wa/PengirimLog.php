<?php

namespace App\Support\Wa;

use Illuminate\Support\Facades\Log;

/** Mode pengembangan: pesan hanya dicatat di log, tidak ada yang benar-benar dikirim. */
class PengirimLog implements PengirimWa
{
    public function status(): array
    {
        return ['mode' => 'log', 'gateway' => true, 'masuk' => true, 'nomor' => null, 'galat' => null];
    }

    public function qr(): ?array
    {
        return null;
    }

    public function kirim(string $nomor, string $isi): void
    {
        Log::info("[WhatsApp mode log] ke {$nomor}:\n{$isi}");
    }

    public function keluar(): void
    {
    }

    public function sambungUlang(): void
    {
    }
}
