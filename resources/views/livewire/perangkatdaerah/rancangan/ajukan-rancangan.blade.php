@php
    $lengkap = filled($jenisRancangan) && filled($tentang) && filled($tanggalNota) && filled($nomorNota)
        && $rancangan && $matrik && $nota_dinas_pd;

    $uploads = [
        'rancangan' => ['File Rancangan', '.doc,.docx', 'Word (.docx), maks. 20MB', true],
        'matrik' => ['Matrik', '.doc,.docx', 'Word (.docx) sesuai template Bahan Penting, maks. 20MB', true],
        'nota_dinas_pd' => ['Nota Dinas Perangkat Daerah', '.pdf', 'PDF, maks. 20MB', true],
        'bahanPendukung' => ['Bahan Pendukung (opsional)', '.pdf', 'PDF, maks. 20MB', false],
    ];
@endphp

<div>
    <x-ui.button variant="navy" icon="send-plus" x-on:click="openModal('ajukanRancanganModal')">
        Ajukan Rancangan Baru
    </x-ui.button>

    <x-ui.modal id="ajukanRancanganModal" title="Ajukan Rancangan Baru" description="Lengkapi data dan unggah berkas rancangan."
        size="3xl" static on-close="resetForm">
        <form id="ajukanRancanganForm" wire:submit="submit" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field label="Jenis Rancangan" for="jenisRancangan" error="jenisRancangan" required>
                    <x-form.select id="jenisRancangan" wire:model.live="jenisRancangan" icon="tags">
                        <option value="" hidden>Pilih jenis rancangan</option>
                        <option value="Peraturan Bupati">Peraturan Bupati</option>
                        <option value="Surat Keputusan">Surat Keputusan</option>
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
                Gunakan template matrik dari menu <strong>Bahan Penting</strong>. File <strong>Rancangan</strong> dan
                <strong>Matrik</strong> berformat Word (.docx), selain itu PDF.
            </x-ui.alert>

            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ($uploads as $field => [$label, $accept, $hint, $required])
                    <x-form.upload :model="$field" :file="$this->{$field}" :label="$label" :accept="$accept" :hint="$hint"
                        :required="$required" wire:change="resetError('{{ $field }}')" />
                @endforeach
            </div>

            <x-rancangan.ai-check target="rancangan, matrik, nota_dinas_pd, jalankanPrediksi" :gagal="$gagalCek"
                :sudah="$sudahDicek" :hasil="$hasilPrediksi" :catatan="$catatanKurang" />
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="ajukanRancanganForm" variant="navy" icon="file-earmark-arrow-up"
                loading="submit, jalankanPrediksi" :disabled="! $lengkap">
                {{ $sudahDicek && $hasilPrediksi === 'Tidak Lengkap' ? 'Tetap Ajukan' : 'Ajukan' }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
