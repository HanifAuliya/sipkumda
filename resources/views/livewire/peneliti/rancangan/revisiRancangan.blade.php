@section('title', 'Menu Penelitian')

<div>
    <x-ui.page-header title="Upload Penelitian" description="Rancangan produk hukum yang ditugaskan kepada Anda untuk diteliti" icon="pencil-square" />

    <x-ui.card>
        <x-ui.tabs>
            @foreach ([
                'menunggu-revisi' => ['hourglass-split', 'Menunggu Revisi'],
                'riwayat-revisi' => ['clock-history', 'Riwayat Revisi'],
                'daftar-revisi' => ['check2-all', 'Revisi Selesai'],
            ] as $tab => [$icon, $label])
                <x-ui.tab :active="$activeTab === $tab" :icon="$icon" wire:click="switchTab('{{ $tab }}')">
                    {{ $label }}
                    <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('{{ $tab }}')" />
                </x-ui.tab>
            @endforeach
        </x-ui.tabs>

        <div class="mt-5">
            @if ($activeTab === 'menunggu-revisi')
                <livewire:peneliti.rancangan.menunggu-revisi />
            @elseif ($activeTab === 'riwayat-revisi')
                <livewire:peneliti.rancangan.riwayat-revisi />
            @else
                <livewire:peneliti.rancangan.daftar-revisi />
            @endif
        </div>
    </x-ui.card>
</div>
