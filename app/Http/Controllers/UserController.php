<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PerangkatDaerah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;


class UserController extends Controller
{
    public function index()
    {
        $users = User::with('perangkatDaerah')->get();
        $daftar_perangkat_daerah = PerangkatDaerah::all();

        return view('users.index', compact('users', 'daftar_perangkat_daerah'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama_user' => 'required|string|max:255',
            'NIP' => 'required|string|max:50|unique:users,NIP',
            'email' => 'required|email|lowercase|max:100|unique:users,email,',
            'role' => 'required|in:Admin,Verifikator,Peneliti,Perangkat_Daerah,Tamu',
            'perangkat_daerah_id' => 'nullable|exists:perangkat_daerah,id',
        ], [
            'nama_user.required' => 'Nama pengguna harus diisi.',
            'NIP.required' => 'NIP harus diisi.',
            'NIP.unique' => 'NIP sudah digunakan.',
            'email.required' => 'Email harus diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'role.required' => 'Role harus dipilih.',
            'perangkat_daerah_id.exists' => 'Perangkat Daerah tidak valid.',
        ]);

        // Generate random password
        $password = Str::random(8);
        $validated['password'] = Hash::make($password);

        $user = User::create($validated);

        // Kirim password ke email pengguna
        Mail::to($user->email)->send(new \App\Mail\SendPasswordToUser($password));

        return redirect()->back()->with('sweetalert', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Pengguna berhasil ditambahkan!',
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'nama_user' => 'required|string|max:255',
            'NIP' => [
                'required',
                'string',
                'max:18',
                Rule::unique('users', 'NIP')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'lowercase',
                'max:100',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => 'required|in:Admin,Verifikator,Peneliti,Perangkat_Daerah,Tamu',
            'perangkat_daerah_id' => 'nullable|exists:perangkat_daerah,id',
        ], [
            'nama_user.required' => 'Nama pengguna harus diisi.',
            'NIP.required' => 'NIP harus diisi.',
            'NIP.unique' => 'NIP sudah digunakan.',
            'email.required' => 'Email harus diisi.',
            'email.unique' => 'Email sudah digunakan.',
            'role.required' => 'Role harus dipilih.',
            'perangkat_daerah_id.exists' => 'Perangkat Daerah tidak valid.',
        ]);

        $user->update($validated);

        return redirect()->back()->with('sweetalert', [
            'type' => 'success',
            'title' => 'Diperbarui!',
            'message' => 'Data Pengguna berhasil diperbarui!',
        ]);
    }


    public function destroy($id)
    {
        User::findOrFail($id)->delete();

        return redirect()->back()->with('success', 'Pengguna berhasil dihapus!');
    }
}
