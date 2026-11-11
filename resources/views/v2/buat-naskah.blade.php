@php
    $lengkap = filled($jenis) && filled($tentang) && filled($tanggalNota) && filled($nomorNota) && $notaDinas;
@endphp

<div>
    <x-ui.button variant="navy" icon="send-plus" x-on:click="openModal('ajukanNaskahModal')">
        Ajukan Rancangan Baru
    </x-ui.button>

    <x-ui.modal id="ajukanNaskahModal" title="Ajukan Rancangan Baru" description="Lengkapi data pengajuan. Isi rancangan diperiksa pada langkah berikutnya."
        size="4xl" static on-close="resetForm">
        <form id="ajukanNaskahForm" wire:submit="simpan" class="space-y-5">
            <x-form.field label="Jenis Rancangan" for="jenis" error="jenis" required>
                <x-form.select id="jenis" wire:model.live="jenis" icon="tags">
                    <option value="" hidden>Pilih jenis rancangan</option>
                    @foreach (App\Models\Naskah::JENIS as $pilihan)
                        <option value="{{ $pilihan }}">{{ $pilihan }}</option>
                    @endforeach
                </x-form.select>
            </x-form.field>

            <x-form.field label="Tentang (Judul Rancangan)" for="tentang" error="tentang" required>
                <x-form.judul id="tentang" wire:model.live.blur="tentang" placeholder="Contoh: Pemberian Insentif Pemungutan Pajak Daerah" />
            </x-form.field>

            <div class="grid gap-5 sm:grid-cols-2">
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
                <a class="font-semibold underline" href="{{ route('v2.template', 'keputusan-bupati') }}">Keputusan Bupati</a>.
                Tanpa file Word, isi rancangan bisa diketik langsung.
            </x-ui.alert>

            {{-- Dipilih sebelum mengunggah: naskah dibaca per paragraf, matriks dari kolom Rumusan. --}}
            <x-form.field label="Bentuk File Rancangan" error="bentuk" hint="Hanya berlaku bila file Word diunggah.">
                <div class="grid gap-3 sm:grid-cols-2" role="radiogroup" aria-label="Bentuk file rancangan">
                    @foreach ([
                        App\Services\PembacaNaskahWord::BENTUK_NASKAH => ['file-earmark-text', 'Naskah Rancangan', 'Ditulis seperti naskah yang dicetak: judul, Menimbang, Mengingat, pasal atau diktum.'],
                        App\Services\PembacaNaskahWord::BENTUK_MATRIKS => ['table', 'Matriks', 'Tabel dengan kolom Materi dan Rumusan; isi kolom Rumusan yang dibaca.'],
                    ] as $nilai => [$ikon, $nama, $keterangan])
                        <label @class([
                            'flex cursor-pointer gap-3 rounded-lg p-3.5 ring-1 transition has-focus-visible:ring-2 has-focus-visible:ring-primary-500',
                            'bg-primary-50 ring-2 ring-primary-400 dark:bg-primary-500/10 dark:ring-primary-500' => $bentuk === $nilai,
                            'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $bentuk !== $nilai,
                        ])>
                            <input type="radio" name="bentuk" wire:model.live="bentuk" value="{{ $nilai }}" class="sr-only">
                            <i @class(['bi bi-' . $ikon . ' mt-0.5 text-lg', 'text-primary-600 dark:text-primary-300' => $bentuk === $nilai, 'text-slate-400' => $bentuk !== $nilai]) aria-hidden="true"></i>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">{{ $nama }}</span>
                                <span class="mt-0.5 block text-sm text-slate-500 dark:text-slate-400">{{ $keterangan }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </x-form.field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.upload model="notaDinas" :file="$notaDinas" label="Nota Dinas Perangkat Daerah" accept=".pdf" hint="PDF, maks. 20MB" required />
                <x-form.upload model="word" :file="$word" accept=".docx" hint="Word (.docx), maks. 20MB"
                    :label="$bentuk === App\Services\PembacaNaskahWord::BENTUK_MATRIKS ? 'File Matriks (opsional)' : 'File Naskah Rancangan (opsional)'" />
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
