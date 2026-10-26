<div>
    <x-ui.button variant="navy" icon="send" x-on:click="openModal('modalAjukanFasilitasi')">Ajukan Fasilitasi</x-ui.button>

    <x-ui.modal id="modalAjukanFasilitasi" title="Ajukan Fasilitasi" description="Pilih rancangan yang telah disetujui lalu unggah berkas hasil revisi."
        size="2xl" static on-close="resetFormFasilitasi">
        <form id="ajukanFasilitasiForm" wire:submit="submit" class="space-y-5">
            <x-form.field label="Nomor Rancangan" for="rancanganId" error="rancanganId" required>
                <x-form.select id="rancanganId" wire:model.live="rancanganId" icon="clipboard2-check" :disabled="collect($rancanganOptions)->isEmpty()">
                    <option value="" hidden>Pilih rancangan...</option>
                    @foreach ($rancanganOptions as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </x-form.select>
            </x-form.field>

            @if (collect($rancanganOptions)->isEmpty())
                <x-ui.alert type="warning">Belum ada rancangan disetujui yang dapat diajukan ke fasilitasi.</x-ui.alert>
            @endif

            @if ($rancanganId)
                <x-form.upload model="fileRancangan" :file="$fileRancangan" label="File Rancangan Fasilitasi" accept=".pdf" required
                    hint="Rancangan hasil revisi peneliti yang telah diperbaiki, format PDF maks. 20MB."
                    wire:change="resetError('fileRancangan')" />
            @endif
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="ajukanFasilitasiForm" variant="navy" icon="send" loading="submit, fileRancangan"
                :disabled="! $rancanganId || ! $fileRancangan">Ajukan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
