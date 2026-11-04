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

                <div class="space-y-4">
                    @forelse ($halaman as $naskah)
                        <x-naskah.item :naskah="$naskah" wire:key="naskah-{{ $naskah->id }}" />
                    @empty
                        <x-ui.empty-state :icon="$info['ikon']" title="Tidak ada naskah pada daftar ini." class="py-12" />
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
                    <th>Nomor</th>
                    <th>Tentang</th>
                    <th>Jenis</th>
                    <th>Perangkat Daerah</th>
                    <th>Tahap</th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>

                @forelse ($halaman as $naskah)
                    <tr wire:key="naskah-{{ $naskah->id }}">
                        <td class="font-semibold whitespace-nowrap text-navy-900 dark:text-white">{{ $naskah->nomor }}</td>
                        <td class="min-w-56">{{ $naskah->tentang }}</td>
                        <td><x-ui.status-badge :value="$naskah->jenis" /></td>
                        <td class="min-w-40">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user }}</td>
                        <td class="whitespace-nowrap"><x-ui.badge :color="$naskah->tahap->warna()">{{ $naskah->tahap->label() }}</x-ui.badge></td>
                        <td>
                            <div class="flex justify-end">
                                <x-naskah.detail-button :naskah="$naskah" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="6" message="Tidak ada naskah pada daftar ini." />
                @endforelse
            </x-ui.table>
        @endisset

        <div class="mt-5">{{ $halaman->links() }}</div>
    </x-ui.card>
</div>
