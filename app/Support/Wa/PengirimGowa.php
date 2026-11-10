<?php

namespace App\Support\Wa;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * GOWA (go-whatsapp-web-multidevice, v9) yang berjalan di server sendiri, hanya di 127.0.0.1.
 * Nomor kantor tersambung seperti WhatsApp Web; sesinya disimpan GOWA di foldernya sendiri.
 * Sejak v8 setiap nomor adalah "perangkat" di GOWA: perangkat $device dibuat otomatis bila belum ada.
 */
class PengirimGowa implements PengirimWa
{
    /** Kode GOWA bila perangkat belum dibuat (GOWA baru dipasang atau foldernya dihapus). */
    private const TANPA_PERANGKAT = ['DEVICE_NOT_FOUND', 'DEVICE_ID_REQUIRED'];

    public function __construct(private string $url, private ?string $user, private ?string $password, private string $device = 'sifkumda')
    {
    }

    private function http(): PendingRequest
    {
        $http = Http::baseUrl($this->url)->acceptJson()->timeout(15)->connectTimeout(3)
            ->withHeaders(['X-Device-Id' => $this->device]);

        return filled($this->user) ? $http->withBasicAuth($this->user, (string) $this->password) : $http;
    }

    private function tanpaPerangkat(Response $hasil): bool
    {
        return in_array($hasil->json('code'), self::TANPA_PERANGKAT, true);
    }

    private function buatPerangkat(): void
    {
        $this->http()->post('/devices', ['device_id' => $this->device]);
    }

    public function status(): array
    {
        try {
            $hasil = $this->http()->get('/app/status');

            if ($this->tanpaPerangkat($hasil)) {
                $this->buatPerangkat();
                $hasil = $this->http()->get('/app/status');
            }
        } catch (ConnectionException) {
            return ['mode' => 'gowa', 'gateway' => false, 'masuk' => false, 'nomor' => null, 'galat' => 'Program WhatsApp (GOWA) tidak berjalan atau tidak bisa dihubungi.'];
        }

        if (! $hasil->successful()) {
            return ['mode' => 'gowa', 'gateway' => false, 'masuk' => false, 'nomor' => null, 'galat' => $this->pesanGalat($hasil)];
        }

        $data = $hasil->json('results') ?? [];
        $jid = (string) ($data['jid'] ?? '');

        return [
            'mode' => 'gowa',
            'gateway' => true,
            'masuk' => (bool) ($data['is_logged_in'] ?? false) && (bool) ($data['is_connected'] ?? false),
            // JID berbentuk 6281234567890:12@s.whatsapp.net
            'nomor' => $jid !== '' ? strtok(strtok($jid, '@'), ':') : null,
            'galat' => null,
        ];
    }

    /**
     * Setiap permintaan QR ke GOWA membuat QR baru. Halaman Admin diperbarui tiap 5 detik, jadi QR disimpan
     * selama masa berlakunya; kalau tidak, QR berganti saat sedang di-scan.
     */
    public function qr(): ?array
    {
        if ($simpanan = Cache::get($this->kunciQr())) {
            return $simpanan;
        }

        try {
            $hasil = $this->http()->get('/app/login');

            if ($this->tanpaPerangkat($hasil)) {
                $this->buatPerangkat();
                $hasil = $this->http()->get('/app/login');
            }

            $tautan = $hasil->json('results.qr_link');

            if (! $hasil->successful() || blank($tautan)) {
                return null;
            }

            // Tautan QR memakai alamat GOWA sendiri; diambil dari sini lalu disisipkan sebagai data URI,
            // sehingga browser Admin tidak perlu (dan tidak bisa) menghubungi GOWA secara langsung.
            $gambar = $this->http()->accept('image/*')->get((string) parse_url($tautan, PHP_URL_PATH));

            if (! $gambar->successful()) {
                return null;
            }

            $qr = [
                'gambar' => 'data:image/png;base64,' . base64_encode($gambar->body()),
                'durasi' => (int) ($hasil->json('results.qr_duration') ?: 30),
            ];

            // Diganti sedikit lebih awal dari batasnya, supaya tidak ada QR kedaluwarsa yang terlanjur di-scan.
            Cache::put($this->kunciQr(), $qr, now()->addSeconds(max(5, $qr['durasi'] - 5)));

            return $qr;
        } catch (ConnectionException) {
            return null;
        }
    }

    public function kirim(string $nomor, string $isi): void
    {
        try {
            $hasil = $this->http()->post('/send/message', ['phone' => "{$nomor}@s.whatsapp.net", 'message' => $isi]);
        } catch (ConnectionException) {
            throw new GagalKirimWa('Program WhatsApp (GOWA) tidak bisa dihubungi.', putus: true);
        }

        if ($hasil->successful()) {
            return;
        }

        $pesan = $this->pesanGalat($hasil);
        $putus = $this->tanpaPerangkat($hasil) || ($hasil->status() >= 500 && preg_match('/log+in|connect|session/i', $pesan));

        throw new GagalKirimWa($pesan, putus: (bool) $putus);
    }

    public function keluar(): void
    {
        Cache::forget($this->kunciQr());
        $this->http()->get('/app/logout');
    }

    public function sambungUlang(): void
    {
        $this->http()->get('/app/reconnect');
    }

    private function kunciQr(): string
    {
        return "wa:qr:{$this->device}";
    }

    private function pesanGalat(Response $hasil): string
    {
        return mb_substr((string) ($hasil->json('message') ?: "HTTP {$hasil->status()}"), 0, 300);
    }
}
