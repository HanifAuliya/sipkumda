@php
    $uploads = [
        'fileNotaDinas' => ['Nota Dinas Perangkat Daerah', '.pdf', 'PDF, maks. 20MB', true],
        'fileRancangan' => ['File Rancangan', '.doc,.docx', 'Word (.docx), maks. 20MB', true],
        'fileMatrik' => ['Matrik', '.doc,.docx', 'Word (.docx) sesuai template, maks. 20MB', true],
        'fileBahanPendukung' => ['Bahan Pendukung (opsional)', '.pdf', 'PDF, maks. 20MB', false],
    ];
    $siapUpload = filled($tanggalNota) && filled($nomorNota) && $fileNotaDinas && $fileRancangan && $fileMatrik;
@endphp

<div>
    <x-ui.list-toolbar placeholder="Cari nomor rancangan atau tentang..." :options="[3, 5, 10, 50]" />

    <div class="space-y-4">
        @forelse ($rancangan as $item)
            @php
                $revisi = $item->revisi->last();
                $statusIcon = match ($item->status_rancangan) { 'Disetujui' => 'check-circle', 'Ditolak' => 'x-circle', default => 'gear' };
            @endphp

            <x-rancangan.item :rancangan="$item" wire:key="sedang-{{ $item->id_rancangan }}">
                <x-slot:meta>
                    <x-rancangan.meta icon="person-gear">
                        Peneliti:
                        <span @class(['font-semibold', 'text-primary-600 dark:text-primary-300' => $revisi?->peneliti, 'text-rose-500' => ! $revisi?->peneliti])>
                            {{ $revisi?->peneliti?->nama_user ?? 'Belum ditentukan' }}
                        </span>
                    </x-rancangan.meta>
                </x-slot:meta>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-slate-500">
                    <span class="flex items-center gap-1.5">Persetujuan berkas <x-ui.status-badge :value="$item->status_berkas" /></span>
                    <span class="flex items-center gap-1.5">Status revisi <x-ui.status-badge :value="$revisi?->status_revisi" /></span>
                </div>

                @if ($item->status_berkas === 'Menunggu Persetujuan')
                    <x-ui.alert type="warning" icon="send" dismissible>
                        Rancangan <strong>menunggu persetujuan</strong>. Admin sedang memeriksa berkas yang diajukan.
                    </x-ui.alert>
                @elseif ($item->status_berkas === 'Ditolak')
                    <x-ui.alert type="danger" icon="clipboard-x" dismissible>
                        <strong>Berkas ditolak.</strong> Buka <em>Kelola Rancangan → Upload Ulang Berkas</em> untuk memperbaiki.
                        @if ($item->catatan_berkas)
                            <span class="mt-1 block opacity-90">Catatan: {{ $item->catatan_berkas }}</span>
                        @endif
                    </x-ui.alert>
                @elseif ($revisi?->status_revisi === 'Menunggu Peneliti')
                    <x-ui.alert type="primary" icon="person-plus" dismissible>
                        Menunggu dipilihkan <strong>peneliti</strong> untuk rancangan ini.
                    </x-ui.alert>
                @elseif ($revisi?->status_revisi === 'Proses Revisi')
                    <x-ui.alert type="navy" icon="pencil-square" dismissible>
                        Rancangan sedang dalam <strong>proses penelitian</strong>.
                    </x-ui.alert>
                @endif

                <x-slot:aside>
                    <x-ui.badge :color="\App\Support\Status::color($item->status_rancangan)" size="lg" :icon="$statusIcon">
                        Rancangan {{ $item->status_rancangan }}
                    </x-ui.badge>
                    <p class="text-xs text-slate-500">Pengajuan tahun {{ \Illuminate\Support\Carbon::parse($item->tanggal_pengajuan)->year }}</p>

                    <x-ui.dropdown>
                        <x-slot:trigger>
                            <x-ui.button variant="outline" icon="gear">Kelola <i class="bi bi-chevron-down text-xs"></i></x-ui.button>
                        </x-slot:trigger>
                        <x-ui.dropdown-item icon="folder2-open"
                            x-on:click="Livewire.dispatch('openModalDetailRancangan', { slug: {{ \Illuminate\Support\Js::from($item->slug) }} })">
                            Detail Pengajuan
                        </x-ui.dropdown-item>
                        @if ($item->status_berkas === 'Ditolak')
                            <x-ui.dropdown-item icon="upload" wire:click="openUploadUlangModal({{ $item->id_rancangan }})">
                                Upload Ulang Berkas
                            </x-ui.dropdown-item>
                        @endif
                    </x-ui.dropdown>
                </x-slot:aside>
            </x-rancangan.item>
        @empty
            <x-ui.empty-state icon="send" title="Tidak ada rancangan yang sedang diajukan."
                description="Klik “Ajukan Rancangan Baru” untuk memulai pengajuan." class="py-12" />
        @endforelse
    </div>

    <div class="mt-5">{{ $rancangan->links() }}</div>

    {{-- Upload ulang berkas yang ditolak --}}
    <x-ui.modal id="uploadUlangBerkasModal" title="Upload Ulang Berkas" description="Perbaiki berkas rancangan yang ditolak."
        size="3xl" static on-close="resetForm">
        <form id="uploadUlangForm" wire:submit="uploadUlangBerkas" class="space-y-5">
            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field label="Tanggal Nota" for="ulang-tanggalNota" error="tanggalNota" required>
                    <x-form.input type="date" id="ulang-tanggalNota" wire:model.live="tanggalNota" />
                </x-form.field>
                <x-form.field label="Nomor Nota" for="ulang-nomorNota" error="nomorNota" required>
                    <x-form.input id="ulang-nomorNota" wire:model.live.blur="nomorNota" placeholder="Masukkan nomor nota" />
                </x-form.field>
            </div>

            <x-ui.alert type="warning" title="Perhatikan:">
                Gunakan template matrik dari <strong>Bahan Penting</strong>. File Rancangan dan Matrik berformat Word (.docx), selain itu PDF.
            </x-ui.alert>

            <div class="grid gap-5 sm:grid-cols-2">
                @foreach ($uploads as $field => [$label, $accept, $hint, $required])
                    <x-form.upload :model="$field" :file="$this->{$field}" :label="$label" :accept="$accept" :hint="$hint"
                        :required="$required" wire:change="resetError('{{ $field }}')" />
                @endforeach
            </div>

            <label class="flex items-start gap-2.5 text-sm text-slate-600 dark:text-slate-300">
                <input type="checkbox" wire:model="hapusBahanPendukung"
                    class="mt-0.5 rounded border-slate-300 text-primary-500 focus:ring-primary-200 dark:border-navy-700 dark:bg-navy-900">
                <span>
                    Hapus bahan pendukung lama
                    <span class="block text-xs text-slate-500">Centang bila Anda tidak lagi menggunakan bahan pendukung.</span>
                </span>
            </label>

            <x-rancangan.ai-check target="fileRancangan, fileMatrik, fileNotaDinas, cekKelengkapanUlang" :gagal="$gagalCekUlang"
                :sudah="$sudahDicekUlang" :hasil="$hasilPrediksiUlang" :catatan="$catatanKurangUlang" />
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="uploadUlangForm" variant="navy" icon="upload"
                loading="uploadUlangBerkas, cekKelengkapanUlang" :disabled="! $siapUpload">
                {{ $sudahDicekUlang && $hasilPrediksiUlang === 'Tidak Lengkap' ? 'Tetap Ajukan Ulang' : 'Upload Ulang' }}
            </x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
