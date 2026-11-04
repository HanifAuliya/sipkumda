{{--
    Teks rumusan di layar. Baris "| a | b |" tampil sebagai tabel; teks biasa memakai penanda
    perubahan bila $lama diberikan. Rumusan bertabel ditampilkan apa adanya, karena tanda
    perubahan per kata tidak terbaca pada tabel.
--}}
@php
    $potongan = App\Support\TataLetakNaskah::potongan($teks);
    $adaTabel = collect($potongan)->contains(fn (array $p) => isset($p['tabel']));
@endphp

@if ($adaTabel)
    @foreach ($potongan as $p)
        @isset($p['tabel'])
            <div class="my-2 overflow-x-auto">
                <table class="w-full border-collapse font-sans text-sm">
                    @foreach ($p['tabel'] as $kolom)
                        <tr>
                            @foreach ($kolom as $isi)
                                <td class="border border-slate-300 px-2 py-1 align-top dark:border-navy-600">{{ $isi }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>
            </div>
        @else
            <span class="block whitespace-pre-line">{{ $p['teks'] }}</span>
        @endisset
    @endforeach
@else
    <span class="whitespace-pre-line">{!! $lama !== null ? App\Support\DiffKata::html($lama, $teks) : e($teks) !!}</span>
@endif
