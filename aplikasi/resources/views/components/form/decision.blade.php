{{--
    Pilihan keputusan setuju/tolak berbentuk kartu radio.
    <x-form.decision model="statusBerkas" :value="$statusBerkas" approve="Disetujui" reject="Ditolak" />
--}}
@props([
    'model',
    'value' => null,
    'approve' => 'Disetujui',
    'reject' => 'Ditolak',
    'approveLabel' => 'Setujui',
    'rejectLabel' => 'Tolak',
])

@php
    $options = [
        $approve => ['check-circle-fill text-emerald-500', $approveLabel, 'bg-emerald-50 text-emerald-700 ring-emerald-300 dark:bg-emerald-500/10 dark:text-emerald-300'],
        $reject => ['x-circle-fill text-rose-500', $rejectLabel, 'bg-rose-50 text-rose-700 ring-rose-300 dark:bg-rose-500/10 dark:text-rose-300'],
    ];
@endphp

<div {{ $attributes->class('grid grid-cols-2 gap-3') }}>
    @foreach ($options as $optionValue => [$icon, $label, $activeClass])
        <label @class([
            'flex cursor-pointer items-center gap-2.5 rounded-lg px-4 py-3 text-sm font-semibold ring-1 transition',
            $activeClass => $value === $optionValue,
            'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $value !== $optionValue,
        ])>
            <input type="radio" wire:model.live="{{ $model }}" value="{{ $optionValue }}" class="sr-only">
            <i class="bi bi-{{ $icon }}"></i> {{ $label }}
        </label>
    @endforeach
</div>
