{{-- Isi pasal, diktum, atau lampiran di layar: tabel, paragraf, serta ayat dan butir bernomor. --}}
@foreach ($daftar as $baris)
    @if (isset($baris['tabel']))
        {{-- Tabel menjorok sejajar dengan teks butir di atasnya. --}}
        <div class="overflow-x-auto" style="margin-left: {{ $baris['tingkat'] * 2.75 }}rem">
            <div class="my-2">@include('v2.partials.tabel-isi', ['tabel' => $baris])</div>
        </div>
        @continue
    @endif
    @if ($baris['tengah'] ?? false)
        <p class="mb-1 text-center">{{ $baris['isi'] }}</p>
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
