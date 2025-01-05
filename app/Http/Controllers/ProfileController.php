<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {


            // Validasi dan perbarui profil
            $request->user()->fill($request->validated());

            // Reset email_verified_at jika email berubah
            if ($request->user()->isDirty('email')) {
                $request->user()->email_verified_at = null; // Reset email verifikasi jika email berubah
            }

            // Simpan perubahan ke database
            $request->user()->save();

            // Set session loading menjadi false setelah proses selesai
            session()->put('loading', false);

            // SweetAlert untuk sukses
            return Redirect::route('profile.edit')
                ->with('status', 'profile-updated')
                ->with('alert', [
                    'type' => 'success',
                    'title' => 'Berhasidl!',
                    'message' => 'Profil Anda telah diperbarui.',
                ]);
        } catch (\Exception $e) {
            // Set session loading menjadi false jika terjadi kesalahan
            session()->put('loading', false);

            // Log error untuk debugging jika diperlukan
            Log::error('Error saat memperbarui profil: ' . $e->getMessage());

            // SweetAlert untuk gagal
            return Redirect::route('profile.edit')
                ->with('status', 'profile-update-failed')
                ->with('alert', [
                    'type' => 'error',
                    'title' => 'Gagal!',
                    'message' => 'Terjadi kesalahan saat memperbarui profil.',
                ]);
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
