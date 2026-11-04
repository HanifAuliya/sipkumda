<?php

namespace App\Livewire\V2;

use App\Models\Naskah;
use App\Models\NaskahBerkas;
use App\Services\AlurNaskah;
use App\Services\PembacaNaskahWord;
use App\Support\TemplateNaskah;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

#[Layout('layouts.v2')]
#[Title('Buat naskah')]
class BuatNaskah extends Component
{
    use WithFileUploads;

    public string $jenis = 'Peraturan Bupati';

    #[Validate('required|string|max:1000')]
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

    public function simpan(AlurNaskah $alur, PembacaNaskahWord $pembaca)
    {
        $this->validate();
        $this->validate(['jenis' => ['required', Rule::in(Naskah::JENIS)]]);

        $baris = TemplateNaskah::kerangka($this->jenis, $this->tentang);

        // Dibaca sebelum menyimpan apa pun: file rusak cukup memberi pesan, tanpa naskah setengah jadi.
        if ($this->word) {
            try {
                $baris = $pembaca->baca($this->word->getRealPath(), $this->jenis)['baris'];
            } catch (RuntimeException $e) {
                throw ValidationException::withMessages(['word' => $e->getMessage()]);
            }
        }

        $naskah = Naskah::denganNomorTerkunci(function () use ($alur, $baris) {
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

        return $this->redirectRoute('v2.naskah.kerja', $naskah, navigate: true);
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
