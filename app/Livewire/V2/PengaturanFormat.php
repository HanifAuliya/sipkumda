<?php

namespace App\Livewire\V2;

use App\Services\CetakNaskah;
use App\Support\FormatDokumen;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Menu Format Dokumen: kertas, margin, huruf, kop, dan paraf naskah, serta isi dan format nota dinas.
 * Bila pedoman berubah, Admin atau Verifikator cukup mengubahnya di sini (lihat App\Support\FormatDokumen).
 */
#[Layout('layouts.app')]
#[Title('Format Dokumen')]
class PengaturanFormat extends Component
{
    use WithFileUploads;

    /** kepbup | perbup | nota */
    #[Url]
    public string $tab = 'kepbup';

    public array $kepbup = [];

    public array $perbup = [];

    public array $nota = [];

    /** Gambar baru per bagian (lambang kop naskah / logo kop nota), belum tersimpan. */
    public $gambar = [];

    /** Naik setiap pratinjau berhasil dibuat ulang; dipakai sebagai alamat PDF supaya browser memuat yang baru. */
    public int $versiPratinjau = 0;

    /** Isian belum sah, jadi pratinjau yang tampil masih yang sebelumnya. */
    public bool $pratinjauGagal = false;

    /** Naskah sungguhan yang dipakai pratinjau, mis. "PB-2026-012 · Dana Desa (Selesai)"; kosong bila data contoh. */
    public string $sumberPratinjau = '';

    /** Folder PDF pratinjau; satu berkas per pengguna per bagian, ditimpa setiap pembaruan. */
    public const FOLDER_PRATINJAU = 'pratinjau-format';

    private const JENIS = ['kepbup' => FormatDokumen::KEPBUP, 'perbup' => FormatDokumen::PERBUP];

    /** Daftar yang barisnya bisa ditambah dan dihapus dari formulir. */
    private const DAFTAR = ['kepbup.paraf.jabatan', 'perbup.paraf.jabatan', 'nota.butir', 'nota.ketentuan', 'nota.kop.baris'];

    public function mount(): void
    {
        foreach (['kepbup', 'perbup', 'nota'] as $bagian) {
            $this->muat($bagian);
        }
    }

    private function muat(string $bagian): void
    {
        $this->{$bagian} = $bagian === 'nota' ? FormatDokumen::nota() : FormatDokumen::naskah(self::JENIS[$bagian]);
        unset($this->gambar[$bagian]);
    }

    private function bagian(string $bagian): string
    {
        abort_unless(in_array($bagian, ['kepbup', 'perbup', 'nota'], true), 404);

        return $bagian;
    }

    private function bawaan(string $bagian): array
    {
        return $bagian === 'nota' ? FormatDokumen::bawaanNota() : FormatDokumen::bawaanNaskah(self::JENIS[$bagian]);
    }

    /** Isian bagian ini yang sudah divalidasi dan dirapikan tipenya. */
    private function isianSah(string $bagian): array
    {
        $aturan = collect($bagian === 'nota' ? FormatDokumen::aturanNota() : FormatDokumen::aturanNaskah())
            ->mapWithKeys(fn ($aturan, $kunci) => ["{$bagian}.{$kunci}" => $aturan])
            ->put("gambar.{$bagian}", 'nullable|image|mimes:png,jpg,jpeg|max:2048|dimensions:max_width=4000,max_height=4000')
            ->all();

        $this->validate($aturan, [
            "{$bagian}.nomor.regex" => 'Pola nomor harus memuat {nomor}.',
            "{$bagian}.nomor_arsip.regex" => 'Pola nomor harus memuat {nomor}.',
        ], $this->namaIsian($bagian));

        $format = FormatDokumen::rapikan($this->{$bagian}, $this->bawaan($bagian));

        // Path gambar tidak pernah diambil dari browser: selalu dari yang tersimpan di server.
        $kunciGambar = $bagian === 'nota' ? 'logo' : 'lambang';
        $format['kop'][$kunciGambar] = $this->tersimpan($bagian)['kop'][$kunciGambar];

        return $format;
    }

    private function tersimpan(string $bagian): array
    {
        return $bagian === 'nota' ? FormatDokumen::nota() : FormatDokumen::naskah(self::JENIS[$bagian]);
    }

