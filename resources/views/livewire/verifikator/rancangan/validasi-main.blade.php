@section('title', 'Validasi Rancangan')

<div>
    <x-ui.page-header title="Validasi Rancangan" description="Validasi hasil penelitian rancangan produk hukum" icon="patch-check" />

    <x-ui.card>
        <x-ui.tabs>
            @foreach (['menunggu-validasi' => ['hourglass-split', 'Menunggu Validasi'], 'riwayat-validasi' => ['clock-history', 'Riwayat Validasi']] as $tab => [$icon, $label])
                <x-ui.tab :active="$activeTab === $tab" :icon="$icon" wire:click="switchTab('{{ $tab }}')">
                    {{ $label }}
                    <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('{{ $tab }}')" />
                </x-ui.tab>
            @endforeach
        </x-ui.tabs>

        <div class="mt-5">
            @if ($activeTab === 'menunggu-validasi')
                <livewire:verifikator.rancangan.validasi-menunggu />
            @else
                <livewire:verifikator.rancangan.riwayat-validasi />
            @endif
        </div>
    </x-ui.card>
</div>
