@section('title', 'Pilih Aplikasi')

@php
    use App\Support\Navigation;

    $pilihan = [
        [Navigation::LAMA, 'SIFKUMDA', 'Aplikasi saat ini', 'Alur yang berjalan sekarang: rancangan, matriks, dan fasilitasi diunggah sebagai berkas.', 'grid-1x2', 'slate'],
        [Navigation::V2, 'SIFKUMDA v2', 'Uji coba', 'Alur baru: matriks dan naskah dikerjakan langsung di aplikasi, tanpa unggah ulang berkas.', 'stars', 'primary'],
    ];
@endphp

<x-guest-layout>
    <x-auth.header title="Pilih aplikasi" subtitle="Selamat datang, {{ auth()->user()->nama_user }}. Akun dan data pengguna Anda sama di kedua aplikasi." />

    <div class="space-y-3">
        @foreach ($pilihan as [$kunci, $nama, $status, $keterangan, $ikon, $warna])
            <form method="POST" action="{{ route('aplikasi.simpan') }}">
                @csrf
                <input type="hidden" name="aplikasi" value="{{ $kunci }}">
                <button type="submit"
                    class="group flex w-full items-start gap-4 rounded-2xl p-4 text-left ring-1 ring-slate-200 transition hover:bg-slate-50 hover:ring-primary-400 focus-visible:ring-2 focus-visible:ring-primary-500 dark:ring-navy-700 dark:hover:bg-navy-800">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-linear-to-br from-primary-500 to-primary-700 text-lg text-white shadow-soft">
                        <i class="bi bi-{{ $ikon }}"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-navy-900 dark:text-white">{{ $nama }}</span>
                            <x-ui.badge :color="$warna">{{ $status }}</x-ui.badge>
                        </span>
                        <span class="mt-1 block text-sm leading-relaxed text-slate-500 dark:text-slate-400">{{ $keterangan }}</span>
                    </span>
                    <i class="bi bi-arrow-right mt-3 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-primary-500"></i>
                </button>
            </form>
        @endforeach
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-6 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold text-slate-500 hover:text-navy-900 dark:hover:text-white">Keluar</button>
    </form>
</x-guest-layout>
