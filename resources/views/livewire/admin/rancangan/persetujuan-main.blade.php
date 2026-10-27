@section('title', 'Pengecekan Berkas')

<div>
    <x-ui.page-header title="Persetujuan Berkas" description="Periksa dan verifikasi berkas rancangan yang diajukan" icon="clipboard2-check" />

    <x-ui.card>
        <x-ui.tabs>
            @foreach (['menunggu' => ['hourglass-split', 'Menunggu Persetujuan'], 'ditolak' => ['x-circle', 'Berkas Ditolak'], 'riwayat' => ['check2-circle', 'Berkas Disetujui']] as $tab => [$icon, $label])
                <x-ui.tab :active="$activeTab === $tab" :icon="$icon" wire:click="switchTab('{{ $tab }}')">
                    {{ $label }}
                    <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('{{ $tab }}')" />
                </x-ui.tab>
            @endforeach
        </x-ui.tabs>

        <div class="mt-5">
            @if ($activeTab === 'menunggu')
                <livewire:admin.rancangan.persetujuan-menunggu />
            @elseif ($activeTab === 'ditolak')
                <livewire:admin.rancangan.persetujuan-ditolak />
            @else
                <livewire:admin.rancangan.persetujuan-riwayat />
            @endif
        </div>
    </x-ui.card>
</div>
