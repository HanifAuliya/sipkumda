<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Livewire\V2\Concerns\KelolaTahapanTtd;
use App\Models\Naskah;
use App\Models\NaskahBerkas;
use App\Models\TandaTangan;
use App\Services\AlurNaskah;
use App\Support\FormatDokumen;
use App\Support\TataLetakNaskah;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Halaman Nota Dinas & Tanda Tangan: tahap akhir naskah sesudah isinya dikunci. Bagian Hukum
 * menerbitkan (dan Admin menerbitkan ulang) nota dinas di sini; Admin dan Tamu mencatat paraf serta
 * tanda tangan basah. Bagi Tamu inilah satu-satunya halaman naskah; isi naskah hanya bisa dibaca.
 */
#[Layout('layouts.app')]
class PantauNaskah extends Component
{
    use KelolaTahapanTtd;
    use WithFileUploads;

    #[Locked]
    public Naskah $naskah;

    public bool $lihatIsi = false;

    // Penerbitan nota dinas
    public string $nomorInputan = '';
    public string $kepada = '';
    public ?int $tandaTanganId = null;

    /** Alasan Admin menerbitkan ulang nota dinas dari pengaturan Format Dokumen terbaru. */
    public string $alasanTerbitUlang = '';

    /** Pembetulan saat terbit ulang, bila penandatangan atau tujuan salah dipilih. */
    public ?int $ttdUlangId = null;
    public string $kepadaUlang = '';

    /** Alasan Verifikator mengembalikan naskah ke Validasi Fasilitasi (membuka kunci isi). */
    public string $alasanBukaKunci = '';

    // Arsip ke JDIH
    public string $nomorArsip = '';
    public string $tanggalPenetapan = '';
    public string $nomorBerita = '';
    public string $tahunBerita = '';
    public $fileArsip;

    /** Bagian nomor Keputusan Bupati selain nomor urut (mis. 140 dan 412.1.6), per urutan potongan polanya. */
    public array $bagianNomorArsip = [];

    public function mount(Naskah $naskah): void
    {
        Gate::authorize('view', $naskah);
        $this->naskah = $naskah;
        $naskah->tandaiNotifikasiDibaca(auth()->user());

        // Tujuan nota dinas adalah Perangkat Daerah yang mengajukan; masih bisa disunting sebelum terbit.
        $pd = $naskah->pemrakarsa?->perangkatDaerah?->nama_perangkat_daerah;
        $this->kepada = $pd ? "Kepala {$pd}" : '';
        $this->isiPembetulanNota();

        // Terisi dari pola di menu Format Dokumen; bisa diganti bila naskah ini memakai kode lain.
        foreach ($this->potonganNomorArsip() as $i => $potongan) {
            if ($potongan['jenis'] === 'teks') {
                $this->bagianNomorArsip[$i] = $potongan['isi'];
            }
        }
    }

    /** Potongan pola nomor Keputusan Bupati; kosong untuk Peraturan Bupati atau bila polanya dikosongkan. */
    private function potonganNomorArsip(): array
    {
        $pola = $this->naskah->jenis === FormatDokumen::KEPBUP ? FormatDokumen::naskah(FormatDokumen::KEPBUP)['nomor_arsip'] : '';

        return filled($pola) ? FormatDokumen::potonganNomorArsip($pola) : [];
    }

    /** Pola nomor naskah ini dari potongan yang diisi di formulir, mis. "140/{nomor}/412.1.6/TAHUN {tahun}". */
    private function polaNomorArsip(): ?string
    {
        $potongan = $this->potonganNomorArsip();

        if (! $potongan) {
            return null;
        }

        $pola = '';

        foreach ($potongan as $i => $bagian) {
            if ($bagian['jenis'] !== 'teks') {
                $pola .= $bagian['isi'];

                continue;
            }

            $isi = trim((string) ($this->bagianNomorArsip[$i] ?? ''));

            if ($isi === '' || mb_strlen($isi) > 30 || ! preg_match('/^[A-Za-z0-9 .\-]+$/', $isi)) {
                throw ValidationException::withMessages(['nomorArsip' => 'Bagian nomor wajib diisi; hanya huruf, angka, titik, dan tanda hubung, paling banyak 30 karakter.']);
            }

            $pola .= $isi;
        }

        return $pola;
    }

    /** Formulir terbit ulang berangkat dari penandatangan dan tujuan yang sekarang tercatat. */
    private function isiPembetulanNota(): void
    {
        $nota = $this->naskah->notaDinas()->first();
        $this->ttdUlangId = $nota?->tanda_tangan_id;
        $this->kepadaUlang = $nota->kepada ?? '';
    }

    public function terbitkanNota(AlurNaskah $alur): void
    {
        $this->resetValidation();
        $this->naskah = $alur->terbitkanNota($this->naskah, auth()->user(), $this->nomorInputan, $this->kepada, $this->tandaTanganId);

        $this->reset('nomorInputan', 'tandaTanganId');
        $this->isiPembetulanNota();
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Nota dinas diterbitkan.']);
    }

