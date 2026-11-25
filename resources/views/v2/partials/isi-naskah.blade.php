{{--
    Bentuk naskah bila dicetak saat ini, digambar dengan aturan yang sama dengan PDF (pdf/v2/naskah) dan format
    dari menu Format Dokumen: kertas, tepi, huruf, rata kanan-kiri, dan lebar kolom dalam cm. Kertasnya selebar
    kertas asli, lalu diperkecil supaya muat di layar, jadi pemenggalan barisnya sama dengan PDF.
    Isinya dari TataLetakNaskah, atau dari BandingNaskah bila perubahannya ditandai ($tandaiUbahan).

    $blok susunan blok, $jenis jenis naskah, $ringkas untuk panel pratinjau, $tombolBaris tombol menuju baris di Matriks.
    CSS-nya di resources/css/app.css (.kertas-naskah).
--}}
@php
    $ringkas ??= false;
    $tandaiUbahan ??= false;
    $tombolBaris ??= false;
    $bisaUbah ??= false;

    ['f' => $f, 'blok' => $blok, 'paraf' => $paraf, 'spasi' => $spasi] = App\Services\CetakNaskah::tampilanLayar($jenis, $blok);

    ['gaya' => $gaya, 'lebarPx' => $lebarPx, 'ruangKop' => $ruangKop] = App\Support\FormatDokumen::gayaKertas($f, $spasi);
    $teksKop = $f['kop']['tampil'] ? trim((string) $f['kop']['teks']) : '';
    $tampilLambang = $f['kop']['tampil'] && $f['kop']['lebar_lambang'] > 0;

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

