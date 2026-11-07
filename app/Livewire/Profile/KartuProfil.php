<?php

namespace App\Livewire\Profile;

use App\Support\FotoProfil;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/** Kartu atas halaman profil: identitas pengguna dan foto. Foto baru tersimpan sesudah pratinjau disetujui. */
class KartuProfil extends Component
{
    use WithFileUploads;

    public $foto;

    protected $validationAttributes = ['foto' => 'foto'];

    private function aturanFoto(): array
    {
        // bail: ukuran piksel hanya bisa dibaca dari file yang sudah pasti gambar.
        return ['foto' => [
            'bail', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
            function (string $kolom, $file, Closure $gagal) {
                if ($pesan = FotoProfil::masalah($file->getRealPath())) {
                    $gagal($pesan);
                }
            },
        ]];
    }

    public function updatedFoto(): void
    {
        try {
            $this->validate($this->aturanFoto());
        } catch (ValidationException $e) {
            // File yang ditolak tidak bisa dipratinjau, jadi jangan dibiarkan terpilih.
            $this->reset('foto');

            throw $e;
        }
    }

    public function batalFoto(): void
    {
        $this->reset('foto');
        $this->resetValidation();
    }

    public function simpanFoto(): void
    {
        $this->validate($this->aturanFoto());

        try {
            FotoProfil::simpan(Auth::user(), $this->foto->getRealPath());
        } catch (Throwable $e) {
            // File rusak bisa lolos pemeriksaan awal karena bagian kepalanya masih utuh.
            report($e);
            $this->reset('foto');

            throw ValidationException::withMessages(['foto' => 'Foto tidak bisa dibaca. Coba pilih foto lain.']);
        }

        $this->reset('foto');
        $this->kabarkan('Foto profil tersimpan.');
    }

    public function hapusFoto(): void
    {
        FotoProfil::hapus(Auth::user());

        $this->kabarkan('Foto profil dihapus.');
    }

    private function kabarkan(string $pesan): void
    {
        // Foto di menu pengguna berada di luar komponen ini.
        $this->dispatch('foto-berubah', url: Auth::user()->fotoUrl());
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => $pesan]);
    }

    #[On('profil-diperbarui')]
    public function segarkan(): void
    {
    }

    public function render()
    {
        return view('livewire.profile.kartu-profil', ['user' => Auth::user()]);
    }
}
