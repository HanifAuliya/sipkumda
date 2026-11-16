{{-- Satu baris tabel matriks; dipakai tabel utama dan tabel lanjutan yang menempel pada tanda tangan. --}}
<tr>
    <td class="kolom-no">{{ $baris['no'] }}</td>
    <td class="kolom-materi">{{ $baris['materi'] }}</td>
    <td class="kolom-rumusan">
        @if ($baris['penanda'] && ! $baris['sebaris'])
            <span class="penanda">{{ $baris['penanda'] }}</span><br>
        @endif
        @include('pdf.v2.partials.teks-matriks', ['teks' => $baris['rumusan'], 'penanda' => $baris['sebaris'] ? $baris['penanda'] : null])
    </td>
    <td class="kolom-sempurna">@include('pdf.v2.partials.teks-matriks', ['teks' => $baris['penyempurnaan'], 'penanda' => null])</td>
    <td class="kolom-ket"><div class="teks">{{ $baris['keterangan'] }}</div></td>
</tr>
