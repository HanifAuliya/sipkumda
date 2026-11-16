{{--
    Rancangan yang sedang dipegang seorang peneliti (BebanPeneliti::daftar()->naskahAktif).
    Variabel: $daftar, $bisaDibuka (klik membuka detail naskah; jangan dipakai di dalam modal lain).
--}}
@php $bisaDibuka ??= false; @endphp

@if ($daftar->isEmpty())
    <p class="py-1 text-sm text-slate-500 dark:text-slate-400">Belum memegang rancangan.</p>
@else
    <ul class="divide-y divide-slate-100 dark:divide-navy-800">
        @foreach ($daftar as $n)
            @php $menunggu = in_array($n->tahap, \App\Support\BebanPeneliti::MENUNGGU, true); @endphp
            <li wire:key="beban-{{ $n->id }}">
                <div @if ($bisaDibuka) role="button" tabindex="0" data-slug="{{ $n->slug }}" x-on:click="Livewire.dispatch('bukaDetailNaskah', { slug: $el.dataset.slug })" x-on:keydown.enter="$el.click()" @endif
                    class="flex w-full items-start gap-3 py-2 text-left {{ $bisaDibuka ? 'cursor-pointer rounded-lg px-1 hover:bg-slate-50 dark:hover:bg-navy-800/60' : '' }}">
                    <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $menunggu ? 'bg-orange-500' : 'bg-slate-300 dark:bg-navy-600' }}"
                        title="{{ $menunggu ? 'Menunggu dikerjakan peneliti' : 'Sedang di pihak lain' }}"></span>
                    <span class="min-w-0 flex-1">
                        <span class="line-clamp-2 text-sm font-medium text-navy-900 dark:text-white" title="{{ $n->tentang }}">{{ $n->tentang }}</span>
                        <span class="block truncate text-sm text-slate-500 dark:text-slate-400">
                            {{ $n->nomor_tampil }} · {{ $n->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $n->pemrakarsa->nama_user ?? '-' }}
                        </span>
                        <x-ui.badge :color="$n->tahap->warna()" class="mt-1">{{ $n->tahap->label() }}</x-ui.badge>
                    </span>
                </div>
            </li>
        @endforeach
    </ul>
@endif
