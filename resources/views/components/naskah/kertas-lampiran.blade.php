{{--
    Selembar kertas lampiran: ukuran, tepi, dan huruf sama dengan PDF (FormatDokumen::gayaKertas), diperkecil bila layar
    lebih sempit. Kepala dan tanda tangan dibuat otomatis, jadi hanya ditampilkan; isinya di slot.
    Garis abu-abu tipis memperkirakan batas halaman (.kertas-lampiran di resources/css/app.css).

    <x-naskah.kertas-lampiran :kertas="$kertas">isi</x-naskah.kertas-lampiran>   $kertas dari BarisKerja::kertasLampiran()
--}}
@props(['kertas'])

<div x-data="{
        skala: 1,
        ukur() {
            const lebar = this.$el.clientWidth;
            if (lebar > 0) this.skala = Math.min(1, lebar / {{ $kertas['lebarPx'] }});
        },
    }"
    x-init="ukur(); new ResizeObserver(() => ukur()).observe($el)" {{ $attributes }}>
    <div x-bind:style="{ zoom: skala }" style="{{ $kertas['gaya'] }}" class="kertas-naskah kertas-lampiran mx-auto shadow-card">
        <div class="isi-kertas">
            <div class="kepala-lampiran otomatis" title="Dibuat otomatis dari judul naskah">
                @foreach ($kertas['kepala'] as $baris)
                    <p>{{ $baris }}</p>
                @endforeach
            </div>

            {{ $slot }}

            <div class="penutup ditetapkan otomatis" title="Dibuat otomatis dari bagian Penutup">
                @foreach ($kertas['jabatan'] as $baris)
                    <p class="jabatan">{{ $baris }}</p>
                @endforeach
            </div>
        </div>
    </div>
</div>
