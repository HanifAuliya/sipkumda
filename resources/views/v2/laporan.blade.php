<div class="space-y-6">
    <x-ui.page-header title="Laporan" description="Rekap naskah yang sudah diajukan: jumlah, lama proses, dan naskah yang tertahan." icon="bar-chart">
        <x-slot:actions>
            <x-ui.button variant="outline" icon="file-earmark-spreadsheet" wire:click="unduhExcel" loading="unduhExcel">Unduh Excel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="flex flex-col gap-3 sm:flex-row">
        <x-form.select wire:model.live="tahun" icon="calendar3" class="sm:w-48" aria-label="Tahun pengajuan">
            <option value="">Semua tahun</option>
            @foreach ($daftarTahun as $t)
                <option value="{{ $t }}">{{ $t }}</option>
            @endforeach
        </x-form.select>
        <x-form.select wire:model.live="jenis" icon="tags" class="sm:w-56" aria-label="Jenis produk hukum">
            <option value="">Semua jenis</option>
            <option value="Peraturan Bupati">Peraturan Bupati</option>
            <option value="Keputusan Bupati">Keputusan Bupati</option>
        </x-form.select>
    </div>

    <div class="grid grid-cols-2 gap-3 transition-opacity sm:gap-5 xl:grid-cols-4" wire:loading.class="opacity-60">
        <x-ui.stat-card rapat label="Diajukan" :value="$ringkasan['diajukan']" suffix="naskah" icon="send">
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ringkasan['peraturan'] }} Perbup · {{ $ringkasan['keputusan'] }} Kepbup</p>
        </x-ui.stat-card>
        <x-ui.stat-card rapat label="Sedang berjalan" :value="$ringkasan['berjalan']" suffix="naskah" icon="hourglass-split" />
        <x-ui.stat-card rapat label="Selesai" :value="$ringkasan['selesai']" suffix="naskah" icon="check2-circle" />
        <x-ui.stat-card rapat label="Rata-rata lama proses" :value="$ringkasan['rata_proses'] ?? '-'" :suffix="$ringkasan['rata_proses'] !== null ? 'hari' : null" icon="clock-history">
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Dari diajukan sampai diarsipkan</p>
        </x-ui.stat-card>
    </div>

    <x-ui.card :title="'Tertahan lebih dari ' . $batas . ' hari'" description="Naskah yang belum berpindah tahap. Pemegang gilirannya diingatkan otomatis setiap {{ $batas }} hari.">
        <x-ui.table>
            <x-slot:head>
                <th>Naskah</th>
                <th class="max-sm:hidden">Tahap</th>
                <th class="max-sm:hidden">Sejak</th>
                <th class="text-right">Lama</th>
            </x-slot:head>
            @forelse ($tertahan as $b)
                <tr wire:key="tertahan-{{ $b['naskah']->id }}" class="cursor-pointer"
                    x-on:click="Livewire.dispatch('bukaDetailNaskah', { slug: {{ \Illuminate\Support\Js::from($b['naskah']->slug) }} })">
                    <td>
                        <p class="sel-utama">{{ $b['naskah']->nomor_tampil }}</p>
                        <p class="sel-judul mt-0.5">{{ $b['naskah']->tentang }}</p>
                        <x-ui.badge :color="$b['naskah']->tahap->warna()" class="mt-1 sm:hidden">{{ $b['naskah']->tahap->label() }}</x-ui.badge>
                    </td>
                    <td class="whitespace-nowrap max-sm:hidden">
                        <x-ui.badge :color="$b['naskah']->tahap->warna()">{{ $b['naskah']->tahap->label() }}</x-ui.badge>
                        <p class="sel-kedua mt-1">Giliran {{ $b['naskah']->tahap->peran() }}</p>
                    </td>
                    <td class="sel-kedua whitespace-nowrap max-sm:hidden">{{ \App\Support\LaporanNaskah::formatTanggal($b['masuk']) }}</td>
                    <td class="text-right font-semibold whitespace-nowrap text-orange-600 dark:text-orange-300">{{ $b['lama_tahap'] }} hari</td>
                </tr>
            @empty
                <x-ui.empty-row colspan="4" icon="check2-circle" message="Tidak ada naskah yang tertahan." />
            @endforelse
        </x-ui.table>
    </x-ui.card>

    <div class="grid items-start gap-6 xl:grid-cols-2">
        <x-ui.card title="Per Tahap" description="Jumlah naskah di tiap tahap sekarang, dan rata-rata lamanya naskah berada di tahap itu.">
            <x-ui.table>
                <x-slot:head>
                    <th>Tahap</th>
                    <th class="text-right">Sekarang</th>
                    <th class="text-right">Rata-rata</th>
                </x-slot:head>
                @foreach ($perTahap as $t)
                    <tr wire:key="tahap-{{ $t['tahap']->value }}">
                        <td>{{ $t['tahap']->label() }} <span class="sel-kedua">· {{ $t['tahap']->peran() }}</span></td>
                        <td class="text-right font-semibold">{{ $t['sekarang'] }}</td>
                        <td class="text-right whitespace-nowrap text-slate-500 dark:text-slate-400">@if ($t['rata_hari'] === null) - @elseif ($t['rata_hari'] < 1) &lt; 1 hari @else {{ str_replace('.', ',', $t['rata_hari']) }} hari @endif</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Per Perangkat Daerah">
            <x-ui.table>
                <x-slot:head>
                    <th>Perangkat Daerah</th>
                    <th class="text-right">Diajukan</th>
                    <th class="text-right">Berjalan</th>
                    <th class="text-right">Selesai</th>
                </x-slot:head>
                @forelse ($perPd as $pd)
                    <tr wire:key="pd-{{ $loop->index }}">
                        <td><p class="sel-judul">{{ $pd['nama'] }}</p></td>
                        <td class="text-right font-semibold">{{ $pd['diajukan'] }}</td>
                        <td class="text-right">{{ $pd['berjalan'] }}</td>
                        <td class="text-right">{{ $pd['selesai'] }}</td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="4" message="Belum ada naskah yang diajukan." />
                @endforelse
            </x-ui.table>
        </x-ui.card>
    </div>
</div>
