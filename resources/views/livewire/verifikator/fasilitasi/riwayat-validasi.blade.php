<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[5, 10, 20, 50]" />

    <x-ui.table>
        <x-slot:head>
            <th>No Rancangan</th>
            <th>Tentang</th>
            <th>Perangkat Daerah</th>
            <th>Divalidasi</th>
            <th>Validasi</th>
            <th class="text-right">Aksi</th>
        </x-slot:head>

        @forelse ($fasilitasiRiwayat as $fasilitasi)
            <tr wire:key="riwayat-validasi-{{ $fasilitasi->id }}">
                <td class="font-semibold whitespace-nowrap text-navy-900 dark:text-white">{{ $fasilitasi->rancangan->no_rancangan ?? '-' }}</td>
                <td class="min-w-64">{{ $fasilitasi->rancangan->tentang ?? '-' }}</td>
                <td class="min-w-40">{{ $fasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</td>
                <td class="whitespace-nowrap">{{ tanggal($fasilitasi->tanggal_validasi_fasilitasi) }}</td>
                <td><x-ui.status-badge :value="$fasilitasi->status_validasi_fasilitasi" /></td>
                <td class="text-right">
                    <x-ui.button variant="navy" size="sm" square icon="eye" title="Lihat detail"
                        x-on:click="Livewire.dispatch('openDetailFasilitasi', { id: {{ $fasilitasi->id }} })" />
                </td>
            </tr>
        @empty
            <x-ui.empty-row colspan="6" icon="clock-history" message="Belum ada riwayat validasi fasilitasi." />
        @endforelse
    </x-ui.table>

    <div class="mt-5">{{ $fasilitasiRiwayat->links() }}</div>
</div>
