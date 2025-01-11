@section('header', 'Kelola User')
@section('title', 'Manage User')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
        <ol class="breadcrumb breadcrumb-links bg-gradient-green">

            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}" class="text-white">
                    <i class="fas fa-home"></i>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('user.management') }}" class="text-white">Daftar Pengguna</a>
            </li>
            <li class="breadcrumb-item active text-white" aria-current="page">Tables</li>
        </ol>
    </nav>
@endsection

@section('actions')
    <a href="#" class="btn btn-sm btn-neutral">New</a>
    <a href="#" class="btn btn-sm btn-neutral">Filters</a>
@endsection

<div>

    <div class="row mb-1">
        <div class="col-12">
            {{-- Header Card --}}
            <div class="card  mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">Manajemen Pengguna</h3>
                    @if ($isAdmin)
                        <button class="btn btn-default" wire:click="resetInput" data-toggle="modal"
                            data-target="#addUserModal">
                            Tambah Pengguna
                        </button>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Daftar Pengguna SIPKUMDA</h3>
                </div>
                {{-- Search and Pagination Controls --}}
                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        {{-- Search Bar --}}
                        <div class="col-md-6 d-flex">
                            <div class="input-group w-100">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="ni ni-zoom-split-in"></i></span>
                                </div>
                                <input type="text" class="form-control" wire:model.live="search"
                                    placeholder="Cari nama, NIP, atau email...">
                            </div>
                        </div>

                        {{-- Per Page Dropdown --}}
                        <div class="col-md-3 d-flex">
                            <div class="input-group w-100">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="ni ni-bullet-list-67"></i></span>
                                </div>
                                <select class="form-control" wire:model.live="perPage">
                                    <option value="5">5</option>
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>

                            </div>
                        </div>

                        {{-- Filter by Role --}}
                        <div class="col-md-3 d-flex">
                            <div class="input-group w-100">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="ni ni-single-02"></i></span>
                                </div>
                                <select class="form-control" wire:model.live="roleFilter">
                                    <option value="">Semua Role</option>
                                    <option value="Admin">Admin</option>
                                    <option value="Verifikator">Verifikator</option>
                                    <option value="Peneliti">Peneliti</option>
                                    <option value="Perangkat_Daerah">Perangkat Daerah</option>
                                    <option value="Tamu">Tamu</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Tabel Data --}}
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th wire:click="sortBy('id')" class="sortable cursor-pointer">
                                        ID
                                        @if ($sortField === 'id')
                                            <span>{{ $sortDirection === 'asc' ? '🔼' : '🔽' }}</span>
                                        @endif
                                    </th>
                                    <th wire:click="sortBy('nama_user')" class="sortable cursor-pointer">
                                        Nama
                                        @if ($sortField === 'nama_user')
                                            <span>{{ $sortDirection === 'asc' ? '🔼' : '🔽' }}</span>
                                        @endif
                                    </th>
                                    <th wire:click="sortBy('NIP')" class="sortable cursor-pointer">
                                        NIP
                                        @if ($sortField === 'NIP')
                                            <span>{{ $sortDirection === 'asc' ? '🔼' : '🔽' }}</span>
                                        @endif
                                    </th>
                                    <th>
                                        Email
                                    </th>
                                    <th>Perangkat Daerah</th>
                                    <th>
                                        Role User Sebagai
                                    </th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $index => $user)
                                    <tr>
                                        <td>{{ $users->firstItem() + $index }}</td>
                                        <td class="wrap-text-td-50">{{ $user->nama_user }}</td>
                                        <td>{{ $user->NIP }}</td>
                                        <td class="still-text">{{ $user->email }}</td>
                                        <td class="wrap-text-td-50">
                                            {{ $user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}</td>
                                        <td class="wrap-text">{{ $user->getRoleNames()->implode(', ') }}</td>
                                        <td>
                                            @if ($isAdmin)
                                                <button wire:click="edit({{ $user->id }})"
                                                    class="btn btn-warning btn-sm" data-toggle="modal"
                                                    data-target="#editUserModal">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button wire:click="delete({{ $user->id }})"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            @else
                                                <span class="text-muted">Hanya untuk Admin</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Tidak ada data.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="mb-2 mb-md-0">
                            Menampilkan {{ $users->firstItem() }} hingga {{ $users->lastItem() }} dari
                            {{ $users->total() }}
                            data
                        </div>
                        <div class="d-flex justify-content-center w-100 w-md-auto">
                            {{ $users->links('pagination::bootstrap-4') }}
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <div class="dataTables_info" id="userTable_info" role="status" aria-live="polite">
                                    {{-- DataTables will automatically populate info here --}}
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-7">
                                <div class="dataTables_paginate paging_simple_numbers" id="userTable_paginate">
                                    {{-- Pagination controls will be dynamically inserted by DataTables here --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Modal Tambah --}}
                <div wire:ignore.self class="modal fade" id="addUserModal" tabindex="-1" role="dialog"
                    aria-labelledby="addUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <form wire:submit.prevent="store">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addUserModalLabel">Tambah Pengguna</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        {{-- Nama --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="nama_user" class="form-label">Nama</label>
                                            <input type="text" wire:model.blur="nama_user" class="form-control"
                                                id="nama_user">
                                            @error('nama_user')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- NIP --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="NIP" class="form-label">NIP</label>
                                            <input type="text" wire:model="NIP" class="form-control"
                                                id="NIP">
                                            @error('NIP')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        {{-- Email --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" wire:model="email" class="form-control"
                                                id="email">
                                            @error('email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- Perangkat Daerah --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="perangkat_daerah_id" class="form-label">Perangkat
                                                Daerah</label>
                                            <select wire:model="perangkat_daerah_id" class="form-control"
                                                id="perangkat_daerah_id">
                                                <option hidden>Pilih Perangkat Daerah</option>
                                                @foreach ($daftar_perangkat_daerah as $pd)
                                                    <option value="{{ $pd->id }}">
                                                        {{ $pd->nama_perangkat_daerah }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('perangkat_daerah_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        {{-- Role --}}
                                        <div class="col-md-12 mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <select wire:model="role" class="form-control" id="role" required>
                                                <option hidden>Pilih Role</option>
                                                @foreach (\Spatie\Permission\Models\Role::all() as $roleOption)
                                                    <option value="{{ $roleOption->name }}">{{ $roleOption->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('role')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-danger" data-dismiss="modal"><i
                                            class="bi bi-x-lg"></i> Tutup</button>
                                    <button type="submit" class="btn btn-outline-success">
                                        <i class="bi bi-box-arrow-down"></i> Simpan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


                {{-- Modal Edit --}}
                <div wire:ignore.self class="modal fade" id="editUserModal" tabindex="-1" role="dialog"
                    aria-labelledby="editUserModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">/
                        <div class="modal-content">
                            <form wire:submit.prevent="update">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editUserModalLabel">Edit Pengguna</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        {{-- Nama --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="nama_user" class="form-label">Nama</label>
                                            <input type="text" wire:model="nama_user" class="form-control"
                                                id="nama_user">
                                            @error('nama_user')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- NIP --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="NIP" class="form-label">NIP</label>
                                            <input type="text" wire:model="NIP" class="form-control"
                                                id="NIP">
                                            @error('NIP')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        {{-- Email --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" wire:model="email" class="form-control"
                                                id="email">
                                            @error('email')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- Perangkat Daerah --}}
                                        <div class="col-md-6 mb-3">
                                            <label for="perangkat_daerah_id" class="form-label">Perangkat
                                                Daerah</label>
                                            <select wire:model="perangkat_daerah_id" class="form-control"
                                                id="perangkat_daerah_id">
                                                <option hidden>Pilih Perangkat Daerah</option>
                                                @foreach ($daftar_perangkat_daerah as $pd)
                                                    <option value="{{ $pd->id }}">
                                                        {{ $pd->nama_perangkat_daerah }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('perangkat_daerah_id')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row">
                                        {{-- Role --}}
                                        <div class="col-md-12 mb-3">
                                            <label for="role" class="form-label">Role</label>
                                            <select wire:model="role" class="form-control" id="role" required>
                                                <option hidden>Pilih Role</option>
                                                @foreach (\Spatie\Permission\Models\Role::all() as $roleOption)
                                                    <option value="{{ $roleOption->name }}">{{ $roleOption->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('role')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-warning" data-dismiss="modal"><i
                                            class="bi bi-x-lg"></i> Tutup</button>
                                    <button type="submit" class="btn btn-outline-default"><i
                                            class="bi bi-box-arrow-down"></i>
                                        Perbarui</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        {{-- sweet alert --}}
        <script>
            window.addEventListener('swal:user', function(event) {

                const data = event.detail[0];

                $('#addUserModal').modal('hide'); // Tutup modal
                $('#editUserModal').modal('hide'); // Tutup modal
                // Tampilkan SweetAlert
                Swal.fire({
                    icon: data.type,
                    title: data.title,
                    text: data.message,
                    showConfirmButton: true,
                });

            });
            window.addEventListener('swal:error', function(event) {

                const data = event.detail[0];

                // Tampilkan SweetAlert
                Swal.fire({
                    icon: data.type,
                    title: data.title,
                    text: data.message,
                    showConfirmButton: true,
                });

            });
        </script>
    </div>
</div>
