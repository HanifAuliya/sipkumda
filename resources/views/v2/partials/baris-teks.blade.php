{{-- Isi pasal atau diktum di layar: tabel, paragraf, serta ayat dan butir bernomor. --}}
@foreach ($daftar as $baris)
    @if (isset($baris['tabel']))
        <div class="overflow-x-auto">
            <table class="my-2 w-full border-collapse text-sm">
                @foreach ($baris['tabel'] as $kolom)
                    <tr>
                        @foreach ($kolom as $isi)
                            <td class="border border-black px-2 py-1 align-top">{{ $isi }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </div>
        @continue
    @endif
    {{-- Tiap tingkat bergeser selebar kolom nomor (2rem) + jarak (0,75rem), supaya butir sejajar dengan teks di atasnya. --}}
    <div class="mb-1 flex gap-3 {{ $rata }}" style="margin-left: {{ $baris['tingkat'] * 2.75 }}rem">
        @if ($baris['nomor'] !== null)
            <span class="w-8 shrink-0">{{ $baris['nomor'] }}</span>
        @endif
        <span class="min-w-0 flex-1">{{ $baris['isi'] }}</span>
    </div>
@endforeach
