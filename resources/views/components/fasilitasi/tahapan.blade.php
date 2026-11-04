{{-- Progres tahapan paraf (koordinasi → asisten → sekda → bupati). <x-fasilitasi.tahapan :fasilitasi="$f" /> --}}
@props(['fasilitasi'])

<ol {{ $attributes->class('grid gap-3 sm:grid-cols-4') }}>
    @foreach ($fasilitasi->tahapanParaf() as $i => [$label, $status, $tanggal])
        @php $selesai = $status === 'Selesai'; @endphp
        <li @class([
            'relative rounded-xl p-3 ring-1',
            'bg-emerald-50 ring-emerald-200 dark:bg-emerald-500/10 dark:ring-emerald-500/30' => $selesai,
            'bg-slate-50 ring-slate-200 dark:bg-navy-950/40 dark:ring-navy-700' => ! $selesai,
        ])>
            <div class="flex items-center gap-2">
                <span @class([
                    'grid size-7 shrink-0 place-items-center rounded-full text-xs font-bold',
                    'bg-emerald-500 text-white' => $selesai,
                    'bg-slate-200 text-slate-500 dark:bg-navy-800 dark:text-slate-400' => ! $selesai,
                ])>
                    @if ($selesai)<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
                </span>
                <span class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $label }}</span>
            </div>
            <p @class(['mt-1.5 text-xs', 'text-emerald-700 dark:text-emerald-300' => $selesai, 'text-slate-500' => ! $selesai])>
                {{ $selesai ? tanggal($tanggal) : 'Belum' }}
            </p>
        </li>
    @endforeach
</ol>
