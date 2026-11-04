@section('title', 'Pilih Peneliti')

<div>
    <x-ui.page-header title="Pilih Peneliti" description="Tugaskan peneliti untuk rancangan yang berkasnya telah disetujui" icon="person-check" />

    <x-ui.card>
        <x-ui.tabs>
            @foreach ([
                'menunggu-peneliti' => ['hourglass-split', 'Menunggu Peneliti'],
                'peneliti-ditugaskan' => ['person-check', 'Peneliti Ditugaskan'],
                'daftar-peneliti' => ['people', 'Penelitian Berlangsung'],
            ] as $tab => [$icon, $label])
                <x-ui.tab :active="$activeTab === $tab" :icon="$icon" wire:click="switchTab('{{ $tab }}')">
                    {{ $label }}
                    <x-ui.spinner class="size-3.5" wire:loading wire:target="switchTab('{{ $tab }}')" />
                </x-ui.tab>
            @endforeach
        </x-ui.tabs>

        <div class="mt-5">
            @if ($activeTab === 'menunggu-peneliti')
                <livewire:verifikator.rancangan.menunggu-peneliti />
            @elseif ($activeTab === 'peneliti-ditugaskan')
                <livewire:verifikator.rancangan.peneliti-ditugaskan />
            @else
                <livewire:verifikator.rancangan.daftarpeneliti />
            @endif
        </div>
    </x-ui.card>
</div>
