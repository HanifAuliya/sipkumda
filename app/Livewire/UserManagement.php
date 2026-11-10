<?php

namespace App\Livewire;

use App\Mail\AktivasiAkun;
use App\Models\User;
use App\Models\PerangkatDaerah;
use App\Support\Wa\NomorWa;
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


    public $nama_user, $NIP, $email, $password, $role, $perangkat_daerah_id, $no_wa;
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
        $this->isAdmin = Auth::user()->hasRole(['Super Admin', 'Admin']);
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
        // Hanya kolom yang memang bisa diurutkan
        if (! in_array($field, ['id', 'nama_user', 'NIP'], true)) {
            return;
        }

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
                'role' => 'required|exists:roles,name',
                'perangkat_daerah_id' => 'required|exists:perangkat_daerah,id',
                'no_wa' => NomorWa::aturan(),
            ], [], ['no_wa' => 'nomor WhatsApp']);
            $validated['no_wa'] = NomorWa::normal($validated['no_wa'] ?? null);

            // Password acak ini tidak diberikan kepada siapa pun; pengguna membuat sendiri lewat tautan aktivasi.
            $validated['password'] = Hash::make(Str::random(64));

            // Hapus role sebelum membuat user
            $role = $validated['role'];
            unset($validated['role']);

            // Simpan user ke dalam variabel
            $user = User::create($validated);

            // Assign role ke user
            $user->assignRole($role);

            $this->resetInput();
            $this->dispatch('closeModal', 'userModal');

            if ($this->kirimAktivasi($user)) {
                $this->dispatch('swal:modal', [
                    'type' => 'success',
                    'title' => 'Berhasil!',
                    'message' => 'Pengguna ditambahkan. Tautan aktivasi sudah dikirim ke ' . $user->email . '.',
                ]);
            } else {
                $this->dispatch('swal:modal', [
                    'type' => 'warning',
                    'title' => 'Email belum terkirim',
                    'message' => 'Pengguna sudah ditambahkan, tetapi tautan aktivasi gagal dikirim. Coba lagi lewat tombol Kirim Ulang Aktivasi di baris pengguna tersebut.',
                ]);
            }
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



    public function kirimUlangAktivasi($id)
    {
        if (!$this->isAdmin) {
            abort(403, 'Anda tidak memiliki izin untuk mengirim aktivasi.');
        }

        $user = User::findOrFail($id);

        // Akun yang sudah aktif memakai Lupa Password.
        if ($user->email_verified_at) {
            return;
        }

        $this->kirimAktivasi($user)
            ? $this->dispatch('swal:toast', ['type' => 'success', 'message' => 'Tautan aktivasi dikirim ke ' . $user->email . '.'])
            : $this->dispatch('swal:modal', ['type' => 'error', 'title' => 'Email gagal terkirim', 'message' => 'Periksa pengaturan email server, lalu coba lagi.']);
    }

    private function kirimAktivasi(User $user): bool
    {
        try {
            Mail::to($user->email)->send(new AktivasiAkun($user));

            return true;
        } catch (\Throwable $e) {
            report($e);

            return false;
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
        $this->no_wa = $user->no_wa ? NomorWa::tampil($user->no_wa) : '';

        $this->dispatch('openModal', 'userModal');
    }

    public function create()
    {
        if (!$this->isAdmin) {
            abort(403, 'Anda tidak memiliki izin untuk menambah pengguna.');
        }

        $this->resetInput();
        $this->dispatch('openModal', 'userModal');
    }

    public function update()
    {
        if (!$this->isAdmin) {
            abort(403, 'Anda tidak memiliki izin untuk mengubah pengguna.');
        }

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
                'no_wa' => NomorWa::aturan(),
            ], [], ['no_wa' => 'nomor WhatsApp']);

            // Perbarui data pengguna
            $user->update([
                'nama_user' => $validated['nama_user'],
                'NIP' => $validated['NIP'],
                'email' => $validated['email'],
                'perangkat_daerah_id' => $validated['perangkat_daerah_id'],
                'no_wa' => NomorWa::normal($validated['no_wa'] ?? null),
            ]);

            // Tetapkan role ke pengguna menggunakan Spatie
            $user->syncRoles([$validated['role']]); // Role diatur menggunakan Spatie

            // Reset input
            $this->resetInput();

            $this->dispatch('closeModal', 'userModal');
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
        $this->no_wa = '';
        $this->userIdToEdit = null;

        // Reset error validasi sebelumnya
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function render()
    {
        $users = User::with(['roles', 'perangkatDaerah']) // Eager loading relasi roles dan perangkat daerah
            ->when($this->search, function ($query) {
                // Dikelompokkan agar tidak "membatalkan" filter role
                $query->where(function ($q) {
                    $q->where('nama_user', 'like', '%' . $this->search . '%')
                        ->orWhere('NIP', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
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
