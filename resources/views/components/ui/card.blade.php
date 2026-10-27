{{--
    Kartu konten.
    <x-ui.card title="Judul" description="Sub judul">
        <x-slot:actions> tombol... </x-slot:actions>
        isi...
        <x-slot:footer> ... </x-slot:footer>
    </x-ui.card>
--}}
@props(['title' => null, 'description' => null, 'padding' => true])

<section {{ $attributes->class('rounded-2xl bg-white shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5') }}>
    @if ($title || isset($actions) || isset($header))
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 sm:px-6 dark:border-navy-800">
            @isset($header)
                {{ $header }}
            @else
                <div class="min-w-0">
                    <h3 class="text-base font-semibold">{{ $title }}</h3>
                    @if ($description)
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $description }}</p>
                    @endif
                </div>
            @endisset

            @isset($actions)
                <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
            @endisset
        </header>
    @endif

    <div @class(['p-5 sm:p-6' => $padding])>
        {{ $slot }}
    </div>

    @isset($footer)
        <footer class="border-t border-slate-100 px-5 py-4 sm:px-6 dark:border-navy-800">
            {{ $footer }}
        </footer>
    @endisset
</section>
