<?php

namespace App\Livewire\V2;

use App\Models\DokumentasiProdukHukum;
use App\Models\Naskah;
use App\Models\NaskahBerkas;
use App\Services\AlurNaskah;
use App\Services\PembacaNaskahWord;
use App\Support\TemplateNaskah;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

/** Tombol dan modal "Ajukan Rancangan Baru", sama seperti aplikasi lama. */
class BuatNaskah extends Component
{
    use WithFileUploads;

    public string $jenis = '';

    #[Validate('required|string|max:' . DokumentasiProdukHukum::TENTANG_MAKS)]
    public string $tentang = '';

    #[Validate('required|string|max:50|unique:naskah,nomor_nota')]
    public string $nomorNota = '';

    #[Validate('required|date')]
    public string $tanggalNota = '';

    #[Validate('required|file|mimes:pdf|max:20480')]
    public $notaDinas;

    /** Opsional: tanpa Word, PD mengetik baris langsung di layar kerja. */
    #[Validate('nullable|file|mimes:docx|max:20480')]
    public $word;

    /** Bentuk file Word: naskah seperti yang dicetak, atau matriks berbentuk tabel. Dipilih pengguna. */
    #[Validate('required|in:' . PembacaNaskahWord::BENTUK_NASKAH . ',' . PembacaNaskahWord::BENTUK_MATRIKS)]
    public string $bentuk = PembacaNaskahWord::BENTUK_NASKAH;

    public function mount(): void
    {
        Gate::authorize('create', Naskah::class);
    }

    public function removeFile(string $model): void
    {
        if (in_array($model, ['notaDinas', 'word'], true)) {
            $this->reset($model);
            $this->resetValidation($model);
        }
    }

    public function resetForm(): void
    {
        $this->reset();
        $this->resetValidation();
    }

    public function simpan(AlurNaskah $alur, PembacaNaskahWord $pembaca)
    {
        Gate::authorize('create', Naskah::class);

        $this->tentang = Str::squish($this->tentang);

        $this->validate();
        $this->validate(['jenis' => ['required', Rule::in(Naskah::JENIS)]]);

        $baris = TemplateNaskah::kerangka($this->jenis, $this->tentang);

        // Dibaca sebelum menyimpan apa pun: file rusak cukup memberi pesan, tanpa naskah setengah jadi.
        if ($this->word) {
            try {
                $baris = TemplateNaskah::denganPenutup($pembaca->baca($this->word->getRealPath(), $this->jenis, $this->bentuk)['baris'], $this->jenis);
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages(['word' => $e->getMessage()]);
            }
        }

        // Draf belum bernomor; nomornya diberikan saat diajukan (lihat Naskah::beriNomor).
        $naskah = DB::transaction(function () use ($alur, $baris) {
            $naskah = Naskah::create([
                'jenis' => $this->jenis,
                'tentang' => $this->tentang,
                'pemrakarsa_id' => auth()->id(),
                'nomor_nota' => $this->nomorNota,
                'tanggal_nota' => $this->tanggalNota,
            ]);

            $alur->isiBaris($naskah, $baris);

            return $naskah;
        });

        $this->simpanBerkas($naskah, NaskahBerkas::NOTA_DINAS_PD, $this->notaDinas);

        if ($this->word) {
            $this->simpanBerkas($naskah, NaskahBerkas::WORD_ASLI, $this->word);
        }

        return $this->redirectRoute('v2.naskah.kerja', $naskah);
    }

    private function simpanBerkas(Naskah $naskah, string $jenis, $file): void
    {
        $naskah->berkas()->create([
            'jenis' => $jenis,
            'path' => $file->store("naskah/{$naskah->slug}", 'local'),
            'nama_asli' => $file->getClientOriginalName(),
            'diunggah_oleh' => auth()->id(),
        ]);
    }

    public function render()
    {
        return view('v2.buat-naskah');
    }
}
