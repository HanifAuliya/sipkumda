<div class="mx-auto max-w-2xl px-4 py-8">
    <a href="{{ route('v2.beranda') }}" wire:navigate class="text-sm text-slate-500 hover:text-navy-900 dark:hover:text-white">
        <i class="bi bi-arrow-left"></i> Pekerjaan
    </a>
    <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-navy-900 dark:text-white">Buat naskah baru</h1>
    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Isi data pengajuan. Baris matriks bisa dibaca dari Word atau diketik langsung pada langkah berikutnya.</p>

    <form wire:submit="simpan" class="mt-6 space-y-6 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
        <x-form.field label="Jenis" for="jenis" error="jenis" required>
            <div class="grid grid-cols-2 gap-2">
                @foreach (App\Models\Naskah::JENIS as $pilihan)
                    <label wire:key="jenis-{{ $loop->index }}" @class([
                        'cursor-pointer rounded-xl px-4 py-3 text-sm font-semibold ring-1 transition',
                        'bg-primary-50 text-primary-700 ring-primary-300 dark:bg-primary-500/15 dark:text-primary-200' => $jenis === $pilihan,
                        'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $jenis !== $pilihan,
                    ])>
                        <input type="radio" class="sr-only" wire:model.live="jenis" value="{{ $pilihan }}">
                        {{ $pilihan }}
                    </label>
                @endforeach
            </div>
        </x-form.field>

        <x-form.field label="Tentang" for="tentang" error="tentang" required>
            <x-form.textarea id="tentang" wire:model="tentang" rows="3" placeholder="Contoh: Tata Cara Pembagian Alokasi Dana Desa" />
        </x-form.field>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Nomor Nota Dinas PD" for="nomorNota" error="nomorNota" required>
                <x-form.input id="nomorNota" wire:model="nomorNota" />
            </x-form.field>
            <x-form.field label="Tanggal Nota Dinas" for="tanggalNota" error="tanggalNota" required>
                <x-form.input id="tanggalNota" type="date" wire:model="tanggalNota" />
            </x-form.field>
        </div>

        <x-form.field label="Nota Dinas PD (PDF)" for="notaDinas" error="notaDinas" required>
            <input id="notaDinas" type="file" wire:model="notaDinas" accept="application/pdf"
                class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:file:bg-navy-800 dark:file:text-slate-200">
        </x-form.field>

        <x-form.field label="Naskah Word (.docx)" for="word" error="word"
            hint="Opsional. Sistem memecahnya menjadi baris matriks, lalu Anda memeriksanya. Tanpa file ini, Anda mengetik langsung.">
            <input id="word" type="file" wire:model="word" accept=".docx"
                class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:file:bg-navy-800 dark:file:text-slate-200">
        </x-form.field>

        <p class="rounded-lg bg-slate-50 px-3 py-2.5 text-sm text-slate-600 dark:bg-navy-800 dark:text-slate-300">
            <i class="bi bi-lightbulb"></i> Pembacaan paling akurat bila naskah ditulis mengikuti template:
            <a class="font-semibold text-primary-600 hover:underline dark:text-primary-300" href="{{ route('v2.template', 'peraturan-bupati') }}">Peraturan Bupati</a> atau
            <a class="font-semibold text-primary-600 hover:underline dark:text-primary-300" href="{{ route('v2.template', 'surat-keputusan') }}">Surat Keputusan</a>.
            Lampiran dan bahan pendukung diunggah di tab Berkas setelah naskah dibuat.
        </p>

        <div class="flex justify-end border-t border-slate-100 pt-5 dark:border-navy-800">
            <x-ui.button type="submit" icon="arrow-right" loading="simpan">Lanjut ke matriks</x-ui.button>
        </div>
    </form>
</div>
