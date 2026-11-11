<div class="mx-auto max-w-4xl">
    <x-ui.page-header title="Perangkat Daerah" description="Daftar instansi yang bisa dipilih pada akun pengguna dan dokumen." icon="building" :back="route('v2.beranda')">
        <x-slot:actions>
            <x-ui.button icon="plus-lg" wire:click="tambah" loading="tambah">Tambah Perangkat Daerah</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card :title="$total . ' Perangkat Daerah'">
        <x-ui.list-toolbar placeholder="Cari nama Perangkat Daerah..." :options="[10, 20, 50]" />

        <x-ui.table>
            <x-slot:head>
                <th>Nama</th>
                <th class="max-sm:hidden">Dipakai oleh</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($halaman as $perangkat)
                @php $dipakai = $pemakaian[$perangkat->id] ?? null; @endphp
                <tr wire:key="pd-{{ $perangkat->id }}">
                    <td>
                        <p class="sel-utama">{{ $perangkat->nama_perangkat_daerah }}</p>
                        <p class="sel-kedua mt-1 sm:hidden">{{ $dipakai ?? 'Belum dipakai' }}</p>
                    </td>
                    <td class="sel-kedua max-sm:hidden">{{ $dipakai ?? 'Belum dipakai' }}</td>
                    <td>
                        <div class="flex justify-end gap-2">
                            <x-ui.button size="sm" variant="outline" icon="pencil" wire:click="ubah({{ $perangkat->id }})" loading="ubah({{ $perangkat->id }})">Ubah</x-ui.button>
                            @if ($dipakai)
                                {{-- Yang sudah dipakai tidak bisa dihapus, supaya pengguna dan dokumennya tidak kehilangan instansi. --}}
                                <span title="Sudah dipakai, jadi hanya bisa diubah namanya">
                                    <x-ui.button size="sm" variant="outline-danger" icon="trash" disabled>Hapus</x-ui.button>
                                </span>
                            @else
                                <x-ui.button size="sm" variant="outline-danger" icon="trash" wire:click="hapus({{ $perangkat->id }})" loading="hapus({{ $perangkat->id }})"
                                    wire:confirm="Hapus {{ $perangkat->nama_perangkat_daerah }}? Tindakan ini tidak bisa dibatalkan.">Hapus</x-ui.button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="3" icon="building" :message="$search !== '' ? 'Tidak ada Perangkat Daerah yang cocok dengan pencarian.' : 'Belum ada Perangkat Daerah.'">
                    <x-ui.reset-filter :aktif="$search !== ''" :nilai="['search' => '']" />
                </x-ui.empty-row>
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $halaman->links() }}</div>
    </x-ui.card>

    <x-ui.modal id="modalPerangkatDaerah" :title="$idDiubah ? 'Ubah Nama Perangkat Daerah' : 'Tambah Perangkat Daerah'" size="lg">
        <form wire:submit="simpan" id="formPerangkatDaerah">
            <x-form.field label="Nama Perangkat Daerah" for="namaPerangkatDaerah" error="nama" required
                :hint="$idDiubah ? 'Nama baru langsung tampil pada pengguna, naskah, dan dokumen yang memakai Perangkat Daerah ini.' : 'Tulis nama lengkap tanpa singkatan, misalnya Dinas Kesehatan.'">
                <x-form.input id="namaPerangkatDaerah" wire:model="nama" maxlength="255" placeholder="Contoh: Dinas Kesehatan" autocomplete="off" />
            </x-form.field>
        </form>

        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="formPerangkatDaerah" icon="check2" loading="simpan">{{ $idDiubah ? 'Simpan Nama' : 'Tambah' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
