{{--
    Tabel data responsif.
    <x-ui.table>
        <x-slot:head> <th>Kolom</th> ... </x-slot:head>
        <tr> <td>...</td> </tr>
    </x-ui.table>
--}}
<div {{ $attributes->class('-mx-5 overflow-x-auto sm:-mx-6') }}>
    <table class="table-base min-w-full">
        <thead>
            <tr>{{ $head }}</tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
