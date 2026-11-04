@props(['error' => null])

@php
    $field = $error ?? $attributes->wire('model')->value() ?: $attributes->get('name');
    $invalid = $field && $errors->has($field);
@endphp

<textarea {{ $attributes->merge(['rows' => 4])->class(['form-control', 'form-control-invalid' => $invalid]) }}>{{ $slot }}</textarea>
