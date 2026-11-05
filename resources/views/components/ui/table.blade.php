{{--
    Tabel data responsif.
    <x-ui.table>
        <x-slot:head> <th>Kolom</th> ... </x-slot:head>
        <tr> <td>...</td> </tr>
    </x-ui.table>
--}}
<div wire:loading.class="opacity-60" {{ $attributes->class('-mx-5 overflow-x-auto transition-opacity duration-150 sm:-mx-6') }}>
    <table class="table-base min-w-full">
        <thead>
            <tr>{{ $head }}</tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
