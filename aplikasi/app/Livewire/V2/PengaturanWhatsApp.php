<?php

namespace App\Livewire\V2;

use App\Models\Pengaturan;
use App\Models\PesanWa;
use App\Support\Wa\AntreanWa;
use App\Support\Wa\GagalKirimWa;
use App\Support\Wa\NomorWa;
use App\Support\Wa\PengirimWa;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Admin menyambungkan nomor WhatsApp kantor (scan QR), mengatur cakupan notifikasi,
 * dan memantau antrean pesan.
 */
#[Layout('layouts.app')]
class PengaturanWhatsApp extends Component
{
    public string $cakupan = AntreanWa::CAKUPAN_TINDAKAN;

    public string $nomorUji = '';

    public function mount(): void
    {
        $this->cakupan = AntreanWa::cakupan();
        $this->nomorUji = (string) auth()->user()->no_wa;
    }

    public function updatedCakupan(): void
    {
        abort_unless(in_array($this->cakupan, [AntreanWa::CAKUPAN_TINDAKAN, AntreanWa::CAKUPAN_SEMUA], true), 422);
        Pengaturan::simpan(Pengaturan::WA_CAKUPAN, $this->cakupan);
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Cakupan notifikasi WhatsApp disimpan.']);
    }

    /** Langsung dikirim, bukan lewat antrean, supaya Admin tahu saat itu juga apakah sambungannya jalan. */
    public function kirimUji(PengirimWa $pengirim): void
    {
        $this->validate(['nomorUji' => ['required', ...array_slice(NomorWa::aturan(), 1)]], [], ['nomorUji' => 'nomor tujuan']);

        try {
            $pengirim->kirim(NomorWa::normal($this->nomorUji), "*SIFKUMDA* · Pesan uji\n\nSambungan WhatsApp untuk notifikasi SIFKUMDA berjalan.\n\n_Pesan otomatis, mohon tidak dibalas._");
        } catch (GagalKirimWa $e) {
            $this->addError('nomorUji', 'Gagal mengirim: ' . $e->getMessage());

            return;
        }

        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Pesan uji terkirim ke ' . NomorWa::tampil(NomorWa::normal($this->nomorUji)) . '.']);
    }

    public function kirimSekarang(AntreanWa $antrean): void
    {
        $terkirim = $antrean->proses();
        $this->dispatch('swal:toast', ['type' => $terkirim ? 'success' : 'info', 'message' => $terkirim ? "{$terkirim} pesan terkirim." : 'Tidak ada pesan yang terkirim. Lihat status sambungan.']);
    }

    public function kirimUlang(int $id): void
    {
        PesanWa::whereKey($id)->whereIn('status', [PesanWa::GAGAL, PesanWa::DILEWATI])
            ->update(['status' => PesanWa::MENUNGGU, 'percobaan' => 0, 'galat' => null, 'created_at' => now()]);
    }

    public function sambungUlang(PengirimWa $pengirim): void
    {
        $this->jalankan(fn () => $pengirim->sambungUlang(), 'Menyambung ulang…');
    }

    public function putuskan(PengirimWa $pengirim): void
    {
        $this->jalankan(fn () => $pengirim->keluar(), 'Nomor diputuskan. Scan QR untuk menyambungkan nomor lain.');
    }

    private function jalankan(callable $aksi, string $pesan): void
    {
        try {
            $aksi();
            $this->dispatch('swal:toast', ['type' => 'success', 'message' => $pesan]);
        } catch (Throwable $e) {
            report($e);
            $this->dispatch('swal:toast', ['type' => 'error', 'message' => 'Program WhatsApp tidak bisa dihubungi.']);
        }
    }

    public function render(PengirimWa $pengirim)
    {
        $status = $pengirim->status();
        $hitung = PesanWa::query()->toBase()->selectRaw('status, count(*) as jumlah')->groupBy('status')->pluck('jumlah', 'status');

        return view('v2.pengaturan-whatsapp', [
            'status' => $status,
            'qr' => $status['gateway'] && ! $status['masuk'] ? $pengirim->qr() : null,
            'putus' => Cache::get(AntreanWa::KUNCI_PUTUS),
            'hitung' => $hitung,
            'terkirimHariIni' => PesanWa::where('status', PesanWa::TERKIRIM)->where('dikirim_at', '>=', today())->count(),
            'tanpaNomor' => \App\Models\User::whereNull('no_wa')->count(),
            'pesan' => PesanWa::with('user:id,nama_user')->latest('id')->limit(30)->get(),
        ])->title('WhatsApp');
    }
}
