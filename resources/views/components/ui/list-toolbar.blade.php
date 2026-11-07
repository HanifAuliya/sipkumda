{{--
    Toolbar daftar: kotak cari (properti Livewire "search") + jumlah per halaman ("perPage").
    <x-ui.list-toolbar placeholder="Cari nomor atau tentang..." :options="[3, 5, 10, 50]" />
    Slot default untuk filter tambahan di antara keduanya.
--}}
@props(['placeholder' => 'Cari...', 'options' => [5, 10, 20, 50], 'search' => 'search', 'perPage' => 'perPage'])

<div {{ $attributes->class('mb-5') }}>
    {{-- Di HP pilihan filter berdampingan, supaya isi daftar lebih cepat terlihat. --}}
    <div class="grid grid-cols-2 gap-3 sm:flex sm:items-center [&>:last-child:nth-child(even)]:max-sm:col-span-2">
        <x-form.input wire:model.live.debounce.400ms="{{ $search }}" icon="search" class="col-span-2 sm:flex-1" :placeholder="$placeholder" aria-label="Cari" />
        {{ $slot }}
        <x-form.select wire:model.live="{{ $perPage }}" icon="list-ol" class="sm:w-48" aria-label="Data per halaman">
            @foreach ($options as $size)
                <option value="{{ $size }}">{{ $size }} / halaman</option>
            @endforeach
        </x-form.select>
    </div>
    <x-ui.loading target="{{ $search }}, {{ $perPage }}" class="mt-2">Memuat data...</x-ui.loading>
</div>
