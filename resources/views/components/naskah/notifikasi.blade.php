{{--
    Satu baris notifikasi naskah, dipakai lonceng dan halaman Notifikasi.
    Komponen induknya wajib punya aksi buka($id).
    <x-naskah.notifikasi :notifikasi="$n" />
--}}
@props(['notifikasi'])

@php
    use App\Support\PesanNotifikasi as P;

    $data = $notifikasi->data;
    $dibaca = (bool) $notifikasi->read_at;

    // Notifikasi lama belum punya kategori, jadi tampil sebagai kabar biasa.
    [$ikon, $warna, $sebutan] = match ($data['kategori'] ?? P::KABAR) {
        P::TUGAS => ['pencil-square', 'bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300', 'Perlu tindakan'],
        P::KEMBALI => ['arrow-return-left', 'bg-orange-50 text-orange-600 dark:bg-orange-500/15 dark:text-orange-300', 'Dikembalikan'],
        P::SELESAI => ['check-lg', 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300', 'Selesai'],
        default => ['info-lg', 'bg-slate-100 text-slate-500 dark:bg-white/10 dark:text-slate-300', 'Kabar'],
    };
@endphp

<button type="button" wire:click="buka('{{ $notifikasi->id }}')" {{ $attributes->class([
    'flex w-full items-start gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition hover:bg-slate-100 dark:hover:bg-navy-700/60',
    'bg-primary-50/60 dark:bg-primary-500/10' => ! $dibaca,
]) }}>
    <span class="grid size-9 shrink-0 place-items-center rounded-full text-base {{ $warna }}" title="{{ $sebutan }}">
        <i class="bi bi-{{ $ikon }}" aria-hidden="true"></i>
        <span class="sr-only">{{ $sebutan }}</span>
    </span>

    <span class="min-w-0 flex-1">
        <span @class([
            'block',
            'font-semibold text-navy-900 dark:text-white' => ! $dibaca,
            'font-medium text-slate-700 dark:text-slate-200' => $dibaca,
        ])>{{ $data['title'] ?? 'Notifikasi' }}</span>
        <span class="mt-0.5 line-clamp-2 block text-slate-600 dark:text-slate-300">{{ $data['message'] ?? '' }}</span>
        @if (filled($data['catatan'] ?? null))
            <span class="mt-1 line-clamp-2 block rounded-md bg-orange-50 px-2 py-1 text-orange-800 dark:bg-orange-500/10 dark:text-orange-200">Catatan: {{ $data['catatan'] }}</span>
        @endif
        <span class="mt-1 block text-slate-500 dark:text-slate-400">
            @if (filled($data['pelaku'] ?? null))
                {{ $data['pelaku'] }} ·
            @endif
            {{ $notifikasi->created_at->diffForHumans() }}
        </span>
    </span>

    @unless ($dibaca)
        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary-500" title="Belum dibaca"></span>
    @endunless
</button>
