{{--
    Satu baris tabel naskah, dipakai Dashboard dan menu Daftar. Seluruh baris bisa diklik untuk membuka detail.
    Variabel: $naskah, $kolomPd (tampilkan kolom Perangkat Daerah), $kolomWaktu (tampilkan kolom Diperbarui).
--}}
@php
    $tahap = $naskah->tahap;
    $pd = $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user ?? '-';
    // Siapa yang sedang memegang naskah, supaya tidak perlu membuka detail hanya untuk tahu posisinya.
    $giliran = match (true) {
        $tahap === App\Enums\TahapNaskah::Selesai => null,
        $tahap->peran() === 'Peneliti' && $naskah->peneliti => 'Peneliti: ' . $naskah->peneliti->nama_user,
        $tahap->peran() === 'Perangkat Daerah' => 'Perangkat Daerah',
        default => implode(' / ', $tahap->pemegang()),
    };
@endphp

<tr wire:key="naskah-{{ $naskah->id }}" class="cursor-pointer"
    x-on:click="$event.target.closest('a, button') || Livewire.dispatch('bukaDetailNaskah', { slug: {{ \Illuminate\Support\Js::from($naskah->slug) }} })">
    <td class="sm:whitespace-nowrap">
        <p class="sel-utama">{{ $naskah->nomor_tampil }}</p>
        <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1.5">
            <x-ui.status-badge :value="$naskah->jenis" />
            <x-ui.badge :color="$tahap->warna()" class="sm:hidden">{{ $tahap->label() }}</x-ui.badge>
        </div>
        <p class="sel-judul mt-1.5 max-sm:line-clamp-3 sm:hidden">{{ $naskah->tentang }}</p>
        @if ($kolomPd)
            <p class="sel-kedua mt-1 sm:hidden">{{ $pd }}</p>
        @endif
    </td>
    <td class="min-w-64 max-sm:hidden"><p class="sel-judul" title="{{ $naskah->tentang }}">{{ $naskah->tentang }}</p></td>
    @if ($kolomPd)
        <td class="min-w-44 max-sm:hidden"><p class="sel-judul">{{ $pd }}</p></td>
    @endif
    <td class="max-sm:hidden">
        <x-ui.badge :color="$tahap->warna()">{{ $tahap->label() }}</x-ui.badge>
        @if ($giliran)
            <p class="sel-kedua mt-1 whitespace-nowrap">Giliran {{ $giliran }}</p>
        @endif
    </td>
    @if ($kolomWaktu)
        <td class="sel-kedua whitespace-nowrap max-lg:hidden">{{ $naskah->updated_at->diffForHumans() }}</td>
    @endif
    <td>
        <div class="flex justify-end">
            <x-naskah.detail-button :naskah="$naskah" label="Detail" />
        </div>
    </td>
</tr>
