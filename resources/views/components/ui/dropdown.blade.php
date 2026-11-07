{{--
    Menu dropdown. Muncul dari sudut tombolnya, jadi terasa keluar dari tombol yang ditekan.
    <x-ui.dropdown align="right" width="w-56">
        <x-slot:trigger> <x-ui.button ...>Menu</x-ui.button> </x-slot:trigger>
        <x-ui.dropdown-item icon="pencil" wire:click="edit(1)">Edit</x-ui.dropdown-item>
    </x-ui.dropdown>
--}}
@props(['align' => 'right', 'width' => 'w-56'])

<div x-data="{ open: false }" x-on:keydown.escape="open = false" {{ $attributes->class('relative inline-block text-left') }}>
    <div x-ref="trigger" x-on:click="open = !open">
        {{ $trigger }}
    </div>

    <div x-show="open" x-cloak
        x-anchor.{{ $align === 'left' ? 'bottom-start' : 'bottom-end' }}.offset.8="$refs.trigger"
        x-on:click.outside="open = false"
        x-on:click="open = false"
        x-transition:enter="transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]"
        x-transition:enter-start="-translate-y-2 scale-90 opacity-0"
        x-transition:enter-end="translate-y-0 scale-100 opacity-100"
        x-transition:leave="transition duration-100 ease-in"
        x-transition:leave-start="translate-y-0 scale-100 opacity-100"
        x-transition:leave-end="-translate-y-1 scale-95 opacity-0"
        class="{{ $width }} {{ $align === 'left' ? 'origin-top-left' : 'origin-top-right' }} z-40 rounded-2xl bg-white p-2 shadow-xl shadow-navy-900/10 ring-1 ring-slate-900/5 dark:bg-navy-800 dark:shadow-black/40 dark:ring-white/10">
        {{ $slot }}
    </div>
</div>
