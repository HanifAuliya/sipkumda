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
                <div class="font-sans">@include('v2.partials.tabel-isi', ['tabel' => $p, 'garis' => 'border-slate-300 dark:border-navy-600'])</div>
            </div>
        @else
            <span class="block whitespace-pre-line">{{ $p['teks'] }}</span>
        @endisset
    @endforeach
@else
    <span class="whitespace-pre-line">{!! $lama !== null ? App\Support\DiffKata::html($lama, $teks) : e($teks) !!}</span>
@endif
