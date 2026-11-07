<div>
    <x-ui.page-header :title="$info['judul']" :description="$info['keterangan']" :icon="$info['ikon']" :back="route('v2.beranda')">
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
                <x-ui.list-toolbar placeholder="Cari nomor atau tentang..." :options="[5, 10, 20, 50]" />

                <div class="space-y-4 transition-opacity duration-150" wire:loading.class="opacity-60">
                    @forelse ($halaman as $naskah)
                        <x-naskah.item :naskah="$naskah" wire:key="naskah-{{ $naskah->id }}" />
                    @empty
                        <x-ui.empty-state :icon="$info['ikon']" :title="$search !== '' ? 'Tidak ada naskah yang cocok dengan pencarian.' : 'Daftar “'.$info['tab'][$tabAktif === 'menunggu' ? 0 : 1][1].'” masih kosong.'" class="py-12">
                            <x-ui.reset-filter :aktif="$search !== ''" :nilai="['search' => '']" />
                            @if ($bisaBuat && $search === '' && $tabAktif === 'menunggu')
                                <x-ui.button icon="send-plus" size="rapat" x-on:click="openModal('ajukanNaskahModal')">Ajukan Rancangan Baru</x-ui.button>
                            @endif
                        </x-ui.empty-state>
                    @endforelse
                </div>
            </div>
        @else
            <x-ui.list-toolbar placeholder="Cari nomor atau tentang..." :options="[5, 10, 20, 50]">
                <x-form.select wire:model.live="tahap" icon="funnel" class="sm:w-56" aria-label="Tahap">
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
                    {{-- Seluruh baris bisa diklik untuk membuka detail. --}}
                    <tr wire:key="naskah-{{ $naskah->id }}" class="cursor-pointer"
                        x-on:click="$event.target.closest('a, button') || Livewire.dispatch('bukaDetailNaskah', { slug: {{ \Illuminate\Support\Js::from($naskah->slug) }} })">
                        <td class="sm:whitespace-nowrap">
                            <p class="sel-utama">{{ $naskah->nomor }}</p>
                            <div class="mt-1 flex flex-wrap gap-1.5">
                                <x-ui.status-badge :value="$naskah->jenis" />
                                <x-ui.badge :color="$naskah->tahap->warna()" class="sm:hidden">{{ $naskah->tahap->label() }}</x-ui.badge>
                            </div>
                            <p class="sel-judul mt-1.5 max-sm:line-clamp-3 sm:hidden">{{ $naskah->tentang }}</p>
                            <p class="sel-kedua mt-1 sm:hidden">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user }}</p>
                        </td>
                        <td class="min-w-64 max-sm:hidden"><p class="sel-judul" title="{{ $naskah->tentang }}">{{ $naskah->tentang }}</p></td>
                        <td class="min-w-44 max-sm:hidden"><p class="sel-judul">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user }}</p></td>
                        <td class="whitespace-nowrap max-sm:hidden"><x-ui.badge :color="$naskah->tahap->warna()">{{ $naskah->tahap->label() }}</x-ui.badge></td>
                        <td>
                            <div class="flex justify-end">
                                <x-naskah.detail-button :naskah="$naskah" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="5" :message="$search !== '' || $tahap !== '' ? 'Tidak ada naskah yang cocok dengan pencarian atau filter.' : 'Tidak ada naskah pada daftar ini.'">
                        <x-ui.reset-filter :aktif="$search !== '' || $tahap !== ''" :nilai="['search' => '', 'tahap' => '']" />
                    </x-ui.empty-row>
                @endforelse
            </x-ui.table>
        @endisset

        <div class="mt-5">{{ $halaman->links() }}</div>
    </x-ui.card>
</div>
