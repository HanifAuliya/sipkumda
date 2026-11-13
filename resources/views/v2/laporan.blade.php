@php
    use App\Enums\TahapNaskah;
    use App\Support\KalimatRiwayat;
    use App\Support\LaporanNaskah;
    use Illuminate\Support\Js;

    $warnaIkon = [
        'primary' => 'bg-primary-50 text-primary-600 dark:bg-primary-500/15 dark:text-primary-300',
        'success' => 'bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300',
        'danger' => 'bg-rose-50 text-rose-600 dark:bg-rose-500/15 dark:text-rose-300',
        'slate' => 'bg-slate-100 text-slate-500 dark:bg-navy-800 dark:text-slate-400',
    ];
    $bukaDetail = fn ($naskah) => 'Livewire.dispatch(\'bukaDetailNaskah\', { slug: ' . Js::from($naskah->slug) . ' })';
@endphp

<div class="space-y-6">
    <x-ui.page-header title="Laporan & Pemantauan" icon="bar-chart"
        description="Rekap naskah, sebaran per Perangkat Daerah, dan catatan kegiatan semua naskah.">
        <x-slot:actions>
            <x-ui.button variant="outline" icon="file-earmark-spreadsheet" wire:click="unduhExcel" loading="unduhExcel"
                title="Daftar naskah sesuai filter yang sedang dipakai">Unduh Excel</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.tabs>
        <x-ui.tab :active="$tab === 'ringkasan'" icon="bar-chart" wire:click="$set('tab', 'ringkasan')">Ringkasan</x-ui.tab>
        <x-ui.tab :active="$tab === 'perangkat'" icon="building" wire:click="$set('tab', 'perangkat')"><span class="sm:hidden">Per PD</span><span class="max-sm:hidden">Per Perangkat Daerah</span></x-ui.tab>
        <x-ui.tab :active="$tab === 'aktivitas'" icon="activity" wire:click="$set('tab', 'aktivitas')">Aktivitas</x-ui.tab>
    </x-ui.tabs>

    {{-- Filter bersama; tab Aktivitas memakai rentang tanggal sebagai ganti tahun. --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap">
        @if ($tab !== 'aktivitas')
            <x-form.select wire:model.live="tahun" icon="calendar3" class="sm:w-44" aria-label="Tahun pengajuan">
                <option value="">Semua tahun</option>
                @foreach ($daftarTahun as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </x-form.select>
        @endif
        <x-form.select wire:model.live="jenis" icon="tags" class="sm:w-52" aria-label="Jenis produk hukum">
            <option value="">Semua jenis</option>
            <option value="Peraturan Bupati">Peraturan Bupati</option>
            <option value="Keputusan Bupati">Keputusan Bupati</option>
        </x-form.select>
        <x-form.select wire:model.live="pd" icon="building" class="sm:w-72" aria-label="Perangkat Daerah">
            <option value="">Semua Perangkat Daerah</option>
            @foreach ($daftarPd as $p)
                <option value="{{ $p->id }}">{{ $p->nama_perangkat_daerah }}</option>
            @endforeach
        </x-form.select>
        @if ($tab === 'aktivitas')
            <x-form.select wire:model.live="peran" icon="person-badge" class="sm:w-48" aria-label="Peran pelaku">
                <option value="">Semua peran</option>
                @foreach ($peranList as $p)
                    <option value="{{ $p }}">{{ $p }}</option>
                @endforeach
            </x-form.select>
            <div class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2 sm:flex">
                <x-form.input type="date" wire:model.live="dari" class="min-w-0 sm:w-40" aria-label="Dari tanggal" />
                <span class="text-sm text-slate-400">s.d.</span>
                <x-form.input type="date" wire:model.live="sampai" class="min-w-0 sm:w-40" aria-label="Sampai tanggal" />
            </div>
        @endif
    </div>

    @if ($tab === 'ringkasan')
        <div class="grid grid-cols-2 gap-3 transition-opacity sm:gap-5 xl:grid-cols-4" wire:loading.class="opacity-60">
            <x-ui.stat-card rapat label="Diajukan" :value="$ringkasan['diajukan']" suffix="naskah" icon="send">
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $ringkasan['peraturan'] }} Perbup · {{ $ringkasan['keputusan'] }} Kepbup</p>
            </x-ui.stat-card>
            <x-ui.stat-card rapat label="Sedang berjalan" :value="$ringkasan['berjalan']" suffix="naskah" icon="hourglass-split" color="orange" />
            <x-ui.stat-card rapat label="Selesai" :value="$ringkasan['selesai']" suffix="naskah" icon="check2-circle" color="emerald" />
            <x-ui.stat-card rapat label="Rata-rata lama proses" :value="$ringkasan['rata_proses'] ?? '-'" :suffix="$ringkasan['rata_proses'] !== null ? 'hari' : null" icon="clock-history" color="sky">
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
                    <tr wire:key="tertahan-{{ $b['naskah']->id }}" class="cursor-pointer" x-on:click="{{ $bukaDetail($b['naskah']) }}">
                        <td>
                            <p class="sel-utama">{{ $b['naskah']->nomor_tampil }}</p>
                            <p class="sel-judul mt-0.5">{{ $b['naskah']->tentang }}</p>
                            <x-ui.badge :color="$b['naskah']->tahap->warna()" class="mt-1 sm:hidden">{{ $b['naskah']->tahap->label() }}</x-ui.badge>
                        </td>
                        <td class="whitespace-nowrap max-sm:hidden">
                            <x-ui.badge :color="$b['naskah']->tahap->warna()">{{ $b['naskah']->tahap->label() }}</x-ui.badge>
                            <p class="sel-kedua mt-1">Giliran {{ $b['naskah']->tahap->peran() }}</p>
                        </td>
                        <td class="sel-kedua whitespace-nowrap max-sm:hidden">{{ LaporanNaskah::formatTanggal($b['masuk']) }}</td>
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
                <x-slot:actions>
                    <x-ui.button variant="ghost" size="sm" icon="arrow-right" wire:click="$set('tab', 'perangkat')">Lihat sebaran</x-ui.button>
                </x-slot:actions>
                <x-ui.table>
                    <x-slot:head>
                        <th>Perangkat Daerah</th>
                        <th class="text-right">Diajukan</th>
                        <th class="text-right">Berjalan</th>
                        <th class="text-right">Selesai</th>
                    </x-slot:head>
                    @forelse ($perPd as $p)
                        <tr wire:key="pd-{{ $loop->index }}">
                            <td><p class="sel-judul">{{ $p['nama'] }}</p></td>
                            <td class="text-right font-semibold">{{ $p['diajukan'] }}</td>
                            <td class="text-right">{{ $p['berjalan'] }}</td>
                            <td class="text-right">{{ $p['selesai'] }}</td>
                        </tr>
                    @empty
                        <x-ui.empty-row colspan="4" message="Belum ada naskah yang diajukan." />
                    @endforelse
                </x-ui.table>
            </x-ui.card>
        </div>
    @elseif ($tab === 'perangkat')
        <x-ui.card title="Sebaran per Perangkat Daerah"
            description="Jumlah naskah tiap Perangkat Daerah menurut langkahnya sekarang. Tekan barisnya untuk melihat naskahnya.">
            <x-slot:actions>
                @unless ($pd)
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                        <input type="checkbox" wire:model.live="semuaPd" class="rounded text-primary-600 focus:ring-primary-300">
                        Tampilkan yang belum mengajukan
                    </label>
                @endunless
            </x-slot:actions>

            <x-ui.table x-data="{ buka: null }">
                <x-slot:head>
                    <th>Perangkat Daerah</th>
                    @foreach (TahapNaskah::LANGKAH as $langkah)
                        <th class="text-center whitespace-nowrap">{{ $langkah }}</th>
                    @endforeach
                    <th class="text-right">Total</th>
                </x-slot:head>
                @forelse ($matriks as $i => $baris)
                    <tr wire:key="sebaran-{{ md5($baris['nama']) }}" @class(['cursor-pointer' => $baris['total'] > 0])
                        @if ($baris['total'] > 0) x-on:click="buka = buka === {{ $i }} ? null : {{ $i }}" @endif>
                        <td class="min-w-56">
                            <p class="sel-judul flex items-center gap-2">
                                @if ($baris['total'] > 0)
                                    <i class="bi bi-chevron-right text-xs text-slate-400 transition-transform" x-bind:class="buka === {{ $i }} && 'rotate-90'" aria-hidden="true"></i>
                                @else
                                    <span class="w-3"></span>
                                @endif
                                {{ $baris['nama'] }}
                            </p>
                        </td>
                        @foreach ($baris['jumlah'] as $j)
                            <td class="text-center {{ $j ? 'font-semibold text-navy-900 dark:text-white' : 'text-slate-300 dark:text-navy-600' }}">{{ $j ?: '–' }}</td>
                        @endforeach
                        <td class="text-right font-bold">{{ $baris['total'] }}</td>
                    </tr>
                    @if ($baris['total'] > 0)
                        <tr wire:key="sebaran-isi-{{ md5($baris['nama']) }}" x-show="buka === {{ $i }}" x-cloak>
                            <td colspan="{{ count(TahapNaskah::LANGKAH) + 2 }}" class="bg-slate-50/70 dark:bg-navy-950/40">
                                <ul class="divide-y divide-slate-100 dark:divide-navy-800">
                                    @foreach ($baris['naskah'] as $n)
                                        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 py-2.5">
                                            <span class="font-semibold text-navy-900 dark:text-white">{{ $n->nomor_tampil }}</span>
                                            <x-ui.status-badge :value="$n->jenis" />
                                            <span class="min-w-0 flex-1 text-slate-600 dark:text-slate-300">{{ $n->tentang }}</span>
                                            <x-ui.badge :color="$n->tahap->warna()">{{ $n->tahap->label() }}</x-ui.badge>
                                            <x-ui.button variant="outline" size="sm" icon="eye" x-on:click.stop="{{ $bukaDetail($n) }}">Detail</x-ui.button>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @endif
                @empty
                    <x-ui.empty-row :colspan="count(TahapNaskah::LANGKAH) + 2" icon="building" message="Belum ada naskah yang diajukan dengan filter ini." />
                @endforelse
            </x-ui.table>
        </x-ui.card>
    @else
        <x-ui.card title="Aktivitas" description="Catatan kegiatan semua naskah, terbaru di atas.">
            <x-slot:actions>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
                    <input type="checkbox" wire:model.live="rumusan" class="rounded text-primary-600 focus:ring-primary-300">
                    Termasuk perubahan rumusan
                </label>
            </x-slot:actions>

            <ol class="divide-y divide-slate-100 transition-opacity dark:divide-navy-800" wire:loading.class="opacity-60">
                @forelse ($aktivitas as $r)
                    @php $k = KalimatRiwayat::untuk($r); @endphp
                    <li wire:key="aktivitas-{{ $r->id }}" class="flex gap-3 py-3.5 first:pt-0">
                        <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $warnaIkon[$k['warna']] }}">
                            <i class="bi bi-{{ $k['ikon'] }}" aria-hidden="true"></i>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5">
                                <p class="text-sm text-slate-700 dark:text-slate-200">
                                    <strong class="font-semibold text-navy-900 dark:text-white">{{ $r->user->nama_user ?? 'Sistem' }}</strong>
                                    @if ($r->user?->getRoleNames()->first())
                                        <span class="text-slate-400">({{ $r->user->getRoleNames()->first() }})</span>
                                    @endif
                                    {{ $k['teks'] }}
                                </p>
                                <time class="text-xs whitespace-nowrap text-slate-400" datetime="{{ $r->created_at->toIso8601String() }}">{{ $r->created_at->translatedFormat('d M Y, H.i') }}</time>
                            </div>
                            @if ($r->naskah)
                                <button type="button" class="mt-1 flex max-w-full items-center gap-2 text-left text-sm text-slate-500 transition hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-300"
                                    x-on:click="{{ $bukaDetail($r->naskah) }}">
                                    <span class="font-semibold whitespace-nowrap">{{ $r->naskah->nomor_tampil }}</span>
                                    <span class="truncate">{{ $r->naskah->tentang }}</span>
                                    <span class="whitespace-nowrap max-md:hidden">· {{ $r->naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? '-' }}</span>
                                </button>
                            @endif
                            @if ($r->catatan && ! in_array($r->aksi, ['tahapan_tanggal'], true))
                                <p class="mt-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600 dark:bg-navy-800 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($r->catatan, 240) }}</p>
                            @endif
                        </div>
                    </li>
                @empty
                    <li><x-ui.empty-state icon="activity" title="Belum ada aktivitas dengan filter ini." /></li>
                @endforelse
            </ol>

            <div class="mt-5">{{ $aktivitas->links() }}</div>
        </x-ui.card>
    @endif
</div>
