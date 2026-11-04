@section('title', 'Manajemen Fasilitasi')

<div>
    <x-ui.page-header title="Manajemen Fasilitasi" description="Perbarui tahapan paraf fasilitasi yang telah divalidasi" icon="file-earmark-break" />

    <x-ui.card>
        <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[5, 10, 20, 50]" />

        <x-ui.table>
            <x-slot:head>
                <th>Rancangan</th>
                <th>Tahapan Paraf</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($fasilitasiList as $fasilitasi)
                @php
                    $tahapan = $fasilitasi->tahapanParaf();
                    $jumlahSelesai = collect($tahapan)->where(1, 'Selesai')->count();
                @endphp
                <tr wire:key="manajemen-fasilitasi-{{ $fasilitasi->id }}">
                    <td class="min-w-72">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-semibold text-navy-900 dark:text-white">{{ $fasilitasi->rancangan->no_rancangan ?? '-' }}</span>
                            <x-ui.status-badge :value="$fasilitasi->rancangan?->jenis_rancangan" />
                        </div>
                        <p class="mt-0.5 text-slate-600 dark:text-slate-300">{{ $fasilitasi->rancangan->tentang ?? '-' }}</p>
                        <p class="mt-0.5 text-xs text-slate-500">{{ $fasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</p>
                    </td>
                    <td class="min-w-96"><x-fasilitasi.progres :fasilitasi="$fasilitasi" /></td>
                    <td class="text-right whitespace-nowrap">
                        @if ($jumlahSelesai === count($tahapan))
                            <x-ui.badge color="success" icon="check-circle" size="lg">Selesai</x-ui.badge>
                        @else
                            @can('update', $fasilitasi)
                                <x-ui.button variant="navy" size="sm" icon="list-check" wire:click="openUpdateStatus({{ $fasilitasi->id }})"
                                    loading="openUpdateStatus({{ $fasilitasi->id }})">Perbarui Tahap</x-ui.button>
                            @else
                                <x-ui.badge color="warning" size="lg">{{ $jumlahSelesai }}/{{ count($tahapan) }} selesai</x-ui.badge>
                            @endcan
                        @endif
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="3" icon="file-earmark-break" message="Belum ada fasilitasi yang divalidasi." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $fasilitasiList->links() }}</div>
    </x-ui.card>

    {{-- Perbarui tahapan --}}
    <x-ui.modal id="modalUpdateStatus" title="Perbarui Tahapan Fasilitasi" :description="$selectedFasilitasi?->rancangan?->no_rancangan"
        size="3xl" static on-close="resetModal">
        @if ($selectedFasilitasi)
            <div class="space-y-5">
                <x-ui.info-list class="rounded-xl px-4 ring-1 ring-slate-100 dark:ring-navy-800">
                    <x-ui.info-item label="Tentang">{{ $selectedFasilitasi->rancangan->tentang ?? '-' }}</x-ui.info-item>
                    <x-ui.info-item label="Perangkat Daerah">{{ $selectedFasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</x-ui.info-item>
                </x-ui.info-list>

                <x-fasilitasi.tahapan :fasilitasi="$selectedFasilitasi" />

                <form id="updateStatusForm" class="space-y-5"
                    x-on:submit.prevent="$confirm({ title: 'Tandai tahap selesai?', text: 'Perubahan status tidak dapat dibatalkan.', confirmText: 'Ya, perbarui' })
                        .then((ok) => ok && $wire.updateStatus())">
                    <x-form.field label="Tahap yang selesai" error="selectedStatus" required>
                        <div class="grid gap-3 sm:grid-cols-2">
                            @foreach ($tahapanTersisa as $kolom => $label)
                                <label @class([
                                    'flex cursor-pointer items-center gap-3 rounded-lg px-4 py-3 text-sm font-semibold ring-1 transition',
                                    'bg-primary-50 text-primary-700 ring-primary-300 dark:bg-primary-500/10 dark:text-primary-300' => $selectedStatus === $kolom,
                                    'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $selectedStatus !== $kolom,
                                ])>
                                    <input type="radio" wire:model.live="selectedStatus" value="{{ $kolom }}" class="sr-only">
                                    <i @class(['bi', 'bi-record-circle-fill' => $selectedStatus === $kolom, 'bi-circle' => $selectedStatus !== $kolom])></i>
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </x-form.field>

                    <x-ui.alert type="danger" icon="exclamation-triangle">
                        Pastikan rancangan sudah benar. Perubahan status <strong>tidak dapat dibatalkan</strong>.
                    </x-ui.alert>
                </form>
            </div>
        @else
            <x-rancangan.detail :rancangan="null" />
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="updateStatusForm" variant="success" icon="check-circle" loading="updateStatus">Tandai Selesai</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
