@section('title', 'Rancanganku')

<div>
    <x-ui.page-header title="Rancanganku" description="Pengajuan rancangan produk hukum perangkat daerah" icon="clipboard2">
        <x-slot:actions>
            <livewire:rancangan.bahan-penting-dropdown />
            <livewire:perangkat-daerah.rancangan.ajukan-rancangan />
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.tabs>
            <x-ui.tab :active="$activeTab === 'sedang_diajukan'" icon="send" wire:click="switchTab('sedang_diajukan')">
                Sedang Diajukan
                <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('sedang_diajukan')" />
            </x-ui.tab>
            <x-ui.tab :active="$activeTab === 'riwayat_pengajuan'" icon="check2-circle" wire:click="switchTab('riwayat_pengajuan')">
                Pengajuan Diterima
                <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('riwayat_pengajuan')" />
            </x-ui.tab>
        </x-ui.tabs>

        <div class="mt-5">
            @if ($activeTab === 'sedang_diajukan')
                <livewire:perangkat-daerah.rancangan.sedang-diajukan />
            @else
                <livewire:perangkat-daerah.rancangan.riwayat-pengajuan />
            @endif
        </div>
    </x-ui.card>
</div>
