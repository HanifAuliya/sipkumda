<?php

namespace App\Livewire\Profile;

use App\Support\TandaTanganPeneliti;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/** Peneliti mengunggah gambar tanda tangannya sekali; gambar ini masuk ke matriks naskah yang ditelitinya. */
class TandaTanganSaya extends Component
{
    use WithFileUploads;

    public $gambar;

    /** Gambar ini tercetak di matriks resmi, jadi menggantinya meminta kata sandi. */
    public string $sandiSekarang = '';

    protected $validationAttributes = ['gambar' => 'gambar tanda tangan', 'sandiSekarang' => 'kata sandi saat ini'];

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole('Peneliti'), 403);
    }

    private function aturan(): array
    {
        return ['gambar' => [
            'bail', 'required', 'image', 'mimes:png,jpg,jpeg', 'max:2048',
            function (string $kolom, $file, Closure $gagal) {
                if ($pesan = TandaTanganPeneliti::masalah($file->getRealPath())) {
                    $gagal($pesan);
                }
            },
        ]];
    }

    public function updatedGambar(): void
    {
        try {
            $this->validate($this->aturan());
        } catch (ValidationException $e) {
            $this->reset('gambar');

            throw $e;
        }
    }

    public function batal(): void
    {
        $this->reset('gambar', 'sandiSekarang');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        abort_unless(Auth::user()->hasRole('Peneliti'), 403);
        $this->validate($this->aturan() + ['sandiSekarang' => ['required', 'current_password']]);

        try {
            TandaTanganPeneliti::simpan(Auth::user(), $this->gambar->getRealPath());
        } catch (Throwable $e) {
            report($e);
            $this->reset('gambar');

            throw ValidationException::withMessages(['gambar' => 'Gambar tidak bisa dibaca. Coba pilih gambar lain.']);
        }

        $this->reset('gambar', 'sandiSekarang');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Tanda tangan tersimpan.']);
    }

    public function hapus(): void
    {
        abort_unless(Auth::user()->hasRole('Peneliti'), 403);
        TandaTanganPeneliti::hapus(Auth::user());

        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Tanda tangan dihapus.']);
    }

    public function render()
    {
        return view('livewire.profile.tanda-tangan-saya', ['user' => Auth::user()]);
    }
}