    public function simpan(string $bagian): void
    {
        $bagian = $this->bagian($bagian);
        $format = $this->isianSah($bagian);
        $kunciGambar = $bagian === 'nota' ? 'logo' : 'lambang';

        if ($baru = $this->gambar[$bagian] ?? null) {
            $path = FormatDokumen::FOLDER_GAMBAR . "/{$bagian}-" . Str::random(20) . '.' . $baru->extension();
            Storage::disk('local')->put($path, file_get_contents($baru->getRealPath()));

            if ($lama = $format['kop'][$kunciGambar]) {
                Storage::disk('local')->delete($lama);
            }
            $format['kop'][$kunciGambar] = $path;
        }

        $bagian === 'nota' ? FormatDokumen::simpanNota($format) : FormatDokumen::simpanNaskah(self::JENIS[$bagian], $format);

        $this->muat($bagian);
        $this->dispatch('segarkan-pratinjau');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Format ' . $this->judul($bagian) . ' tersimpan.']);
    }

    /** Kembali ke gambar bawaan (Garuda / logo Barabai). */
    public function hapusGambar(string $bagian): void
    {
        $bagian = $this->bagian($bagian);
        $kunciGambar = $bagian === 'nota' ? 'logo' : 'lambang';
        unset($this->gambar[$bagian]);
        $tersimpan = $this->tersimpan($bagian);

        if ($lama = $tersimpan['kop'][$kunciGambar]) {
            $tersimpan['kop'][$kunciGambar] = null;
            $bagian === 'nota' ? FormatDokumen::simpanNota($tersimpan) : FormatDokumen::simpanNaskah(self::JENIS[$bagian], $tersimpan);
            Storage::disk('local')->delete($lama);
            $this->{$bagian}['kop'][$kunciGambar] = null;
        }

        $this->dispatch('segarkan-pratinjau');
    }

    public function kembalikan(string $bagian): void
    {
        $bagian = $this->bagian($bagian);
        FormatDokumen::kembalikan($bagian === 'nota' ? 'nota' : self::JENIS[$bagian]);
        $this->muat($bagian);
        $this->resetValidation();
        $this->dispatch('segarkan-pratinjau');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Format ' . $this->judul($bagian) . ' kembali ke bawaan.']);
    }

    /** Isian formulir yang belum disimpan, termasuk gambar yang baru dipilih, untuk contoh dan pratinjau. */
    private function formatContoh(string $bagian): array
    {
        $format = $this->isianSah($bagian);

        if ($baru = $this->gambar[$bagian] ?? null) {
            $format['kop']['pratinjau'] = $baru->getRealPath();
        }

        return $format;
    }

    public static function pathPratinjau(int $userId, string $bagian): string
    {
        return self::FOLDER_PRATINJAU . "/{$userId}-{$bagian}.pdf";
    }

    /**
     * Membuat ulang PDF pratinjau di samping formulir dari isian sekarang. Dipanggil otomatis sebentar
     * sesudah isian berubah, jadi isian yang belum sah tidak memunculkan galat: pratinjau lama dibiarkan.
     */
    public function pratinjau(string $bagian): void
    {
        $bagian = $this->bagian($bagian);

        try {
            $format = $this->formatContoh($bagian);
        } catch (ValidationException) {
            $this->pratinjauGagal = true;

            return;
        }

        // Memakai naskah sungguhan (yang sudah selesai lebih dulu), supaya perubahan terlihat pada isi yang nyata.
        $cetak = app(CetakNaskah::class);
        [$isi, $naskah] = $bagian === 'nota' ? $cetak->pdfPratinjauNota($format) : $cetak->pdfPratinjauNaskah(self::JENIS[$bagian], $format);

        Storage::disk('local')->put(self::pathPratinjau(auth()->id(), $bagian), $isi);
        $this->sumberPratinjau = $naskah ? "{$naskah->nomor_tampil} · " . Str::limit($naskah->tentang, 60) . " ({$naskah->tahap->label()})" : '';
        $this->pratinjauGagal = false;
        $this->versiPratinjau++;
    }

