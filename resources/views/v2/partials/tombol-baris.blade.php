{{-- Tombol di tepi kertas menuju baris itu di Matriks. Pada baris yang tidak berubah, tombolnya muncul saat kursor di atasnya. --}}
<button type="button" wire:click="bukaBaris({{ $id }})" title="{{ $bisaUbah ? 'Edit baris ini' : 'Lihat di Matriks' }}" aria-label="{{ $bisaUbah ? 'Edit baris ini' : 'Lihat di Matriks' }}"
    @class([
        'absolute top-0 -right-8 flex size-7 items-center justify-center rounded-md bg-white font-sans text-sm text-primary-600 ring-1 ring-primary-200 transition hover:bg-primary-500 hover:text-white sm:-right-12 sm:size-8',
        'opacity-0 group-hover:opacity-100 focus:opacity-100' => ! $diubah,
    ])>
    <i class="bi bi-{{ $bisaUbah ? 'pencil' : 'table' }}" aria-hidden="true"></i>
</button>
