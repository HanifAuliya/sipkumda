<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RealRashid\SweetAlert\Facades\Alert;


class UpdateProfileInformation extends Component
{
    public $nama_user;
    public $email;
    public $password;
    public $password_confirmation;
    public $NIP;
    public $role;
    public $perangkat_daerah_id;
    public $daftar_perangkat_daerah;


    public function mount()
    {
        // Inisialisasi data pengguna
        $user = Auth::user();
        $this->nama_user = $user->nama_user;
        $this->NIP = $user->NIP;
        $this->email = $user->email;
        $this->role = $user->role; // Role hanya untuk ditampilkan
        $this->perangkat_daerah_id = $user->perangkat_daerah_id;
        // Ambil semua perangkat daerah untuk dropdown
        $this->daftar_perangkat_daerah = \App\Models\PerangkatDaerah::all();
    }

    public function updateProfile()
    {
        try {
            // Validasi input
            $this->validate([
                'nama_user' => 'required|string|max:255',
                'email' => [
                    'required',             // Email harus diisi
                    'string',               // Harus berupa teks
                    'lowercase',            // Harus dalam huruf kecil
                    'email',                // Harus format email
                    'max:255',              // Maksimal panjang 255 karakter
                    Rule::unique('users', 'email')->ignore(Auth::id()),
                ],
                'perangkat_daerah_id' => [
                    'required',
                    'exists:perangkat_daerah,id',
                ],
            ]);

            $user = Auth::user();

            // Perbarui data pengguna
            $user->nama_user = $this->nama_user;
            $user->NIP = $this->NIP;
            $user->email = $this->email;
            $user->perangkat_daerah_id = $this->perangkat_daerah_id;

            // Reset email_verified_at jika email berubah
            if ($user->isDirty('email')) {
                $user->email_verified_at = null; // Reset email verifikasi
            }

            // Simpan ke database
            $user->save();

            // Dispatch event untuk SweetAlert sukses
            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Data berhasil diperbarui.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Kirim event untuk notifikasi error validasi
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Kesalahan Validasi!',
                'message' => 'Ada kesalahan pada input Anda. Periksa kembali.',
            ]);

            // Tambahkan untuk debugging jika diperlukan
            throw $e;
        } catch (\Exception $e) {
            // Dispatch event untuk SweetAlert error
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan saat menyimpan data. ' . $e->getMessage(),
            ]);
        }
    }

    public function sendVerificationEmail()
    {
        $user = Auth::user();

        if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !$user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();

            return back()->with('status', 'verification-link-sent');
        }
    }

    public function render()
    {

        return view('livewire.profile.update-profile-information');
    }
}
