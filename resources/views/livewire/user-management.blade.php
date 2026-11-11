@section('title', 'Kelola User')

@php
    $roles = \Spatie\Permission\Models\Role::orderBy('name')->pluck('name');
    $sortable = ['id' => 'No', 'nama_user' => 'Nama', 'NIP' => 'NIP'];
@endphp

<div>
    <x-ui.page-header title="Daftar Pengguna" description="Kelola pengguna SIFKUMDA (tambah, ubah, hapus)" icon="people">
        @if ($isAdmin)
            <x-slot:actions>
                <x-ui.button variant="navy" icon="person-plus" wire:click="create" loading="create">Tambah Pengguna</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card title="Pengguna SIFKUMDA">
        <div class="grid grid-cols-2 gap-3 md:grid-cols-12">
            <x-form.input wire:model.live.debounce.400ms="search" icon="search" class="col-span-2 md:col-span-6" placeholder="Cari nama, NIP, atau email..." aria-label="Cari" />
            <x-form.select wire:model.live="roleFilter" icon="shield-check" class="md:col-span-3" aria-label="Filter role">
                <option value="">Semua Role</option>
                @foreach ($roles as $roleName)
                    <option value="{{ $roleName }}">{{ $roleName }}</option>
                @endforeach
            </x-form.select>
            <x-form.select wire:model.live="perPage" icon="list-ol" class="md:col-span-3" aria-label="Data per halaman">
                @foreach ([5, 10, 20, 50] as $size)
                    <option value="{{ $size }}">{{ $size }} / halaman</option>
                @endforeach
            </x-form.select>
        </div>
        <x-ui.loading target="search, roleFilter, perPage" class="mt-3">Memuat data...</x-ui.loading>

        <x-ui.table class="mt-5">
            <x-slot:head>
                @foreach ($sortable as $field => $label)
                    <th @class(['max-sm:hidden' => $field !== 'nama_user'])>
                        <button type="button" wire:click="sortBy('{{ $field }}')" class="-my-2 inline-flex items-center gap-1 py-2 hover:text-primary-600">
                            {{ $label }}
                            <i @class([
                                'bi text-xs',
                                'bi-chevron-expand opacity-40' => $sortField !== $field,
                                'bi-chevron-up text-primary-500' => $sortField === $field && $sortDirection === 'asc',
                                'bi-chevron-down text-primary-500' => $sortField === $field && $sortDirection === 'desc',
                            ])></i>
                        </button>
                    </th>
                @endforeach
                <th class="max-sm:hidden">Perangkat Daerah</th>
                <th class="max-sm:hidden">Role</th>
                @if ($isAdmin)
                    <th class="text-right">Aksi</th>
                @endif
            </x-slot:head>

            @forelse ($users as $index => $user)
                <tr wire:key="user-{{ $user->id }}">
                    <td class="sel-kedua max-sm:hidden">{{ $users->firstItem() + $index }}</td>
                    <td class="sm:min-w-56">
                        <p class="sel-utama">{{ $user->nama_user }}</p>
                        <p class="sel-kedua break-all sm:break-normal sm:whitespace-nowrap">{{ $user->email }}</p>
                        <p class="sel-kedua sm:hidden">NIP {{ $user->NIP }}</p>
                        <div class="mt-1.5 flex flex-wrap gap-1 sm:hidden">
                            @foreach ($user->getRoleNames() as $roleName)
                                <x-ui.badge color="primary">{{ $roleName }}</x-ui.badge>
                            @endforeach
                        </div>
                    </td>
                    <td class="whitespace-nowrap max-sm:hidden">{{ $user->NIP }}</td>
                    <td class="min-w-48 max-sm:hidden"><p class="sel-judul">{{ $user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</p></td>
                    <td class="max-sm:hidden">
                        <div class="flex flex-wrap gap-1">
                            @foreach ($user->getRoleNames() as $roleName)
                                <x-ui.badge color="primary">{{ $roleName }}</x-ui.badge>
                            @endforeach
                        </div>
                    </td>
                    @if ($isAdmin)
                    <td>
                        <div class="flex justify-end gap-1.5">
                                @unless ($user->email_verified_at)
                                    <x-ui.button variant="soft-primary" size="sm" square icon="send" title="Kirim Ulang Aktivasi" aria-label="Kirim ulang aktivasi"
                                        wire:click="kirimUlangAktivasi({{ $user->id }})" loading="kirimUlangAktivasi({{ $user->id }})" />
                                @endunless
                                <x-ui.button variant="outline" size="sm" icon="pencil" class="max-sm:size-10 max-sm:px-0" title="Ubah" aria-label="Ubah {{ $user->nama_user }}"
                                    wire:click="edit({{ $user->id }})" loading="edit({{ $user->id }})"><span class="max-sm:hidden">Ubah</span></x-ui.button>
                                <x-ui.button variant="soft-danger" size="sm" icon="trash" class="max-sm:size-10 max-sm:px-0" title="Hapus" aria-label="Hapus {{ $user->nama_user }}"
                                    x-on:click="$confirm({ text: {{ \Illuminate\Support\Js::from('Pengguna ' . $user->nama_user . ' akan dihapus permanen.') }}, confirmText: 'Ya, hapus' })
                                        .then(ok => ok && $wire.delete({{ $user->id }}))"><span class="max-sm:hidden">Hapus</span></x-ui.button>
                        </div>
                    </td>
                    @endif
                </tr>
            @empty
                <x-ui.empty-row :colspan="$isAdmin ? 6 : 5" icon="people" message="Tidak ada pengguna yang cocok.">
                    <x-ui.reset-filter :aktif="filled($search) || filled($roleFilter)" :nilai="['search' => '', 'roleFilter' => '']" />
                </x-ui.empty-row>
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $users->links() }}</div>
    </x-ui.card>

    {{-- Tambah / ubah pengguna --}}
    <x-ui.modal id="userModal" :title="$userIdToEdit ? 'Ubah Pengguna' : 'Tambah Pengguna'"
        :description="$userIdToEdit ? null : 'Tautan untuk membuat password akan dikirim ke email pengguna.'" size="2xl" static>
        <form id="userForm" wire:submit="{{ $userIdToEdit ? 'update' : 'store' }}" class="grid gap-5 sm:grid-cols-2">
            <x-form.field label="Nama" for="nama_user" error="nama_user" required>
                <x-form.input id="nama_user" wire:model="nama_user" icon="person" />
            </x-form.field>

            <x-form.field label="NIP" for="NIP" error="NIP" required hint="18 digit">
                <x-form.input id="NIP" wire:model="NIP" icon="person-badge" inputmode="numeric" maxlength="18" />
            </x-form.field>

            <x-form.field label="Email" for="email" error="email" required>
                <x-form.input type="email" id="email" wire:model="email" icon="envelope" />
            </x-form.field>

            <x-form.field label="Role" for="role" error="role" required>
                <x-form.select id="role" wire:model="role" icon="shield-check">
                    <option value="" hidden>Pilih role</option>
                    @foreach ($roles as $roleName)
                        <option value="{{ $roleName }}">{{ $roleName }}</option>
                    @endforeach
                </x-form.select>
            </x-form.field>

            <x-form.field label="Perangkat Daerah" for="perangkat_daerah_id" error="perangkat_daerah_id" required class="sm:col-span-2">
                <x-form.select id="perangkat_daerah_id" wire:model="perangkat_daerah_id" icon="building">
                    <option value="" hidden>Pilih perangkat daerah</option>
                    @foreach ($daftar_perangkat_daerah as $pd)
                        <option value="{{ $pd->id }}">{{ $pd->nama_perangkat_daerah }}</option>
                    @endforeach
                </x-form.select>
            </x-form.field>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="userForm" variant="navy" icon="floppy" loading="store, update">
                {{ $userIdToEdit ? 'Simpan Perubahan' : 'Tambah Pengguna' }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
