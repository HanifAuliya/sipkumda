<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;

use Livewire\Component;

class DeleteUser extends Component
{
    public $password;

    public function deleteAccount()
    {
        try {
            // Validasi input password
            $this->validate([
                'password' => ['required', 'current_password'], // Validasi password saat ini
            ]);

            $user = Auth::user();

            // Logout pengguna sebelum menghapus akun
            Auth::logout();

            // Hapus akun pengguna
            $user->delete();

            // Invalidate session dan regenerasi token
            session()->invalidate();
            session()->regenerateToken();

            // Dispatch notifikasi sukses
            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Account Deleted',
                'message' => 'Your account has been successfully deleted.',
            ]);

            return redirect('/');
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Dispatch notifikasi error jika password salah
            $this->dispatch('swal:modal', [
                'type' => 'error',
                'title' => 'Error',
                'message' => 'Password is incorrect. Please try again.',
            ]);
        }
    }


    public function render()
    {
        return view('livewire.profile.delete-user');
    }
}