    public function terbitUlangNota(AlurNaskah $alur): void
    {
        Gate::authorize('view', $this->naskah);
        $this->resetValidation();
        if (blank($this->kepadaUlang)) {
            throw ValidationException::withMessages(['kepadaUlang' => 'Tujuan nota dinas wajib diisi.']);
        }

        $berkas = $alur->terbitUlangNota($this->naskah, auth()->user(), $this->alasanTerbitUlang, $this->ttdUlangId, $this->kepadaUlang);

        $this->reset('alasanTerbitUlang');
        $this->isiPembetulanNota();
        $this->dispatch('nota-terbit-ulang');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => "Nota dinas diterbitkan ulang ({$berkas->nama_asli})."]);
    }

    /** Isi naskah ternyata masih perlu diubah: kunci dibuka dan naskah kembali ke Validasi Fasilitasi. */
    public function bukaKunci(AlurNaskah $alur)
    {
        $this->resetValidation();
        $kunci = collect($alur->aksiTersedia($this->naskah->fresh(), auth()->user()))->keys()->first(fn ($k) => str_starts_with($k, 'buka_kunci'));
        abort_unless($kunci, 403);

        if (blank($this->alasanBukaKunci)) {
            throw ValidationException::withMessages(['alasanBukaKunci' => 'Tulis alasan naskah dikembalikan.']);
        }

        $alur->jalankan($this->naskah, auth()->user(), $kunci, trim($this->alasanBukaKunci));

        return $this->redirectRoute('naskah.kerja', $this->naskah);
    }

    public function arsipkan(AlurNaskah $alur): void
    {
        $this->resetValidation();
        $this->validate([
            'nomorArsip' => 'required|numeric|digits_between:1,3',
            'tanggalPenetapan' => 'required|date',
            'nomorBerita' => 'nullable|numeric|digits_between:1,3',
            'tahunBerita' => 'nullable|numeric|digits:4',
            'fileArsip' => 'required|file|mimes:pdf|max:20480',
        ], [], [
            'nomorArsip' => 'nomor',
            'tanggalPenetapan' => 'tanggal penetapan',
            'nomorBerita' => 'nomor berita daerah',
            'tahunBerita' => 'tahun berita daerah',
            'fileArsip' => 'file bertanda tangan',
        ]);

        $this->naskah = $alur->arsipkan($this->naskah, auth()->user(), $this->nomorArsip, $this->tanggalPenetapan, $this->nomorBerita ?: null, $this->tahunBerita ?: null, $this->fileArsip, $this->polaNomorArsip());

        $this->reset('nomorArsip', 'tanggalPenetapan', 'nomorBerita', 'tahunBerita', 'fileArsip');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Naskah diarsipkan ke JDIH.']);
    }

    public function render(AlurNaskah $alur)
    {
        $this->naskah->refresh()->load(['pemrakarsa.perangkatDaerah', 'peneliti', 'notaDinas.tandaTangan']);
        $user = auth()->user();
        $pemantau = $user->pemantau();
        $aksi = $alur->aksiTersedia($this->naskah, $user);
        $bolehTerbit = isset($aksi['terbitkan_nota']);

        // Riwayat yang berkaitan dengan nota dinas dan tanda tangan saja.
        $tahapAkhir = collect([TahapNaskah::Penandatanganan, TahapNaskah::Arsip, TahapNaskah::Selesai])->map->label()->all();
        $riwayat = $this->naskah->riwayat()
            ->with('user:id,nama_user')
            ->where(fn ($q) => $q->whereIn('aksi', ['tahapan', 'tahapan_tanggal', 'tahapan_batal', 'terbit_ulang_nota'])
                ->orWhere(fn ($q) => $q->where('aksi', 'tahap')->where(fn ($q) => $q->whereIn('sesudah', $tahapAkhir)->orWhereIn('sebelum', $tahapAkhir))))
            ->limit(50)
            ->get();

        return view('v2.pantau-naskah', [
            'tahap' => $this->naskah->tahap,
            'nota' => $this->naskah->notaDinas,
            'pemantau' => $pemantau,
            'judul' => $pemantau ? 'Pantau Tanda Tangan' : 'Nota Dinas & Tanda Tangan',
            'bisaUnduh' => Gate::allows('unduh', $this->naskah),
            'bolehKelola' => $alur->bolehKelolaTtd($this->naskah, $user),
            'bolehTerbit' => $bolehTerbit,
            'bolehArsip' => isset($aksi['arsipkan']),
            'potonganArsip' => isset($aksi['arsipkan']) ? $this->potonganNomorArsip() : [],
            'bolehBukaKunci' => isset($aksi['buka_kunci']) || isset($aksi['buka_kunci_ttd']),
            'bolehTerbitUlang' => $alur->bolehTerbitUlangNota($this->naskah, $user),
            // Versi terbaru di atas; yang aktif adalah yang berlaku, sisanya sudah diganti.
            'versiNota' => $this->naskah->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS)->with('pengunggah:id,nama_user')->get(),
            'potonganNomor' => $bolehTerbit ? FormatDokumen::potonganNomorNota(FormatDokumen::nota()['nomor'], $this->naskah->jenis, now()->year) : [],
            'daftarTtd' => $bolehTerbit || $alur->bolehTerbitUlangNota($this->naskah, $user) ? TandaTangan::where('status', 'Aktif')->get(['id', 'nama_ttd', 'jabatan']) : collect(),
            'riwayat' => $riwayat,
            'blokNaskah' => $this->lihatIsi ? TataLetakNaskah::susun($this->naskah, $this->naskah->baris()->get()) : [],
        ])->title(($pemantau ? 'Pantau ' : 'Nota Dinas ') . $this->naskah->nomor_tampil);
    }
}
