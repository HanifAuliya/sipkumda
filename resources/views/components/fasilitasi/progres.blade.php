{{-- Progres tahapan paraf versi ringkas untuk baris tabel. <x-fasilitasi.progres :fasilitasi="$f" /> --}}
@props(['fasilitasi'])

<ol {{ $attributes->class('grid grid-cols-4 gap-2') }}>
    @foreach ($fasilitasi->tahapanParaf() as [$label, $status, $tanggal])
        @php $selesai = $status === 'Selesai'; @endphp
        <li title="{{ $label }}: {{ $selesai ? 'Selesai ' . tanggal($tanggal) : 'Belum' }}">
            <span @class(['block h-1.5 rounded-full', 'bg-emerald-500' => $selesai, 'bg-slate-200 dark:bg-navy-700' => ! $selesai])></span>
            <span class="mt-1.5 block text-xs font-medium whitespace-nowrap text-slate-600 dark:text-slate-300">{{ str_replace('Paraf ', '', $label) }}</span>
            <span @class(['block text-[11px] whitespace-nowrap', 'text-emerald-600 dark:text-emerald-400' => $selesai, 'text-slate-400' => ! $selesai])>
                {{ $selesai ? tanggal($tanggal) : 'Belum' }}
            </span>
        </li>
    @endforeach
</ol>
