{{--
    Bentuk naskah bila dicetak saat ini. Isinya dari TataLetakNaskah, sama dengan Word dan PDF,
    atau dari BandingNaskah bila perubahannya ditandai ($tandaiUbahan).
--}}
@php
    $ringkas ??= false;
    $tandaiUbahan ??= false;
    $tombolBaris ??= false;
    $bisaUbah ??= false;
    $sel = 'align-top pb-1';
    // Pada panel sempit, rata kanan-kiri membuat jarak antarkata terlalu renggang.
    $rata = $ringkas ? 'text-left' : 'text-justify';
    // Kolom label Menimbang dan KESATU; dilebarkan bila ada Memperhatikan, seperti pada cetakan.
    $labelPanjang = collect($blok)->contains(fn (array $b) => $b['tipe'] === 'daftar' && str_starts_with((string) $b['label'], 'Memperhatikan'));
    $lebarLabel = match (true) {
        $ringkas && $labelPanjang => 'w-28',
        $ringkas => 'w-24',
        $labelPanjang => 'w-32',
        default => 'w-28',
    };

    // Satu baris naskah bisa menjadi beberapa blok (label dan isi), tetapi tombolnya cukup satu.
    $sudahBertombol = [];
    $perluTombol = function (?int $id) use (&$sudahBertombol, $tombolBaris): bool {
        if (! $tombolBaris || $id === null || in_array($id, $sudahBertombol, true)) {
            return false;
        }
        $sudahBertombol[] = $id;

        return true;
    };
@endphp

<div @class([
    "bg-white text-black shadow-card font-['Bookman_Old_Style',Georgia,'Times_New_Roman',serif]",
    'v2-diff v2-kertas' => $tandaiUbahan,
    'kertas-pratinjau rounded-lg px-5 py-6 text-sm leading-relaxed' => $ringkas,
    'mx-auto max-w-3xl px-8 py-12 text-[0.95rem] leading-relaxed sm:px-16' => ! $ringkas,
])>
    <img src="{{ asset('assets/img/garuda.png') }}" alt="Lambang Garuda Pancasila" @class(['mx-auto mb-2', 'w-14' => $ringkas, 'w-[70px]' => ! $ringkas])>
    <p class="text-center font-bold">{{ App\Support\TataLetakNaskah::KOP }}</p>

    @foreach ($blok as $b)
        @if ($b['tipe'] === 'tengah')
            <div class="group relative mt-5 mb-2 text-center" data-baris="{{ $b['id'] ?? '' }}">
                @foreach ($b['baris'] as $baris)
                    <p>{{ $baris }}</p>
                @endforeach
                @if ($perluTombol($b['id'] ?? null))
                    @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                @endif
            </div>
        @elseif ($b['tipe'] === 'daftar')
            <table class="mb-3 w-full border-collapse">
                @foreach ($b['butir'] as $i => $butir)
                    @php $idButir = $b['ids'][$i] ?? $b['id'] ?? null; @endphp
                    <tr class="group relative" data-baris="{{ $idButir }}">
                        <td class="{{ $sel }} {{ $lebarLabel }}">{{ $i === 0 ? $b['label'] : '' }}</td>
                        <td class="{{ $sel }} w-4">{{ $i === 0 ? ':' : '' }}</td>
                        @if ($butir['nomor'])
                            <td class="{{ $sel }} w-7">{{ $butir['nomor'] }}</td>
                        @endif
                        <td class="{{ $sel }} {{ $rata }} whitespace-pre-line">{{ $butir['isi'] }}@if ($perluTombol($idButir))@include('v2.partials.tombol-baris', ['id' => $idButir, 'diubah' => $b['diubahButir'][$i] ?? false])@endif</td>
                    </tr>
                @endforeach
            </table>
        @elseif ($b['tipe'] === 'diktum')
            {{-- Lebar kolom tetap: tabel di dalam diktum tidak boleh mendorong isi keluar kertas pada layar sempit. --}}
            <table class="mb-3 w-full table-fixed border-collapse">
                <tr>
                    <td class="{{ $sel }} {{ $lebarLabel }}">{{ $b['pembuka'] }}</td>
                    <td class="{{ $sel }} w-4">:</td>
                    <td class="{{ $sel }}"></td>
                </tr>
                @foreach ($b['butir'] as $i => $butir)
                    @php $idButir = $b['ids'][$i] ?? null; @endphp
                    <tr class="group relative" data-baris="{{ $idButir }}">
                        <td class="{{ $sel }} {{ $lebarLabel }}">{{ $butir['nomor'] }}</td>
                        <td class="{{ $sel }} w-4">:</td>
                        <td class="{{ $sel }}">
                            @include('v2.partials.baris-teks', ['daftar' => $butir['baris'], 'rata' => $rata])
                            @if ($perluTombol($idButir))
                                @include('v2.partials.tombol-baris', ['id' => $idButir, 'diubah' => $b['diubahButir'][$i] ?? false])
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
        @elseif ($b['tipe'] === 'lampiran')
            {{-- Lampiran dimulai di halaman baru; kepalanya di sisi kanan seperti pada naskah cetak. --}}
            <div class="-mx-5 my-10 border-t-2 border-dashed border-slate-300 sm:-mx-16" role="separator" aria-label="Halaman lampiran"></div>
            <div class="mb-6 ml-auto w-full max-w-[60%] text-left max-sm:max-w-full">
                @foreach ($b['kepala'] as $baris)
                    <p>{{ $baris }}</p>
                @endforeach
            </div>
        @elseif ($b['tipe'] === 'ttd')
            <div class="mt-8 ml-auto w-fit max-w-full">
                @foreach ($b['baris'] as $baris)
                    <p class="min-h-6 text-center">{{ $baris }}</p>
                @endforeach
            </div>
        @elseif ($b['tipe'] === 'penutup')
            <div class="group relative mt-8" data-baris="{{ $b['id'] ?? '' }}">
                {{-- Lebar blok mengikuti baris terpanjang, supaya jabatan dan nama rata tengah sesamanya. --}}
                <div class="ml-auto w-fit max-w-full">
                    @foreach ($b['ditetapkan'] as $baris)
                        <p @class(['min-h-6', 'text-center' => ! App\Support\TataLetakNaskah::barisTempatTanggal($baris)])>{{ $baris }}</p>
                    @endforeach
                </div>
                @if ($b['diundangkan'])
                    <div class="mt-6 w-fit max-w-full">
                        @foreach ($b['diundangkan'] as $baris)
                            <p @class(['min-h-6', 'text-center' => ! App\Support\TataLetakNaskah::barisTempatTanggal($baris)])>{{ $baris }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($b['berita'])
                    <p class="mt-6">{{ $b['berita'] }}</p>
                @endif
                @if ($b['tembusan'])
                    <div class="mt-6">
                        @foreach ($b['tembusan'] as $baris)
                            <p>{{ $baris }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($perluTombol($b['id'] ?? null))
                    @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                @endif
            </div>
        @else
            <div class="group relative" data-baris="{{ $b['id'] ?? '' }}">
                @include('v2.partials.baris-teks', ['daftar' => $b['baris'], 'rata' => $rata])
                @if ($perluTombol($b['id'] ?? null))
                    @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                @endif
            </div>
        @endif
    @endforeach
</div>
