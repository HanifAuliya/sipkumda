{{--
    Informasi utama rancangan (identitas, status berkas, dan file).
    <x-rancangan.info :rancangan="$selectedRancangan" />
    Tambahan baris khusus halaman bisa diletakkan di slot.
--}}
@props(['rancangan'])

<x-ui.info-list {{ $attributes }}>
    <x-ui.info-item label="Nomor">{{ $rancangan->no_rancangan ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Jenis"><x-ui.status-badge :value="$rancangan->jenis_rancangan" /></x-ui.info-item>
    <x-ui.info-item label="Tentang">{{ $rancangan->tentang ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Tanggal Pengajuan">{{ tanggal($rancangan->tanggal_pengajuan, true) }}</x-ui.info-item>
    <x-ui.info-item label="User Pengaju">{{ $rancangan->user->nama_user ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Perangkat Daerah">{{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Nomor Nota">{{ $rancangan->nomor_nota ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Tanggal Nota">{{ tanggal($rancangan->tanggal_nota) }}</x-ui.info-item>
    <x-ui.info-item label="Status Rancangan"><x-ui.status-badge :value="$rancangan->status_rancangan" /></x-ui.info-item>
    <x-ui.info-item label="Rancangan Disetujui">{{ tanggal($rancangan->tanggal_rancangan_disetujui, true) }}</x-ui.info-item>
    <x-ui.info-item label="Status Berkas"><x-ui.status-badge :value="$rancangan->status_berkas" /></x-ui.info-item>
    <x-ui.info-item label="Berkas Disetujui">{{ tanggal($rancangan->tanggal_berkas_disetujui, true) }}</x-ui.info-item>
    <x-ui.info-item label="Catatan Berkas">
        <span class="prose-note">{{ $rancangan->catatan_berkas ?: 'Tidak ada catatan' }}</span>
    </x-ui.info-item>
    <x-ui.info-item label="Nota Dinas">
        <x-ui.file-link folder="rancangan/nota_dinas" :path="$rancangan->nota_dinas_pd" label="Lihat Nota" />
    </x-ui.info-item>
    <x-ui.info-item label="File Rancangan">
        <x-ui.file-link folder="rancangan/rancangan" :path="$rancangan->rancangan" label="Lihat Rancangan" />
    </x-ui.info-item>
    <x-ui.info-item label="Matrik">
        <x-ui.file-link folder="rancangan/matrik" :path="$rancangan->matrik" label="Lihat Matrik" />
    </x-ui.info-item>
    <x-ui.info-item label="Bahan Pendukung">
        <x-ui.file-link folder="rancangan/bahan_pendukung" :path="$rancangan->bahan_pendukung" label="Lihat Bahan" />
    </x-ui.info-item>
    {{ $slot }}
</x-ui.info-list>
