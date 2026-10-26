@section('title', 'Fasilitasiku')

<div>
    <x-ui.page-header title="Fasilitasiku" description="Kelola fasilitasi produk hukum perangkat daerah" icon="files">
        <x-slot:actions>
            <livewire:perangkat-daerah.fasilitasi.ajukan-fasilitasi />
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.tabs>
            @foreach (['fasilitasi-berlangsung' => ['bookmark-dash', 'Fasilitasi Berlangsung'], 'riwayat-fasilitasi' => ['bookmark-star', 'Riwayat Fasilitasi']] as $tab => [$icon, $label])
                <x-ui.tab :active="$activeTab === $tab" :icon="$icon" wire:click="switchTab('{{ $tab }}')">
                    {{ $label }}
                    <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('{{ $tab }}')" />
                </x-ui.tab>
            @endforeach
        </x-ui.tabs>

        <div class="mt-5">
            @if ($activeTab === 'fasilitasi-berlangsung')
                <livewire:perangkat-daerah.fasilitasi.fasilitasi-berlangsung />
            @else
                <livewire:perangkat-daerah.fasilitasi.riwayat-fasilitasi />
            @endif
        </div>
    </x-ui.card>
</div>
