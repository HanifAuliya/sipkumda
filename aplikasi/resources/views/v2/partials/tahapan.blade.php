{{-- Posisi naskah dalam alur. Butuh $tahap (App\Enums\TahapNaskah). --}}
@php $selesai = $tahap === App\Enums\TahapNaskah::Selesai; @endphp

<ol class="flex flex-wrap items-center gap-x-1 gap-y-1 text-sm" aria-label="Tahapan naskah">
    @foreach (App\Enums\TahapNaskah::LANGKAH as $i => $langkah)
        <li @class([
            'flex items-center gap-1.5 rounded-full px-2.5 py-1 font-semibold',
            'bg-brand-500/15 text-brand-600 dark:text-brand-500' => $i < $tahap->langkah() || $selesai,
            'bg-primary-500 text-white' => $i === $tahap->langkah() && ! $selesai,
            'text-slate-400' => $i > $tahap->langkah(),
        ]) @if ($i === $tahap->langkah()) aria-current="step" @endif>
            @if ($i < $tahap->langkah() || $selesai)<i class="bi bi-check2"></i>@endif
            {{ $langkah }}
        </li>
        @unless ($loop->last)
            <li aria-hidden="true" class="text-slate-300 dark:text-navy-600"><i class="bi bi-chevron-right text-xs"></i></li>
        @endunless
    @endforeach
</ol>
