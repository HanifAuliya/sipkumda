@section('title', 'Kelola TTD')

<div>
    <x-ui.page-header title="Kelola Tanda Tangan" description="Tanda tangan yang dipakai saat membuat Nota Dinas" icon="pen">
        <x-slot:actions>
            <x-ui.button icon="plus-lg" wire:click="openModalTambah" loading="openModalTambah">Tambah TTD</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.list-toolbar placeholder="Cari nama, NIP, atau jabatan..." :options="[5, 10, 20, 50]" />

        <x-ui.table>
            <x-slot:head>
                <th>Penanda Tangan</th>
                <th>Jabatan</th>
                <th>Tanda Tangan</th>
                <th>Status</th>
                <th>Nota Dinas</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($ttdList as $ttd)
                @php $gambar = private_file_url('tanda_tangan/ttd', $ttd->file_ttd); @endphp
                <tr wire:key="ttd-{{ $ttd->id }}">
                    <td class="min-w-48">
                        <p class="font-semibold text-navy-900 dark:text-white">{{ $ttd->nama_ttd }}</p>
                        <p class="text-xs text-slate-500">NIP {{ $ttd->nip_ttd ?: '-' }}</p>
                    </td>
                    <td class="min-w-40">{{ $ttd->jabatan ?: '-' }}</td>
                    <td>
                        @if ($gambar)
                            <a href="{{ $gambar }}" target="_blank" title="Buka gambar"
                                class="inline-flex h-12 w-20 items-center justify-center rounded-lg bg-white p-1 ring-1 ring-slate-200 transition hover:ring-primary-400 dark:ring-navy-700">
                                <img src="{{ $gambar }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy"
                                    x-data x-on:error="$el.replaceWith(Object.assign(document.createElement('i'), { className: 'bi bi-image text-xl text-slate-300', title: 'Gambar tidak ditemukan' }))">
                            </a>
                        @else
                            <span class="text-slate-400">-</span>
                        @endif
                    </td>
                    <td><x-ui.badge :color="$ttd->status === 'Aktif' ? 'success' : 'danger'">{{ $ttd->status }}</x-ui.badge></td>
                    <td class="whitespace-nowrap text-slate-500">{{ $ttd->nota_dinas_count }} dokumen</td>
                    <td class="text-right whitespace-nowrap">
                        <x-ui.button variant="outline" size="sm" square icon="pencil" title="Edit"
                            wire:click="openModalEdit({{ $ttd->id }})" loading="openModalEdit({{ $ttd->id }})" />
                        <x-ui.button variant="soft-danger" size="sm" square icon="trash" title="Hapus" data-nama="{{ $ttd->nama_ttd }}"
                            x-on:click="$confirm({ title: 'Hapus TTD?', text: `TTD ${$el.dataset.nama} akan dihapus permanen.`, confirmText: 'Ya, hapus' })
                                .then((ok) => ok && $wire.delete({{ $ttd->id }}))" />
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="6" icon="pen" message="Belum ada tanda tangan." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $ttdList->links() }}</div>
    </x-ui.card>

    {{-- Tambah / edit TTD --}}
    <x-ui.modal id="modalTtd" :title="$editId ? 'Edit Tanda Tangan' : 'Tambah Tanda Tangan'"
        description="Data ini dicetak pada Nota Dinas, pastikan penulisannya benar." size="2xl" static on-close="resetForm">
        <form id="ttdForm" wire:submit="save" class="space-y-5">
            <x-form.field label="Nama Lengkap" for="nama_ttd" error="nama_ttd" required hint="Sesuai Nota Dinas, contoh: TAUFIK RAHMAN, SH.">
                <x-form.input id="nama_ttd" wire:model="nama_ttd" placeholder="Nama penanda tangan" />
            </x-form.field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field label="NIP" for="nip_ttd" error="nip_ttd" required hint="18 digit angka.">
                    <x-form.input id="nip_ttd" wire:model="nip_ttd" inputmode="numeric" placeholder="19xxxxxxxxxxxxxxxx" />
                </x-form.field>
                <x-form.field label="Jabatan" for="jabatan" error="jabatan" required hint="Contoh: Pembina Tk. I">
                    <x-form.input id="jabatan" wire:model="jabatan" placeholder="Jabatan" />
                </x-form.field>
            </div>

            <x-form.field label="Status" for="status_ttd" error="status" required>
                <x-form.select id="status_ttd" wire:model="status">
                    <option value="" hidden>Pilih status</option>
                    <option value="Aktif">Aktif</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </x-form.select>
            </x-form.field>

            <div class="grid gap-5 sm:grid-cols-[1fr_auto]">
                <x-form.upload model="file_ttd" :file="$file_ttd" :label="$editId ? 'Ganti Tanda Tangan' : 'Gambar Tanda Tangan'" accept="image/png"
                    :required="! $editId" :hint="($editId ? 'Kosongkan jika tidak diganti. ' : '') . 'PNG transparan, rasio 4:3 (mis. 400×300 px), maks. 2MB.'" />

                @php
                    $pratinjau = $file_ttd && ! $errors->has('file_ttd') && method_exists($file_ttd, 'temporaryUrl')
                        ? rescue(fn () => $file_ttd->temporaryUrl(), null, false)
                        : private_file_url('tanda_tangan/ttd', $existingFile);
                @endphp
                @if ($pratinjau)
                    <figure class="text-center">
                        <div class="flex h-24 w-32 items-center justify-center rounded-lg bg-white p-2 ring-1 ring-slate-200 dark:ring-navy-700">
                            <img src="{{ $pratinjau }}" alt="" class="max-h-full max-w-full object-contain"
                                x-data x-on:error="$el.replaceWith(Object.assign(document.createElement('i'), { className: 'bi bi-image text-2xl text-slate-300', title: 'Gambar tidak ditemukan' }))">
                        </div>
                        <figcaption class="mt-1 text-xs text-slate-500">{{ $file_ttd ? 'Pratinjau baru' : 'Saat ini' }}</figcaption>
                    </figure>
                @endif
            </div>
            <x-ui.loading target="file_ttd">Mengunggah file...</x-ui.loading>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="ttdForm" icon="save" loading="save">{{ $editId ? 'Simpan Perubahan' : 'Simpan' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
