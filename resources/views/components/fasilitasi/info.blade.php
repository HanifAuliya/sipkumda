{{-- Informasi fasilitasi beserta rancangan terkait. <x-fasilitasi.info :fasilitasi="$f" /> --}}
@props(['fasilitasi'])

@php $rancangan = $fasilitasi->rancangan; @endphp

<x-ui.info-list {{ $attributes }}>
    <x-ui.info-item label="Nomor Rancangan">{{ $rancangan->no_rancangan ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Jenis"><x-ui.status-badge :value="$rancangan?->jenis_rancangan" /></x-ui.info-item>
    <x-ui.info-item label="Tentang">{{ $rancangan->tentang ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="User Pengaju">{{ $rancangan->user->nama_user ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Perangkat Daerah">{{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</x-ui.info-item>
    <x-ui.info-item label="Tanggal Pengajuan">{{ tanggal($fasilitasi->tanggal_fasilitasi, true) }}</x-ui.info-item>
    <x-ui.info-item label="File Rancangan">
        <x-ui.file-link folder="fasilitasi/rancangan" :path="$fasilitasi->file_rancangan" label="Lihat File" />
    </x-ui.info-item>
    <x-ui.info-item label="Status Berkas"><x-ui.status-badge :value="$fasilitasi->status_berkas_fasilitasi" /></x-ui.info-item>
    <x-ui.info-item label="Tanggal Persetujuan">{{ tanggal($fasilitasi->tanggal_persetujuan_berkas, empty: 'Belum diverifikasi') }}</x-ui.info-item>
    <x-ui.info-item label="Catatan Persetujuan">
        <span class="prose-note">{{ $fasilitasi->catatan_persetujuan_fasilitasi ?: 'Tidak ada catatan' }}</span>
    </x-ui.info-item>
    <x-ui.info-item label="Status Validasi"><x-ui.status-badge :value="$fasilitasi->status_validasi_fasilitasi" /></x-ui.info-item>
    <x-ui.info-item label="Tanggal Validasi">{{ tanggal($fasilitasi->tanggal_validasi_fasilitasi, empty: 'Belum divalidasi') }}</x-ui.info-item>
    <x-ui.info-item label="Catatan Validasi">
        <span class="prose-note">{{ $fasilitasi->catatan_validasi_fasilitasi ?: 'Tidak ada catatan' }}</span>
    </x-ui.info-item>
    <x-ui.info-item label="Nota Dinas">{{ $fasilitasi->notaDinas->nomor_nota ?? 'Belum dibuat' }}</x-ui.info-item>
    {{ $slot }}
</x-ui.info-list>