<div x-data="{
        skala: 1,
        ukur() {
            const lebar = this.$el.clientWidth;
            if (lebar > 0) this.skala = Math.min(1, lebar / {{ $lebarPx }});
        },
    }"
    x-init="ukur(); new ResizeObserver(() => ukur()).observe($el)">
    <div x-bind:style="{ zoom: skala }" style="{{ $gaya }}" @class([
        'kertas-naskah mx-auto shadow-card',
        'v2-diff v2-kertas' => $tandaiUbahan,
        'kertas-pratinjau rounded-lg' => $ringkas,
    ])>
        <div @class(['kop', 'ruang-kop' => $ruangKop > 0, 'kop-biasa' => $ruangKop <= 0])>
            @if ($tampilLambang)
                <div class="lambang"><img src="{{ asset('assets/img/garuda.png') }}" alt="Lambang Garuda Pancasila"></div>
            @endif
            @if ($teksKop !== '')
                {{ $teksKop }}
            @endif
        </div>

        @foreach ($blok as $b)
            @if ($b['tipe'] === 'tengah')
                <div class="tengah group relative" data-baris="{{ $b['id'] ?? '' }}">
                    @foreach ($b['baris'] as $baris)
                        <p>{{ $baris }}</p>
                    @endforeach
                    @if ($perluTombol($b['id'] ?? null))
                        @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                    @endif
                </div>
            @elseif ($b['tipe'] === 'daftar')
                <table class="daftar">
                    @foreach ($b['butir'] as $i => $butir)
                        @php $idButir = $b['ids'][$i] ?? $b['id'] ?? null; @endphp
                        <tr class="group relative" data-baris="{{ $idButir }}">
                            <td class="label">{{ $i === 0 ? $b['label'] : '' }}</td>
                            @unless ($b['rapat'])
                                <td class="titik">{{ $i === 0 ? ':' : '' }}</td>
                            @endunless
                            @if ($butir['nomor'])
                                <td class="nomor">{{ $butir['nomor'] }}</td>
                            @endif
                            <td class="isi">{{ $butir['isi'] }}@if ($perluTombol($idButir))@include('v2.partials.tombol-baris', ['id' => $idButir, 'diubah' => $b['diubahButir'][$i] ?? false])@endif</td>
                        </tr>
                    @endforeach
                </table>
            @elseif ($b['tipe'] === 'diktum')
                <table class="daftar">
                    <tr>
                        <td class="label">{{ $b['pembuka'] }}</td>
                        <td class="titik">{{ $b['rapat'] ? '' : ':' }}</td>
                        <td></td>
                    </tr>
                    @foreach ($b['butir'] as $i => $butir)
                        @php $idButir = $b['ids'][$i] ?? null; @endphp
                        <tr class="group relative" data-baris="{{ $idButir }}">
                            <td class="label">{{ $butir['nomor'] }}</td>
                            <td class="titik">:</td>
                            <td>
                                @foreach ($butir['baris'] as $baris)
                                    @include('pdf.v2.partials.baris-isi', ['baris' => $baris])
                                @endforeach
                                @if ($perluTombol($idButir))
                                    @include('v2.partials.tombol-baris', ['id' => $idButir, 'diubah' => $b['diubahButir'][$i] ?? false])
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </table>
            @elseif ($b['tipe'] === 'lampiran')
                {{-- Lampiran dimulai di halaman baru; kepalanya sejajar dengan blok tanda tangan. --}}
                <div class="halaman-baru" role="separator" aria-label="Halaman lampiran"></div>
                <div class="kepala-lampiran">
                    @foreach ($b['kepala'] as $baris)
                        <p>{{ $baris }}</p>
                    @endforeach
                </div>
            @elseif ($b['tipe'] === 'html')
                {{-- Lampiran dari editor; HTML-nya sudah dibersihkan saat disimpan (HtmlLampiran). --}}
                <div class="lampiran-html group relative" data-baris="{{ $b['id'] ?? '' }}">
                    {!! $b['html'] !!}
                    @if ($perluTombol($b['id'] ?? null))
                        @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                    @endif
                </div>
            @elseif ($b['tipe'] === 'berkas')
                {{-- Lampiran berupa PDF: ditampilkan apa adanya; saat dicetak digabung di belakang naskah. --}}
                <div class="halaman-baru" role="separator" aria-label="Halaman lampiran"></div>
                <div class="lampiran-berkas group relative" data-baris="{{ $b['id'] ?? '' }}">
                    @if ($b['berkas'])
                        @php $urlBerkas = route('naskah.berkas', [$b['berkas']->naskah, $b['berkas'], 'lihat' => 1]); @endphp
                        <p class="text-center font-bold">{{ $b['label'] }}</p>
                        <p class="mb-2 text-center" style="font-size: 10pt">(berkas PDF: {{ $b['berkas']->nama_asli }})</p>
                        <iframe src="{{ $urlBerkas }}#view=FitH" title="{{ $b['label'] }}" loading="lazy" style="width: 100%; height: 26cm; border: 1px solid #cbd5e1"></iframe>
                    @else
                        <p class="text-center">{{ $b['label'] }}: PDF lampiran belum diunggah.</p>
                    @endif
                    @if ($perluTombol($b['id'] ?? null))
                        @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                    @endif
                </div>
            @elseif ($b['tipe'] === 'ttd')
                <div class="penutup ditetapkan">
                    @foreach ($b['baris'] as $baris)
                        <p class="jabatan">{{ $baris }}</p>
                    @endforeach
                </div>
            @elseif ($b['tipe'] === 'penutup')
                <div class="group relative" data-baris="{{ $b['id'] ?? '' }}">
                    <div class="penutup">
                        <div class="ditetapkan">
                            @foreach ($b['ditetapkan'] as $baris)
                                <p @class(['jabatan' => ! App\Support\TataLetakNaskah::barisTempatTanggal($baris)])>{{ $baris }}</p>
                            @endforeach
                        </div>
                        @if ($b['diundangkan'])
                            <div class="diundangkan">
                                @foreach ($b['diundangkan'] as $baris)
                                    <p @class(['jabatan' => ! App\Support\TataLetakNaskah::barisTempatTanggal($baris)])>{{ $baris }}</p>
                                @endforeach
                            </div>
                        @endif
                        @if ($b['berita'])
                            <p class="berita">{{ $b['berita'] }}</p>
                        @endif
                        @if ($b['tembusan'])
                            <div class="tembusan">
                                @foreach ($b['tembusan'] as $baris)
                                    <p>{{ $baris }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    @if ($paraf)
                        <table class="paraf">
                            <tr><td class="jabatan-paraf judul-paraf">{{ $f['paraf']['judul'] }}</td><td class="kosong"></td></tr>
                            @foreach ($paraf as $jabatan)
                                <tr><td class="jabatan-paraf">{{ $jabatan }}</td><td class="kosong"></td></tr>
                            @endforeach
                        </table>
                    @endif

                    @if ($perluTombol($b['id'] ?? null))
                        @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                    @endif
                </div>
            @else
                <div class="group relative" data-baris="{{ $b['id'] ?? '' }}">
                    @foreach ($b['baris'] as $baris)
                        @include('pdf.v2.partials.baris-isi', ['baris' => $baris])
                    @endforeach
                    @if ($perluTombol($b['id'] ?? null))
                        @include('v2.partials.tombol-baris', ['id' => $b['id'], 'diubah' => $b['diubah'] ?? false])
                    @endif
                </div>
            @endif
        @endforeach
    </div>
</div>
