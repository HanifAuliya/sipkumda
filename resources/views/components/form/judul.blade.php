{{--
    Kolom judul produk hukum: selebar form dan ikut memanjang mengikuti isi.
    Judul selalu satu paragraf, jadi Enter diabaikan dan baris baru dari tempelan Word menjadi spasi.
    <x-form.judul id="tentang" wire:model.live.blur="tentang" />
--}}
@php
    $model = $attributes->wire('model')->value();
    $invalid = $model && $errors->has($model);
@endphp

<textarea rows="3"
    x-on:keydown.enter.prevent
    x-on:input="if (/[\r\n]/.test($el.value)) $el.value = $el.value.replace(/\s*[\r\n]+\s*/g, ' ')"
    {{ $attributes->class(['form-control field-sizing-content max-h-80 min-h-24 resize-y leading-relaxed sm:text-base', 'form-control-invalid' => $invalid]) }}></textarea>
