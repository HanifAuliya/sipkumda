<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\View\View;
use Illuminate\Http\Request;
use App\Models\PerangkatDaerah;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Auth\Events\Registered;


class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $perangkatDaerah = PerangkatDaerah::all(); // Ambil semua data perangkat daerah
        return view('auth.register', compact('perangkatDaerah')); // Kirim data ke view
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_user' => [
                'required', // Nama harus diisi
                'string',   // Harus berupa teks
                'max:255',  // Maksimal panjang 255 karakter
                'regex:/^[a-zA-Z\s]+$/', // Hanya huruf dan spasi yang diperbolehkan
            ],
            'NIP' => [
                'required',            // NIP harus diisi
                'string',              // Harus berupa teks
                'unique:users,NIP',    // Harus unik di tabel users
                'digits:18',            // Panjang tepat 9 digit
                'regex:/^[0-9]+$/',    // Hanya angka yang diperbolehkan
            ],
            'email' => [
                'required',             // Email harus diisi
                'string',               // Harus berupa teks
                'lowercase',            // Harus dalam huruf kecil
                'email',                // Harus format email
                'max:255',              // Maksimal panjang 255 karakter
                'unique:users,email',   // Harus unik di tabel users
            ],
            'password' => [
                'required',                     // Password harus diisi
                'confirmed',                    // Harus sesuai dengan password_confirmation
                Rules\Password::defaults(),     // Validasi password bawaan Laravel
                'min:8',                        // Minimal panjang 8 karakter
                'regex:/[A-Za-z]/',             // Harus mengandung huruf
                'regex:/[0-9]/',                // Harus mengandung angka
                'regex:/[@$!%*?&#]/',           // Harus mengandung karakter spesial
            ],
            'perangkat_daerah_id' => [
                'required',                    // Perangkat Daerah harus diisi
                'exists:perangkat_daerah,id',  // Harus ada di tabel perangkat daerah
            ],
        ], [
            // Pesan error kustom
            'nama_user.required' => 'Nama harus diisi.',
            'nama_user.regex' => 'Nama hanya boleh berisi huruf dan spasi.',
            'NIP.required' => 'NIP harus diisi.',
            'NIP.unique' => 'NIP ini sudah terdaftar.',
            'NIP.regex' => 'NIP hanya boleh berisi angka.',
            'NIP.digits' => 'NIP harus terdiri dari 9 angka.',
            'email.required' => 'Email harus diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar.',
            'password.required' => 'Password harus diisi.',
            'password.min' => 'Password harus minimal 8 karakter.',
            'password.regex' => 'Password harus mengandung huruf, angka, dan karakter spesial.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'perangkat_daerah_id.required' => 'Perangkat Daerah harus dipilih.',
            'perangkat_daerah_id.exists' => 'Perangkat Daerah yang dipilih tidak valid.',
        ]);

        $user = User::create([
            'nama_user' => $request->nama_user, // Nama pengguna
            'NIP' => $request->NIP, // NIP
            'email' => $request->email, // Email
            'password' => Hash::make($request->password), // Hash password
            'perangkat_daerah_id' => $request->perangkat_daerah_id, // Perangkat Daerah
        ]);

        // Tambahkan role 'Tamu' ke pengguna
        $user->assignRole('Tamu');
        Log::info('User Registered: ', $user->toArray());

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
