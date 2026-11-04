{{-- Bentuk naskah bila dicetak saat ini. Isinya dari TataLetakNaskah, sama dengan Word dan PDF. --}}
@php $sel = 'align-top pb-1'; @endphp

<div class="mx-auto max-w-3xl bg-white px-8 py-12 text-[0.95rem] leading-relaxed text-black shadow-card sm:px-16 font-['Bookman_Old_Style',Georgia,'Times_New_Roman',serif]">
    <p class="text-center font-bold">{{ App\Support\TataLetakNaskah::KOP }}</p>

    @foreach ($blok as $b)
        @if ($b['tipe'] === 'tengah')
            <div class="mt-5 mb-2 text-center">
                @foreach ($b['baris'] as $baris)
                    <p>{{ $baris }}</p>
                @endforeach
            </div>
        @elseif ($b['tipe'] === 'daftar')
            <table class="mb-3 w-full border-collapse">
                @foreach ($b['butir'] as $i => $butir)
                    <tr>
                        <td class="{{ $sel }} w-28">{{ $i === 0 ? $b['label'] : '' }}</td>
                        <td class="{{ $sel }} w-4">{{ $i === 0 ? ':' : '' }}</td>
                        @if ($butir['nomor'])
                            <td class="{{ $sel }} w-7">{{ $butir['nomor'] }}</td>
                        @endif
                        <td class="{{ $sel }} text-justify whitespace-pre-line">{{ $butir['isi'] }}</td>
                    </tr>
                @endforeach
            </table>
        @elseif ($b['tipe'] === 'diktum')
            <table class="mb-3 w-full border-collapse">
                @foreach ($b['butir'] as $butir)
                    <tr>
                        <td class="{{ $sel }} w-28">{{ $butir['nomor'] }}</td>
                        <td class="{{ $sel }} w-4">:</td>
                        <td class="{{ $sel }} text-justify whitespace-pre-line">{{ $butir['isi'] }}</td>
                    </tr>
                @endforeach
            </table>
        @elseif ($b['tipe'] === 'penutup')
            <div class="mt-8">
                <div class="ml-auto w-72">
                    @foreach ($b['ditetapkan'] as $baris)
                        <p class="min-h-6">{{ $baris }}</p>
                    @endforeach
                </div>
                @if ($b['diundangkan'])
                    <div class="mt-6 w-80">
                        @foreach ($b['diundangkan'] as $baris)
                            <p class="min-h-6">{{ $baris }}</p>
                        @endforeach
                    </div>
                @endif
                @if ($b['berita'])
                    <p class="mt-6">{{ $b['berita'] }}</p>
                @endif
            </div>
        @else
            @foreach ($b['baris'] as $baris)
                <div class="mb-1 flex gap-3 text-justify" style="margin-left: {{ $baris['tingkat'] * 1.75 }}rem">
                    @if ($baris['nomor'] !== null)
                        <span class="w-8 shrink-0">{{ $baris['nomor'] }}</span>
                    @endif
                    <span class="min-w-0">{{ $baris['isi'] }}</span>
                </div>
            @endforeach
        @endif
    @endforeach
</div>
