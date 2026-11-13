<?php

namespace App\Livewire\Profile;

use App\Models\TandaTangan;
use App\Support\TandaTanganPeneliti;
use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

/**
 * Verifikator adalah Kepala Bagian Hukum: ia mengatur sendiri tanda tangan yang tercetak di nota
 * dinas dan di kolom Kepala Bagian Hukum pada matriks. Admin bisa membantu di Kelola TTD.
 */
class TandaTanganKabag extends Component
{
    use WithFileUploads;

    public bool $ubah = false;

    public string $nama = '';

    public string $nip = '';

    public string $jabatan = '';

    public $gambar;

    /** Tercetak di nota dinas resmi, jadi perubahannya meminta kata sandi. */
    public string $sandiSekarang = '';

    protected $validationAttributes = [
        'nama' => 'nama lengkap', 'nip' => 'NIP', 'jabatan' => 'pangkat/golongan',
        'gambar' => 'gambar tanda tangan', 'sandiSekarang' => 'kata sandi saat ini',
    ];

    public function mount(): void
    {
        abort_unless(Auth::user()->hasRole('Verifikator'), 403);
        $this->isiDariData();
        $this->ubah = ! $this->milikSaya();
    }

    private function milikSaya(): ?TandaTangan
    {
        return TandaTangan::where('user_id', Auth::id())->first();
    }

    private function isiDariData(): void
    {
        $ttd = $this->milikSaya();
        $this->nama = $ttd->nama_ttd ?? Auth::user()->nama_user;
        $this->nip = $ttd->nip_ttd ?? (ctype_digit((string) Auth::user()->NIP) ? Auth::user()->NIP : '');
        $this->jabatan = $ttd->jabatan ?? '';
    }

    private function aturanGambar(bool $wajib): array
    {
        return [
            'bail', $wajib ? 'required' : 'nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048',
            function (string $kolom, $file, Closure $gagal) {
                if ($pesan = TandaTanganPeneliti::masalah($file->getRealPath())) {
                    $gagal($pesan);
                }
            },
        ];
    }

    public function updatedGambar(): void
    {
        try {
            $this->validate(['gambar' => $this->aturanGambar(false)]);
        } catch (ValidationException $e) {
            $this->reset('gambar');

            throw $e;
        }
    }

    public function mulaiUbah(): void
    {
        $this->ubah = true;
    }

    public function batal(): void
    {
        $this->reset('gambar', 'sandiSekarang');
        $this->resetValidation();
        $this->isiDariData();
        $this->ubah = ! $this->milikSaya();
    }

    public function simpan(): void
    {
        abort_unless(Auth::user()->hasRole('Verifikator'), 403);
        $ttd = $this->milikSaya();

        $this->validate([
            'nama' => ['required', 'string', 'max:100'],
            'nip' => ['required', 'string', 'max:50', Rule::unique('tanda_tangan', 'nip_ttd')->ignore($ttd?->id)],
            'jabatan' => ['required', 'string', 'max:100'],
            'gambar' => $this->aturanGambar(! $ttd?->file_ttd),
            'sandiSekarang' => ['required', 'current_password'],
        ], [
            'nip.unique' => 'NIP ini sudah dipakai data tanda tangan lain. Minta Admin menghubungkannya ke akun Anda di Kelola TTD.',
        ]);

        $ttd ??= new TandaTangan(['user_id' => Auth::id(), 'dibuat_oleh' => Auth::id(), 'status' => 'Aktif']);
        $ttd->fill(['nama_ttd' => trim($this->nama), 'nip_ttd' => trim($this->nip), 'jabatan' => trim($this->jabatan)]);

        // gantiGambar ikut menyimpan baris, jadi data baru tersimpan bersama gambarnya.
        if ($this->gambar) {
            try {
                $ttd->gantiGambar($this->gambar->getRealPath());
            } catch (Throwable $e) {
                report($e);
                $this->reset('gambar');

                throw ValidationException::withMessages(['gambar' => 'Gambar tidak bisa dibaca. Coba pilih gambar lain.']);
            }
        } else {
            $ttd->save();
        }

        $this->reset('gambar', 'sandiSekarang');
        $this->ubah = false;
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Tanda tangan tersimpan.']);
    }

    public function render()
    {
        return view('livewire.profile.tanda-tangan-kabag', ['ttd' => $this->milikSaya()]);
    }
}
