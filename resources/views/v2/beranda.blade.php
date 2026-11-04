@php
    $user = auth()->user();
    $pd = $user->hasRole('Perangkat Daerah');
@endphp

<div class="space-y-6">
    {{-- Sambutan --}}
    <section class="relative overflow-hidden rounded-2xl bg-linear-to-br from-navy-900 via-navy-800 to-primary-700 p-6 text-white shadow-card sm:p-8">
        <div class="pointer-events-none absolute -top-16 -right-10 size-64 rounded-full bg-white/5"></div>
        <div class="pointer-events-none absolute -right-24 -bottom-24 size-72 rounded-full bg-primary-400/10"></div>

        <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ asset('assets/img/theme/profile.png') }}" alt="" class="size-16 rounded-full ring-4 ring-white/15">
                <div class="min-w-0">
                    <p class="text-sm text-white/70">Selamat datang kembali,</p>
                    <h1 class="truncate text-xl font-bold text-white sm:text-2xl">{{ $user->nama_user }}</h1>
                    <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs">
                        <span class="rounded-full bg-white/15 px-2.5 py-0.5 font-semibold">{{ $user->getRoleNames()->first() ?? 'Tanpa Role' }}</span>
                        <span class="text-white/70">NIP {{ $user->NIP }}</span>
                        @if ($user->perangkatDaerah)
                            <span class="text-white/70">· {{ $user->perangkatDaerah->nama_perangkat_daerah }}</span>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </section>

    {{-- Statistik --}}
    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <x-ui.stat-card label="Rancangan" :value="$statistik['rancangan']" suffix="naskah" icon="clipboard2-data" color="rose"
            :href="route('v2.daftar', $pd ? 'rancanganku' : 'rancangan')" />
        <x-ui.stat-card label="Fasilitasi" :value="$statistik['fasilitasi']" suffix="naskah" icon="file-text" color="orange"
            :href="route('v2.daftar', $pd ? 'fasilitasiku' : 'fasilitasi')" />
        <x-ui.stat-card label="Penandatanganan" :value="$statistik['ttd']" suffix="naskah" icon="pen" color="emerald" />
        <x-ui.stat-card label="Selesai" :value="$statistik['selesai']" suffix="naskah" icon="folder2-open" color="sky" />
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
            <x-form.select wire:model.live="tahap" icon="funnel" class="sm:w-56" aria-label="Tahap">
                <option value="">Semua tahap</option>
                @foreach (App\Enums\TahapNaskah::cases() as $pilihan)
                    <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                @endforeach
            </x-form.select>
        </div>

        <x-ui.table>
            <x-slot:head>
                <th>Nomor</th>
                <th>Tentang</th>
                <th>Tahap</th>
                <th>Diperbarui</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($halaman as $naskah)
                <tr wire:key="naskah-{{ $naskah->id }}">
                    <td class="font-semibold whitespace-nowrap">{{ $naskah->nomor }}</td>
                    <td class="min-w-64 whitespace-normal">{{ $naskah->tentang }}</td>
                    <td class="whitespace-nowrap"><x-ui.badge :color="$naskah->tahap->warna()">{{ $naskah->tahap->label() }}</x-ui.badge></td>
                    <td class="whitespace-nowrap text-slate-500">{{ $naskah->updated_at->diffForHumans() }}</td>
                    <td>
                        <div class="flex justify-end">
                            <x-naskah.detail-button :naskah="$naskah" />
                        </div>
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="5" message="Belum ada naskah." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $halaman->links() }}</div>
    </x-ui.card>
</div>
