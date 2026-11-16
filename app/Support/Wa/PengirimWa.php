<?php

namespace App\Support\Wa;

/** Jalur pengiriman WhatsApp. Dipilih lewat config('whatsapp.driver'). */
interface PengirimWa
{
    /**
     * @return array{mode: string, gateway: bool, masuk: bool, nomor: ?string, galat: ?string}
     *   gateway: program pengirim bisa dihubungi; masuk: nomor kantor sudah tersambung.
     */
    public function status(): array;

    /** QR untuk disambungkan dari HP kantor, sebagai data URI, beserta lama berlakunya (detik). */
    public function qr(): ?array;

    /** @throws GagalKirimWa */
    public function kirim(string $nomor, string $isi): void;

    public function keluar(): void;

    public function sambungUlang(): void;
}
