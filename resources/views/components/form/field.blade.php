{{--
    Pembungkus label + kontrol + pesan error.
    <x-form.field label="Nama" for="nama_user" error="nama_user" required>
        <x-form.input id="nama_user" wire:model="nama_user" />
    </x-form.field>
--}}
@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null, 'required' => false])

<div {{ $attributes->class('space-y-1.5') }}>
    @if ($label)
        <label @if ($for) for="{{ $for }}" @endif class="block text-sm font-semibold text-slate-700 dark:text-slate-200">
            {{ $label }}
            @if ($required)<span class="text-rose-500">*</span>@endif
        </label>
    @endif

    {{ $slot }}

    @if ($hint)
        <p class="text-xs text-slate-500 dark:text-slate-400">{{ $hint }}</p>
    @endif

    @if ($error)
        @error($error)
            <p class="flex items-center gap-1 text-xs font-medium text-rose-600 dark:text-rose-400">
                <i class="bi bi-exclamation-circle"></i> {{ $message }}
            </p>
        @enderror
    @endif
</div>
