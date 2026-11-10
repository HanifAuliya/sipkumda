<div class="mx-auto max-w-5xl">
    <x-ui.page-header title="Format Dokumen" description="Kertas, margin, huruf, kop, dan isi dokumen yang dicetak. Berlaku untuk dokumen yang dicetak sesudah disimpan." icon="file-earmark-text" :back="route('v2.beranda')" />

    <x-ui.card :padding="false" class="mb-6">
        <div class="p-3">
            <x-ui.tabs>
                <x-ui.tab :active="$tab === 'kepbup'" icon="file-earmark-text" wire:click="$set('tab', 'kepbup')">Keputusan Bupati</x-ui.tab>
                <x-ui.tab :active="$tab === 'perbup'" icon="file-earmark-text" wire:click="$set('tab', 'perbup')">Peraturan Bupati</x-ui.tab>
                <x-ui.tab :active="$tab === 'nota'" icon="file-earmark-text" wire:click="$set('tab', 'nota')">Nota Dinas</x-ui.tab>
            </x-ui.tabs>
        </div>
    </x-ui.card>

    @if ($tab === 'nota')
        @include('v2.partials.format-nota')
    @else
        @include('v2.partials.format-naskah', ['b' => $tab])
    @endif
</div>
