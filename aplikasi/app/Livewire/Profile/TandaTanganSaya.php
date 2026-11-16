<?php

namespace App\Livewire\Profile;

use App\Support\TandaTanganLangsung;
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

    /** Menggambar langsung di layar sebagai ganti mengunggah gambar. */
    public bool $menggambar = false;
    public string $ttdLangsung = '';

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

    public function mulaiGambar(): void
    {
        $this->reset('gambar', 'ttdLangsung', 'sandiSekarang');
        $this->resetValidation();
        $this->menggambar = true;
    }

    public function batal(): void
    {
        $this->reset('gambar', 'sandiSekarang', 'menggambar', 'ttdLangsung');
        $this->resetValidation();
    }

    public function simpan(): void
    {
        abort_unless(Auth::user()->hasRole('Peneliti'), 403);

        if ($this->menggambar) {
            $this->validate(['sandiSekarang' => ['required', 'current_password']]);
            $sumber = TandaTanganLangsung::fileSementara($this->ttdLangsung, 'gambar');
        } else {
            $this->validate($this->aturan() + ['sandiSekarang' => ['required', 'current_password']]);
            $sumber = $this->gambar->getRealPath();
        }

        try {
            TandaTanganPeneliti::simpan(Auth::user(), $sumber);
        } catch (Throwable $e) {
            report($e);
            $this->reset('gambar');

            throw ValidationException::withMessages(['gambar' => 'Gambar tidak bisa dibaca. Coba pilih gambar lain.']);
        } finally {
            if ($this->menggambar) {
                @unlink($sumber);
            }
        }

        $this->reset('gambar', 'sandiSekarang', 'menggambar', 'ttdLangsung');
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
