{{--
    Tabel di dalam rumusan pada layar, sama bentuknya dengan hasil cetak: rata kolom, baris judul rata tengah,
    dan judul kelompok (hanya sel pertama terisi) dibentangkan selebar tabel.
    $tabel: ['tabel' => baris sel, 'rata' => rata kolom, 'judul' => bool]. $garis: kelas warna garis sel.
--}}
<table class="w-full border-collapse text-sm">
    @foreach (App\Support\TataLetakNaskah::susunTabel($tabel) as $b)
        <tr>
            @foreach ($b['sel'] as $s)
                <td colspan="{{ $s['bentang'] }}" style="text-align: {{ $s['rata'] }}"
                    @class(['border px-2 py-1 align-top', $garis ?? 'border-black', 'font-semibold' => $s['tebal']])>{{ $s['isi'] }}</td>
            @endforeach
        </tr>
    @endforeach
</table>
