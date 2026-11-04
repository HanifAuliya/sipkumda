<div>
    <x-ui.modal id="modalDetailRancangan" title="Detail Rancangan" :description="$rancangan?->no_rancangan" size="6xl" on-close="refreshData">
        <x-rancangan.detail :rancangan="$rancangan" />

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
