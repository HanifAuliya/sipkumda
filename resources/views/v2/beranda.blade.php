@php
    $user = auth()->user();
    $pd = $user->hasRole('Perangkat Daerah');
@endphp

<div class="space-y-6">
    @if (session('pesan'))
        <x-ui.alert type="info" dismissible>{{ session('pesan') }}</x-ui.alert>
    @endif

    {{-- Sambutan: sederhana, supaya mata langsung ke pekerjaan di bawahnya. --}}
    <section class="flex items-center gap-4 rounded-2xl bg-white p-5 shadow-card sm:p-6 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
        <img src="{{ $user->fotoUrl() }}" alt="" class="size-12 shrink-0 rounded-full object-cover sm:size-14">
        <div class="min-w-0">
            <p class="text-sm text-slate-500 dark:text-slate-400">Selamat datang kembali,</p>
            <h1 class="truncate text-xl font-bold text-navy-900 sm:text-2xl dark:text-white">{{ $user->nama_user }}</h1>
            <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $user->getRoleNames()->first() ?? 'Tanpa Role' }}</span>
                <span class="max-sm:hidden" aria-hidden="true">·</span>
                <span class="max-sm:hidden">NIP {{ $user->NIP }}</span>
                @if ($user->perangkatDaerah)
                    <span aria-hidden="true">·</span>
                    <span>{{ $user->perangkatDaerah->nama_perangkat_daerah }}</span>
                @endif
            </p>
        </div>
    </section>

    {{-- Statistik --}}
    <div class="grid grid-cols-2 gap-3 sm:gap-5 xl:grid-cols-4">
        <x-ui.stat-card rapat label="Rancangan" :value="$statistik['rancangan']" suffix="naskah" icon="clipboard2-data" color="rose"
            :href="route('daftar', $pd ? 'rancanganku' : 'rancangan')" wire:navigate />
        <x-ui.stat-card rapat label="Fasilitasi" :value="$statistik['fasilitasi']" suffix="naskah" icon="file-text" color="orange"
            :href="route('daftar', $pd ? 'fasilitasiku' : 'fasilitasi')" wire:navigate />
        @if ($user->hasAnyRole(['Admin', 'Tamu']))
            <x-ui.stat-card rapat label="Tanda Tangan & Arsip" :value="$statistik['ttd']" suffix="naskah" icon="pen" color="emerald" :href="route('daftar', 'penandatanganan')" wire:navigate />
        @else
            <x-ui.stat-card rapat label="Tanda Tangan & Arsip" :value="$statistik['ttd']" suffix="naskah" icon="pen" color="emerald" />
        @endif
        <x-ui.stat-card rapat label="Selesai" :value="$statistik['selesai']" suffix="naskah" icon="folder2-open" color="sky" />
    </div>

    {{-- Giliran --}}
    <x-ui.card title="Giliran Anda" description="Naskah yang sedang menunggu tindakan Anda">
        <x-slot:actions>
            <x-ui.badge color="primary">{{ $giliranSaya->count() }} naskah</x-ui.badge>
        </x-slot:actions>

        @if ($giliranSaya->isEmpty())
            <x-ui.empty-state icon="check2-circle" title="Tidak ada naskah yang menunggu Anda." />
        @else
            <div class="space-y-4">
                @foreach ($giliranSaya as $naskah)
                    <x-naskah.item :naskah="$naskah" wire:key="giliran-{{ $naskah->id }}" />
                @endforeach
            </div>
        @endif
    </x-ui.card>

    {{-- Semua naskah --}}
    <x-ui.card title="Semua Naskah" description="Seluruh naskah yang bisa Anda lihat">
        <div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center">
            <x-form.input wire:model.live.debounce.400ms="cari" icon="search" class="sm:flex-1" placeholder="Cari nomor atau tentang..." aria-label="Cari naskah" />
            @include('v2.partials.filter-jenis')
            <x-form.select wire:model.live="tahap" icon="funnel" class="sm:w-52" aria-label="Tahap">
                <option value="">Semua tahap</option>
                @foreach (App\Enums\TahapNaskah::cases() as $pilihan)
                    <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                @endforeach
            </x-form.select>
        </div>

        <x-ui.table>
            <x-slot:head>
                <th><span class="sm:hidden">Naskah</span><span class="max-sm:hidden">Nomor</span></th>
                <th class="max-sm:hidden">Tentang</th>
                <th class="max-sm:hidden">Tahap</th>
                <th class="max-lg:hidden">Diperbarui</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($halaman as $naskah)
                @include('v2.partials.baris-naskah', ['kolomPd' => false, 'kolomWaktu' => true])
            @empty
                <x-ui.empty-row colspan="5" :message="$cari !== '' || $tahap !== '' || $jenis !== '' ? 'Tidak ada naskah yang cocok dengan pencarian atau filter.' : 'Belum ada naskah.'">
                    <x-ui.reset-filter :aktif="$cari !== '' || $tahap !== '' || $jenis !== ''" :nilai="['cari' => '', 'tahap' => '', 'jenis' => '']" />
                </x-ui.empty-row>
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $halaman->links() }}</div>
    </x-ui.card>
</div>
