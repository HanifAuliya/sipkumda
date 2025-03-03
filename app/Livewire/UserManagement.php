<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\PerangkatDaerah;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Livewire\Component;
use Livewire\WithPagination;

class UserManagement extends Component
{
    use WithPagination;

    public $search = '';
    public $page = 1;
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'asc';
    public $roleFilter = ''; // Filter berdasarkan role

    public $isAdmin = false; // Menandai apakah user adalah Admin
    public $isVerifier = false; // Menandai apakah user adalah Verifikator


    public $nama_user, $NIP, $email, $password, $role, $perangkat_daerah_id;
    public $daftar_perangkat_daerah;
    public $userIdToEdit = null;

    public $canManageUsers = false; // Hak akses admin

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'id'],
        'sortDirection' => ['except' => 'asc'],
        'roleFilter' => ['except' => ''],
        'perPage' => ['except' => '10'], // Untuk jumlah data per halaman
        'page' => ['except' => 1],     // Untuk pagination
    ];

    protected $listeners = ['refreshTable' => '$refresh', 'deleteUser' => 'delete'];

    public function mount()
    {
        $this->isAdmin = Auth::user()->hasRole('Super Admin');
        $this->isVerifier = Auth::user()->hasRole(['Verifikator', 'Admin']);
        $this->daftar_perangkat_daerah = PerangkatDaerah::all();

        if (!$this->isAdmin && !$this->isVerifier) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }
    }

    public function updatingSearch()
    {
        $this->resetPage(); // Reset pagination saat pencarian berubah
    }

    public function updatingPerPage()
    {
        $this->resetPage(); // Reset pagination saat jumlah per halaman berubah
    }

    public function updatingRoleFilter()
    {
        $this->resetPage();
    }
    public function updatingSortField()
    {
        $this->resetPage(); // Reset pagination saat sorting field berubah
    }

    public function sortBy($field)
    {

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function store()
    {
        if (!$this->isAdmin) {
            abort(403, 'Anda tidak memiliki izin untuk menambah pengguna.');
        }
        try {
            $validated = $this->validate([
                'nama_user' => 'required|string|max:255',
                'NIP' => 'required|string|size:18|unique:users,NIP',
                'email' => 'required|email:rfc,dns|lowercase|max:100|unique:users,email',
                'password' => 'nullable|string|min:8|confirmed',
                'role' => 'required|exists:roles,name',
                'perangkat_daerah_id' => 'required|exists:perangkat_daerah,id',
            ]);

            // Buat password acak
            $generatedPassword = Str::random(8);

            // Tambahkan password yang sudah di-hash
            $validated['password'] = Hash::make($generatedPassword);

            // Hapus role sebelum membuat user
            $role = $validated['role'];
            unset($validated['role']);

            // Simpan user ke dalam variabel
            $user = User::create($validated);

            // Assign role ke user
            $user->assignRole($role);

            $this->resetInput();

            // Kirim password ke email pengguna
            Mail::to($validated['email'])->send(new \App\Mail\SendPasswordToUser($generatedPassword));

            $this->dispatch('closeModal', 'addUserModal');
            // Dispatch success event
            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Pengguna berhasil ditambahkan!',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Dispatch event untuk SweetAlert error validasi
            $this->dispatch('swal:error', [
                'type' => 'error',
                'title' => 'Kesalahan!',
                'message' => 'Ada kesalahan pada input data. Periksa kembali!',
            ]);
            // Tambahkan untuk debugging jika diperlukan
            throw $e;
        }
    }



    public function edit($id)
    {
        if (!$this->isAdmin) {
            abort(403, 'Anda tidak memiliki izin untuk mengedit pengguna.');
        }
        // Reset error validasi sebelumnya
        $this->resetErrorBag();
        $this->resetValidation();

        $user = User::findOrFail($id);
        $this->userIdToEdit = $user->id;
        $this->nama_user = $user->nama_user;
        $this->NIP = $user->NIP;
        $this->email = $user->email;
        // Mengambil nama role pertama yang terkait dengan user
        $this->role = $user->getRoleNames()->first(); // Pastikan hanya satu role yang diambil
        $this->perangkat_daerah_id = $user->perangkat_daerah_id;
    }

    public function update()
    {

        try {
            $user = User::findOrFail($this->userIdToEdit);

            // Validasi input
            $validated = $this->validate([
                'nama_user' => 'required|string|max:255',
                'NIP' => [
                    'required',
                    'string',
                    'size:18', // Harus tepat 18 karakter
                    'unique:users,NIP,' . $user->id, // Pastikan unik kecuali untuk user yang sedang diedit
                ],
                'email' => [
                    'required',
                    'email:rfc,dns', // Memeriksa format email yang valid dan domain yang valid
                    'lowercase', // Memastikan email disimpan dalam huruf kecil
                    'max:100', // Batasan panjang email maksimal 100 karakter
                    'unique:users,email,' . $user->id, // Pastikan email unik, kecuali untuk user yang sedang diedit
                ],
                'perangkat_daerah_id' => 'required|exists:perangkat_daerah,id',
                'role' => 'required|string|exists:roles,name', // Pastikan role ada di tabel roles
            ]);

            // Perbarui data pengguna
            $user->update([
                'nama_user' => $validated['nama_user'],
                'NIP' => $validated['NIP'],
                'email' => $validated['email'],
                'perangkat_daerah_id' => $validated['perangkat_daerah_id'],
            ]);

            // Tetapkan role ke pengguna menggunakan Spatie
            $user->syncRoles([$validated['role']]); // Role diatur menggunakan Spatie

            // Reset input
            $this->resetInput();

            $this->dispatch('closeModal', 'editUserModal');
            // Dispatch notifikasi sukses
            $this->dispatch('swal:modal', [
                'type' => 'success',
                'title' => 'Berhasil!',
                'message' => 'Pengguna berhasil diperbarui.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Dispatch event untuk SweetAlert error validasi
            $this->dispatch('swal:error', [
                'type' => 'error',
                'title' => 'Kesalahan!',
                'message' => 'Ada kesalahan pada input data. Periksa kembali!',
            ]);
            // Tambahkan untuk debugging jika diperlukan
            throw $e;
        }
    }

    public function delete($id)
    {
        // Pastikan hanya admin yang bisa menghapus
        if (!$this->isAdmin) {
            abort(403, 'Anda tidak memiliki izin untuk menghapus pengguna.');
        }

        // Hapus user berdasarkan ID
        User::destroy($id);

        // Kirim event ke frontend untuk menampilkan notifikasi sukses
        $this->dispatch('swal:user', [
            'type' => 'success',
            'title' => 'Dihapus',
            'message' => 'Pengguna berhasil dihapus!',
        ]);
    }


    public function resetInput()
    {
        $this->nama_user = '';
        $this->NIP = '';
        $this->email = '';
        $this->password = '';
        $this->role = '';
        $this->perangkat_daerah_id = null;
        $this->userIdToEdit = null;

        // Reset error validasi sebelumnya
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::with(['roles', 'perangkatDaerah']) // Eager loading relasi roles dan perangkat daerah
            ->when($this->search, function ($query) {
                $query->where('nama_user', 'like', '%' . $this->search . '%')
                    ->orWhere('NIP', 'like', '%' . $this->search . '%')
                    ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->roleFilter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', $this->roleFilter);
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.user-management', compact('users'))->layout('layouts.app');
    }
}
