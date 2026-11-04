{{--
    Menu dropdown.
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
        x-anchor.{{ $align === 'left' ? 'bottom-start' : 'bottom-end' }}.offset.6="$refs.trigger"
        x-on:click.outside="open = false"
        x-on:click="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="{{ $width }} z-40 rounded-xl bg-white p-1.5 shadow-lg ring-1 ring-slate-900/5 dark:bg-navy-800 dark:ring-white/10">
        {{ $slot }}
    </div>
</div>
