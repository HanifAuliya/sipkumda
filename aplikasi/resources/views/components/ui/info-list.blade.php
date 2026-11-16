{{--
    Daftar label–nilai (pengganti tabel detail).
    <x-ui.info-list>
        <x-ui.info-item label="Nomor">{{ $r->no_rancangan }}</x-ui.info-item>
    </x-ui.info-list>
--}}
<dl {{ $attributes->class('divide-y divide-slate-100 dark:divide-navy-800') }}>
    {{ $slot }}
</dl>
