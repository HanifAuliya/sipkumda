{{--
    Badge untuk nilai status/jenis; warnanya diambil dari App\Support\Status.
    <x-ui.status-badge :value="$rancangan->status_berkas" />
--}}
@props(['value' => null, 'empty' => 'N/A'])

@if (filled($value))
    <x-ui.badge :color="\App\Support\Status::color($value)" {{ $attributes }}>{{ $value }}</x-ui.badge>
@else
    <x-ui.badge color="slate" {{ $attributes }}>{{ $empty }}</x-ui.badge>
@endif
