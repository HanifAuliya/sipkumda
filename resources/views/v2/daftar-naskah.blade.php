<div>
    <x-ui.page-header :title="$info['judul']" :description="$info['keterangan']" :icon="$info['ikon']" :back="route('dashboard')">
        @if ($bisaBuat)
            <x-slot:actions>
                <livewire:rancangan.bahan-penting-dropdown />
                <livewire:v2.buat-naskah />
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        @isset($info['tab'])
            <x-ui.tabs>
                @foreach (['menunggu', 'riwayat'] as $i => $tab)
                    <x-ui.tab :active="$tabAktif === $tab" :icon="$info['tab'][$i][0]" :count="$jumlah[$tab]" wire:click="pilihTab('{{ $tab }}')">
                        {{ $info['tab'][$i][1] }}
                    </x-ui.tab>
                @endforeach
            </x-ui.tabs>

            <div class="mt-5">
                <x-ui.list-toolbar placeholder="Cari nomor atau tentang..." :options="[5, 10, 20, 50]">
                    @include('v2.partials.filter-jenis')
                </x-ui.list-toolbar>

                <div class="space-y-4 transition-opacity duration-150" wire:loading.class="opacity-60">
                    @forelse ($halaman as $naskah)
                        <x-naskah.item :naskah="$naskah" wire:key="naskah-{{ $naskah->id }}" />
                    @empty
                        <x-ui.empty-state :icon="$info['ikon']" :title="$search !== '' || $jenis !== '' ? 'Tidak ada naskah yang cocok dengan pencarian atau filter.' : 'Daftar “'.$info['tab'][$tabAktif === 'menunggu' ? 0 : 1][1].'” masih kosong.'" class="py-12">
                            <x-ui.reset-filter :aktif="$search !== '' || $jenis !== ''" :nilai="['search' => '', 'jenis' => '']" />
                            @if ($bisaBuat && $search === '' && $jenis === '' && $tabAktif === 'menunggu')
                                <x-ui.button icon="send-plus" size="rapat" x-on:click="openModal('ajukanNaskahModal')">Ajukan Rancangan Baru</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    @endforelse
                </div>
            </div>
        @else
            <x-ui.list-toolbar placeholder="Cari nomor atau tentang..." :options="[5, 10, 20, 50]">
                @include('v2.partials.filter-jenis')
                <x-form.select wire:model.live="tahap" icon="funnel" class="sm:w-52" aria-label="Tahap">
                    <option value="">Semua tahap</option>
                    @foreach ($info['tahap'] as $pilihan)
                        <option value="{{ $pilihan->value }}">{{ $pilihan->label() }}</option>
                    @endforeach
                </x-form.select>
            </x-ui.list-toolbar>

            <x-ui.table>
                <x-slot:head>
                    <th><span class="sm:hidden">Naskah</span><span class="max-sm:hidden">Nomor</span></th>
                    <th class="max-sm:hidden">Tentang</th>
                    <th class="max-sm:hidden">Perangkat Daerah</th>
                    <th class="max-sm:hidden">Tahap</th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>

                @forelse ($halaman as $naskah)
                    @include('v2.partials.baris-naskah', ['kolomPd' => true, 'kolomWaktu' => false])
                @empty
                    <x-ui.empty-row colspan="5" :message="$search !== '' || $tahap !== '' || $jenis !== '' ? 'Tidak ada naskah yang cocok dengan pencarian atau filter.' : 'Tidak ada naskah pada daftar ini.'">
                        <x-ui.reset-filter :aktif="$search !== '' || $tahap !== '' || $jenis !== ''" :nilai="['search' => '', 'tahap' => '', 'jenis' => '']" />
                    </x-ui.empty-row>
                @endforelse
            </x-ui.table>
        @endisset

        <div class="mt-5">{{ $halaman->links() }}</div>
    </x-ui.card>
</div>
