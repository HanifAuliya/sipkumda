@section('title', 'Master Data')

<div>
    <x-ui.page-header title="Master Data" description="Rekap data produk hukum yang tercatat di sistem" icon="database">
        <x-slot:actions>
            <x-ui.button variant="success" icon="file-earmark-spreadsheet" target="_blank"
                :href="route('export.masterdata', ['tahun' => $tahun, 'jenis' => $jenis])">Export Excel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.alert type="info" icon="info-circle" class="mb-6">
        Data sebelum sistem ini diterapkan tidak tercatat di sini. Untuk arsip lama, buka halaman
        <a href="{{ route('dokumentasi.main') }}" class="font-semibold underline">Dokumentasi</a>.
    </x-ui.alert>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row">
        <x-form.select wire:model.live="tahun" icon="calendar4-week" class="sm:w-52" aria-label="Tahun">
            <option value="">Semua tahun</option>
            @foreach ($tahunList as $year)
                <option value="{{ $year }}">Tahun {{ $year }}</option>
            @endforeach
        </x-form.select>
        <x-form.select wire:model.live="jenis" icon="tags" class="sm:w-60" aria-label="Jenis rancangan">
            <option value="all">Semua jenis</option>
            <option value="Peraturan Bupati">Peraturan Bupati</option>
            <option value="Surat Keputusan">Surat Keputusan</option>
        </x-form.select>
        <x-ui.loading target="tahun, jenis" class="self-center">Memuat data...</x-ui.loading>
    </div>

    @php
        $cards = [
            'Rancangan' => ['clipboard2-data', 'rose'],
            'Revisi' => ['pencil-square', 'orange'],
            'Fasilitasi' => ['file-text', 'primary'],
            'Dokumentasi' => ['folder-fill', 'sky'],
        ];
    @endphp

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($cards as $type => [$icon, $color])
            <button type="button" wire:click="loadData('{{ $type }}')" wire:key="card-{{ $type }}-{{ $counts[$type] }}"
                @class([
                    'rounded-2xl text-left transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-400',
                    'ring-2 ring-primary-500 ring-offset-2 ring-offset-surface dark:ring-offset-navy-950' => $dataType === $type,
                    'opacity-70 hover:opacity-100' => $dataType !== $type,
                ])
                aria-pressed="{{ $dataType === $type ? 'true' : 'false' }}">
                <x-ui.stat-card :label="$type . ' Produk Hukum'" :value="$counts[$type]" suffix="Data" :icon="$icon" :color="$color" class="h-full" />
            </button>
        @endforeach
    </div>

    <x-ui.card>
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="text-base font-semibold text-navy-900 dark:text-white">
                Daftar {{ $dataType }} Produk Hukum
                <x-ui.spinner class="ml-2 size-4" wire:loading wire:target="loadData" />
            </h2>
            <x-form.input wire:model.live.debounce.400ms="search" icon="search" class="sm:w-80" placeholder="Cari tentang atau nomor rancangan..." aria-label="Cari" />
        </div>

        <x-ui.table>
            <x-slot:head>
                <th class="w-12">No</th>
                <th>No Rancangan</th>
                <th>Tentang</th>
                <th>Perangkat Daerah</th>
            </x-slot:head>

            @forelse ($selectedData as $data)
                @php $rancangan = $dataType === 'Rancangan' ? $data : $data->rancangan; @endphp
                <tr wire:key="master-{{ $dataType }}-{{ $data->getKey() }}">
                    <td class="text-slate-500">{{ $selectedData->firstItem() + $loop->index }}</td>
                    <td class="whitespace-nowrap">
                        <span class="font-semibold text-navy-900 dark:text-white">{{ $rancangan->no_rancangan ?? '-' }}</span>
                        <div class="mt-1"><x-ui.status-badge :value="$rancangan?->jenis_rancangan" /></div>
                    </td>
                    <td class="min-w-72">{{ $rancangan->tentang ?? '-' }}</td>
                    <td class="min-w-48">
                        {{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}
                        <p class="text-xs text-slate-500">{{ $rancangan->user->nama_user ?? '' }}</p>
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="4" icon="database" message="Tidak ada data ditemukan." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $selectedData->links() }}</div>
    </x-ui.card>
</div>
