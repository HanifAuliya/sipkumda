<div>
    <x-form.input wire:model.live.debounce.400ms="search" icon="search" class="mb-5" placeholder="Cari nomor rancangan atau tentang..." aria-label="Cari" />

    <x-ui.table>
        <x-slot:head>
            <th>No Rancangan</th>
            <th>Tentang</th>
            <th>Disetujui</th>
            <th>Validasi</th>
            <th class="text-right">Aksi</th>
        </x-slot:head>

        @forelse ($riwayatFasilitasi as $fasilitasi)
            <tr wire:key="riwayat-persetujuan-{{ $fasilitasi->id }}">
                <td class="font-semibold whitespace-nowrap text-navy-900 dark:text-white">{{ $fasilitasi->rancangan->no_rancangan ?? '-' }}</td>
                <td class="min-w-64">{{ $fasilitasi->rancangan->tentang ?? '-' }}</td>
                <td class="whitespace-nowrap">{{ tanggal($fasilitasi->tanggal_persetujuan_berkas) }}</td>
                <td><x-ui.status-badge :value="$fasilitasi->status_validasi_fasilitasi" /></td>
                <td class="text-right">
                    <x-ui.button variant="navy" size="sm" square icon="eye" title="Lihat detail"
                        x-on:click="Livewire.dispatch('openDetailFasilitasi', { id: {{ $fasilitasi->id }} })" />
                </td>
            </tr>
        @empty
            <x-ui.empty-row colspan="5" icon="clock-history" message="Belum ada riwayat persetujuan fasilitasi." />
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $riwayatFasilitasi->links() }}</div>
</div>
