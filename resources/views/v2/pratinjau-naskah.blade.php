<div x-effect="if (pratinjau && ! $wire.aktif) $wire.aktifkan()">
    @if ($aktif)
        @include('v2.partials.isi-naskah', ['blok' => $blok, 'ringkas' => true, 'tandaiUbahan' => $banding])
    @else
        <p class="py-12 text-center text-sm text-slate-500 dark:text-slate-400">Memuat pratinjau…</p>
    @endif
</div>
