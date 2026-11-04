<div>
    <x-form.input wire:model.live.debounce.400ms="search" icon="search" class="mb-5" placeholder="Cari nomor rancangan atau tentang..." aria-label="Cari" />

    <x-ui.table>
        <x-slot:head>
            <th class="w-12">No</th>
            <th>Nomor Rancangan</th>
            <th>Tentang</th>
            <th>Validasi</th>
            <th>Tanggal Revisi</th>
        </x-slot:head>

        @forelse ($revisiList as $revisi)
            <tr wire:key="daftar-revisi-{{ $revisi->id_revisi }}">
                <td class="text-slate-400">{{ $revisiList->firstItem() + $loop->index }}</td>
                <td class="font-semibold whitespace-nowrap text-navy-900 dark:text-white">{{ $revisi->rancangan->no_rancangan ?? '-' }}</td>
                <td class="min-w-64">{{ $revisi->rancangan->tentang ?? '-' }}</td>
                <td><x-ui.status-badge :value="$revisi->status_validasi" /></td>
                <td class="whitespace-nowrap">{{ tanggal($revisi->tanggal_revisi) }}</td>
            </tr>
        @empty
            <x-ui.empty-row colspan="5" icon="journal-check" message="Belum ada revisi yang selesai divalidasi." />
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $revisiList->links() }}</div>
</div>