    /** Contoh dari isian formulir yang belum disimpan, supaya hasilnya bisa dicek dulu. */
    public function contoh(string $bagian, string $jenisFile = 'pdf'): StreamedResponse
    {
        $cetak = app(CetakNaskah::class);
        $bagian = $this->bagian($bagian);
        $format = $this->formatContoh($bagian);

        $nama = 'Contoh ' . $this->judul($bagian);

        if ($bagian === 'nota') {
            $isi = $cetak->pdfContohNota($format);

            return response()->streamDownload(fn () => print($isi), "{$nama}.pdf", ['Content-Type' => 'application/pdf']);
        }

        if ($jenisFile === 'word') {
            $path = $cetak->wordTemplate(self::JENIS[$bagian], $format);

            return response()->streamDownload(function () use ($path) {
                readfile($path);
                @unlink($path);
            }, "{$nama}.docx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']);
        }

        $isi = $cetak->pdfContohNaskah(self::JENIS[$bagian], $format);

        return response()->streamDownload(fn () => print($isi), "{$nama}.pdf", ['Content-Type' => 'application/pdf']);
    }

    public function tambahBaris(string $daftar): void
    {
        abort_unless(in_array($daftar, self::DAFTAR, true), 404);
        $isi = data_get($this, $daftar, []);
        $isi[] = $daftar === 'nota.kop.baris' ? ['teks' => '', 'ukuran' => 11, 'tebal' => false] : '';
        data_set($this, $daftar, $isi);
        $this->dispatch('segarkan-pratinjau');
    }

    public function hapusBaris(string $daftar, int $indeks): void
    {
        abort_unless(in_array($daftar, self::DAFTAR, true), 404);
        $isi = data_get($this, $daftar, []);
        unset($isi[$indeks]);
        data_set($this, $daftar, array_values($isi));
        $this->dispatch('segarkan-pratinjau');
    }

    private function judul(string $bagian): string
    {
        return match ($bagian) {
            'kepbup' => 'Keputusan Bupati',
            'perbup' => 'Peraturan Bupati',
            default => 'Nota Dinas',
        };
    }

    /** Nama isian untuk pesan galat, mis. "kepbup.margin.atas" => "margin atas". */
    private function namaIsian(string $bagian): array
    {
        $nama = [
            'kertas.lebar' => 'lebar kertas', 'kertas.tinggi' => 'tinggi kertas',
            'margin.atas_pertama' => 'margin atas halaman pertama', 'margin.atas' => 'margin atas',
            'margin.bawah' => 'margin bawah', 'margin.kiri' => 'margin kiri', 'margin.kanan' => 'margin kanan',
            'huruf.nama' => 'jenis huruf', 'huruf.ukuran' => 'ukuran huruf', 'huruf.spasi' => 'spasi baris',
            'kop.teks' => 'teks kop', 'kop.ukuran' => 'ukuran teks kop', 'kop.lebar_lambang' => 'lebar lambang', 'kop.lebar_logo' => 'lebar logo',
            'konsiderans.lebar_label' => 'lebar label', 'penutup.tempat' => 'tempat penetapan', 'penutup.kiri' => 'posisi tanda tangan',
            'paraf.judul' => 'judul kotak paraf', 'judul.teks' => 'judul nota', 'judul.ukuran' => 'ukuran judul',
            'nomor' => 'pola nomor', 'nomor_arsip' => 'pola nomor keputusan', 'dari' => 'dari', 'perihal' => 'perihal', 'pembuka' => 'paragraf pembuka',
            'penutup' => 'paragraf penutup', 'ttd.jabatan' => 'jabatan penanda tangan', 'ttd.kiri' => 'posisi tanda tangan',
        ];

        return collect($nama)->mapWithKeys(fn ($v, $k) => ["{$bagian}.{$k}" => $v])->put("gambar.{$bagian}", 'gambar')->all();
    }

    public function render()
    {
        // Dari yang tersimpan di server, bukan dari isian formulir yang bisa diubah dari browser.
        $unggahan = [
            'kepbup' => $this->tersimpan('kepbup')['kop']['lambang'],
            'perbup' => $this->tersimpan('perbup')['kop']['lambang'],
            'nota' => $this->tersimpan('nota')['kop']['logo'],
        ];

        return view('v2.pengaturan-format', [
            'unggahan' => array_map('boolval', $unggahan),
            // Alamatnya ikut berganti setiap gambar diganti, supaya browser tidak menampilkan yang lama.
            'urlGambar' => collect($unggahan)->map(fn ($path, $bagian) => route('v2.format.gambar', ['bagian' => $bagian, 'v' => substr(md5((string) $path), 0, 8)]))->all(),
        ]);
    }
}
