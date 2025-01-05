<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class UpdatePassword extends Component
{
    public $current_password;
    public $password;
    public $password_confirmation;

    public function updatePassword()
    {
        try {
            // Validasi input
            $this->validate([
                'current_password' => ['required', 'current_password'], // Password lama harus sesuai
                'password' => ['required', Password::defaults(), 'confirmed'], // Password baru harus valid
            ]);

            // Perbarui password pengguna
            $user = Auth::user();
            $user->update([
                'password' => Hash::make($this->password),
            ]);

            // Reset input setelah berhasil
            $this->reset(['current_password', 'password', 'password_confirmation']);

            // Kirim event untuk notifikasi sukses
            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Password berhasil diperbarui.',
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
            // Kirim event untuk notifikasi error umum
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Gagal!',
                'message' => 'Terjadi kesalahan saat memperbarui password.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.profile.update-password');
    }
}
