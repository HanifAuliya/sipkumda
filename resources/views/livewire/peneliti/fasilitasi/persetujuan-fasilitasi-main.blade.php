@section('title', 'Persetujuan Fasilitasi')

<div>
    <x-ui.page-header title="Persetujuan Fasilitasi" description="Periksa berkas fasilitasi rancangan produk hukum" icon="file-check" />

    <x-ui.card>
        <x-ui.tabs>
            @foreach (['menunggu-persetujuan' => ['hourglass-split', 'Menunggu Persetujuan'], 'riwayat-persetujuan' => ['clock-history', 'Riwayat Persetujuan']] as $tab => [$icon, $label])
                <x-ui.tab :active="$activeTab === $tab" :icon="$icon" wire:click="switchTab('{{ $tab }}')">
                    {{ $label }}
                    <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('{{ $tab }}')" />
                </x-ui.tab>
            @endforeach
        </x-ui.tabs>

        <div class="mt-5">
            @if ($activeTab === 'menunggu-persetujuan')
                <livewire:peneliti.fasilitasi.persetujuan-fasilitasi-menunggu />
            @else
                <livewire:peneliti.fasilitasi.riwayat-fasilitasi />
            @endif
        </div>
    </x-ui.card>
</div>
