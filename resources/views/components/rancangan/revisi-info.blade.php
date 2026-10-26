{{--
    Hasil penelitian (revisi) sebuah rancangan.
    <x-rancangan.revisi-info :revisi="$rancangan->revisi->last()" />
--}}
@props(['revisi'])

@if ($revisi)
    <x-ui.info-list {{ $attributes }}>
        <x-ui.info-item label="Status Revisi"><x-ui.status-badge :value="$revisi->status_revisi" /></x-ui.info-item>
        <x-ui.info-item label="Status Validasi"><x-ui.status-badge :value="$revisi->status_validasi" /></x-ui.info-item>
        <x-ui.info-item label="Peneliti">{{ $revisi->peneliti->nama_user ?? 'Belum ditentukan' }}</x-ui.info-item>
        <x-ui.info-item label="Peneliti Ditunjuk">{{ tanggal($revisi->tanggal_peneliti_ditunjuk, true) }}</x-ui.info-item>
        <x-ui.info-item label="Tanggal Revisi">{{ tanggal($revisi->tanggal_revisi, true) }}</x-ui.info-item>
        <x-ui.info-item label="Tanggal Validasi">{{ tanggal($revisi->tanggal_validasi, true) }}</x-ui.info-item>
        {{-- Draf revisi rancangan hanya untuk internal Bagian Hukum, tidak untuk Perangkat Daerah. --}}
        @unless (auth()->user()?->hasRole('Perangkat Daerah'))
            <x-ui.info-item label="Revisi Rancangan">
                <x-ui.file-link folder="revisi/rancangan" :path="$revisi->revisi_rancangan" label="Lihat Rancangan Revisi" />
            </x-ui.info-item>
        @endunless
        <x-ui.info-item label="Revisi Matrik">
            <x-ui.file-link folder="revisi/matrik" :path="$revisi->revisi_matrik" label="Lihat Matrik Revisi" />
        </x-ui.info-item>
        <x-ui.info-item label="Catatan Revisi">
            <span class="prose-note">{{ $revisi->catatan_revisi ?: 'Tidak ada catatan' }}</span>
        </x-ui.info-item>
        <x-ui.info-item label="Catatan Validasi">
            <span class="prose-note">{{ $revisi->catatan_validasi ?: 'Tidak ada catatan' }}</span>
        </x-ui.info-item>
        {{ $slot }}
    </x-ui.info-list>
@else
    <x-ui.empty-state icon="journal-x" title="Belum ada hasil penelitian." />
@endif
