<div>
    <x-ui.modal id="modalDetailFasilitasi" title="Detail Fasilitasi" :description="$fasilitasi?->rancangan?->no_rancangan" size="4xl" on-close="close">
        @if ($fasilitasi)
            <div class="space-y-5">
                @if ($info = $fasilitasi->statusInfo())
                    <x-ui.alert :type="$info['type']" :title="$info['title']">{{ $info['message'] }}</x-ui.alert>
                @endif

                <section>
                    <h4 class="mb-3 text-sm font-semibold">Tahapan Paraf</h4>
                    <x-fasilitasi.tahapan :fasilitasi="$fasilitasi" />
                </section>

                <section class="rounded-xl px-4 ring-1 ring-slate-100 dark:ring-navy-800">
                    <x-fasilitasi.info :fasilitasi="$fasilitasi" />
                </section>
            </div>
        @else
            <x-rancangan.detail :rancangan="null" />
        @endif

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Tutup</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
