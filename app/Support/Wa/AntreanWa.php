<?php

namespace App\Support\Wa;

use App\Models\Pengaturan;
use App\Models\PesanWa;
use App\Models\User;
use App\Support\PesanNotifikasi;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Antrean notifikasi WhatsApp. Pesan dibuat bersamaan dengan notifikasi lonceng, lalu dikirim satu per satu
 * oleh wa:kirim. Saat sambungan putus, pesan tetap menunggu; pesan yang sudah dibuka di lonceng, tugasnya
 * sudah lewat, atau terlalu lama tertunda tidak dikirim, supaya penerima tidak dibanjiri kabar basi.
 */
class AntreanWa
{
    /** tindakan: hanya giliran dan pengembalian (bawaan); semua: termasuk kabar posisi naskah. */
    public const CAKUPAN_TINDAKAN = 'tindakan';
    public const CAKUPAN_SEMUA = 'semua';

    public const KUNCI_PUTUS = 'wa:putus';

    public function __construct(private PengirimWa $pengirim)
    {
    }

    public static function cakupan(): string
    {
        return Pengaturan::ambil(Pengaturan::WA_CAKUPAN, self::CAKUPAN_TINDAKAN);
    }

    public static function perluDikirim(User $user, array $data): bool
    {
        if (! $user->wa_aktif || NomorWa::normal($user->no_wa) === null) {
            return false;
        }

        return self::cakupan() === self::CAKUPAN_SEMUA
            || in_array($data['kategori'] ?? null, [PesanNotifikasi::TUGAS, PesanNotifikasi::KEMBALI], true);
    }

    public static function susunIsi(array $data, User $user): string
    {
        $judul = match ($data['kategori'] ?? null) {
            PesanNotifikasi::TUGAS => 'Perlu tindakan Anda',
            PesanNotifikasi::KEMBALI => 'Naskah dikembalikan',
            PesanNotifikasi::SELESAI => 'Naskah selesai',
            default => 'Kabar naskah',
        };

        $slug = $data['slug'] ?? null;
        $tautan = match (true) {
            blank($slug) => route('v2.beranda'),
            $user->pemantau() => route('v2.naskah.pantau', $slug),
            default => route('v2.naskah.kerja', $slug),
        };

        return collect([
            "*SIFKUMDA* · {$judul}",
            '',
            '*' . ($data['title'] ?? 'Notifikasi') . '*',
            $data['message'] ?? null,
            filled($data['pelaku'] ?? null) ? "Oleh: {$data['pelaku']}" : null,
            filled($data['catatan'] ?? null) ? "Catatan: {$data['catatan']}" : null,
            '',
            "Buka: {$tautan}",
            '',
            '_Pesan otomatis, mohon tidak dibalas._',
        ])->reject(fn ($baris) => $baris === null)->implode("\n");
    }

    /** Mengirim pesan yang menunggu. Mengembalikan jumlah yang terkirim. */
    public function proses(int $batas = 20): int
    {
        $kunci = Cache::lock('wa-kirim', 300);

        if (! $kunci->get()) {
            return 0;
        }

        try {
            return $this->kirimSemua($batas);
        } finally {
            $kunci->release();
        }
    }

    private function kirimSemua(int $batas): int
    {
        $antrean = PesanWa::menunggu()->with('user')->oldest('id')->limit($batas)->get();

        if ($antrean->isEmpty()) {
            return 0;
        }

        $status = $this->pengirim->status();

        if (! $status['gateway'] || ! $status['masuk']) {
            $this->tandaiPutus($status['galat'] ?? 'Nomor WhatsApp kantor belum tersambung.');

            return 0;
        }

        $terkirim = 0;

        foreach ($antrean as $pesan) {
            if ($alasan = $this->alasanLewati($pesan)) {
                $pesan->update(['status' => PesanWa::DILEWATI, 'galat' => $alasan]);

                continue;
            }

            if ($terkirim > 0) {
                $this->jeda();
            }

            try {
                $nomor = NomorWa::normal($pesan->user->no_wa);
                $this->pengirim->kirim($nomor, $pesan->isi);
                $pesan->update(['status' => PesanWa::TERKIRIM, 'nomor' => $nomor, 'galat' => null, 'dikirim_at' => now()]);
                $terkirim++;
            } catch (GagalKirimWa $e) {
                if ($e->putus) {
                    $this->tandaiPutus($e->getMessage());

                    break;
                }

                $percobaan = $pesan->percobaan + 1;
                $pesan->update([
                    'percobaan' => $percobaan,
                    'galat' => $e->getMessage(),
                    'status' => $percobaan >= config('whatsapp.maks_percobaan') ? PesanWa::GAGAL : PesanWa::MENUNGGU,
                ]);
            }
        }

        if ($terkirim > 0) {
            Cache::forget(self::KUNCI_PUTUS);
        }

        return $terkirim;
    }

    private function alasanLewati(PesanWa $pesan): ?string
    {
        $user = $pesan->user;

        return match (true) {
            ! $user || ! $user->wa_aktif || NomorWa::normal($user->no_wa) === null => 'Penerima mematikan WhatsApp atau menghapus nomornya.',
            $pesan->notification_id && DatabaseNotification::whereKey($pesan->notification_id)->whereNotNull('read_at')->exists() => 'Sudah dibuka di aplikasi.',
            $pesan->created_at->lt(now()->subHours(config('whatsapp.kedaluwarsa_jam'))) => 'Terlalu lama tertunda.',
            default => null,
        };
    }

    private function tandaiPutus(string $alasan): void
    {
        // Dicatat sekali per jam saja, supaya log tidak penuh saat sambungan putus lama.
        if (Cache::add(self::KUNCI_PUTUS, ['alasan' => $alasan, 'sejak' => now()->toIso8601String()], now()->addHour())) {
            Log::warning("WhatsApp: pesan tertunda. {$alasan}");
        }
    }

    private function jeda(): void
    {
        $min = max(0, (int) config('whatsapp.jeda_min'));
        $maks = max($min, (int) config('whatsapp.jeda_max'));

        if ($maks > 0) {
            usleep(random_int($min * 1000, $maks * 1000) * 1000);
        }
    }
}
