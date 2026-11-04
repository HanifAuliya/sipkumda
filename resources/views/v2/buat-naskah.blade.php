@php
    $lengkap = filled($jenis) && filled($tentang) && filled($tanggalNota) && filled($nomorNota) && $notaDinas;
@endphp

<div>
    <x-ui.button variant="navy" icon="send-plus" x-on:click="openModal('ajukanNaskahModal')">
        Ajukan Rancangan Baru
    </x-ui.button>

    <x-ui.modal id="ajukanNaskahModal" title="Ajukan Rancangan Baru" description="Lengkapi data pengajuan. Isi rancangan diperiksa pada langkah berikutnya."
        size="3xl" static on-close="resetForm">
        <form id="ajukanNaskahForm" wire:submit="simpan" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field label="Jenis Rancangan" for="jenis" error="jenis" required>
                    <x-form.select id="jenis" wire:model.live="jenis" icon="tags">
                        <option value="" hidden>Pilih jenis rancangan</option>
                        @foreach (App\Models\Naskah::JENIS as $pilihan)
                            <option value="{{ $pilihan }}">{{ $pilihan }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Tentang" for="tentang" error="tentang" required>
                    <x-form.input id="tentang" wire:model.live.blur="tentang" placeholder="Contoh: Pemberian Insentif Pemungutan Pajak Daerah" />
                </x-form.field>

                <x-form.field label="Tanggal Nota Dinas PD" for="tanggalNota" error="tanggalNota" required hint="Akan dicetak di nota dinas hasil.">
                    <x-form.input type="date" id="tanggalNota" wire:model.live="tanggalNota" />
                </x-form.field>

                <x-form.field label="Nomor Nota Dinas PD" for="nomorNota" error="nomorNota" required hint="Akan dicetak di nota dinas hasil.">
                    <x-form.input id="nomorNota" wire:model.live.blur="nomorNota" placeholder="Contoh: NOTA-2023-001" />
                </x-form.field>
            </div>

            <x-ui.alert type="warning" title="Perhatikan:">
                Naskah paling akurat terbaca bila ditulis mengikuti template
                <a class="font-semibold underline" href="{{ route('v2.template', 'peraturan-bupati') }}">Peraturan Bupati</a> atau
                <a class="font-semibold underline" href="{{ route('v2.template', 'surat-keputusan') }}">Surat Keputusan</a>.
                Tanpa file Word, isi rancangan bisa diketik langsung.
            </x-ui.alert>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.upload model="notaDinas" :file="$notaDinas" label="Nota Dinas Perangkat Daerah" accept=".pdf" hint="PDF, maks. 20MB" required />
                <x-form.upload model="word" :file="$word" label="File Rancangan (opsional)" accept=".docx" hint="Word (.docx), maks. 20MB" />
            </div>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="ajukanNaskahForm" variant="navy" icon="file-earmark-arrow-up" loading="simpan" :disabled="! $lengkap">
                Lanjutkan
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
