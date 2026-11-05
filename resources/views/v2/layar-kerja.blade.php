@php
    use App\Enums\TahapNaskah;
    use App\Models\NaskahBaris as B;
    use App\Models\NaskahBerkas;
    use App\Services\AlurNaskah as A;
    use App\Support\TemplateNaskah;

    $tahap = $naskah->tahap;
    $persen = $total ? (int) round($diperiksa / $total * 100) : 0;
    $titik = [
        B::BELUM => 'bg-slate-300 dark:bg-navy-600',
        B::TETAP => 'bg-emerald-500',
        B::DIUBAH => 'bg-primary-500',
        B::DIHAPUS => 'bg-rose-500',
        B::PERLU_PD => 'bg-orange-500',
        B::DISESUAIKAN => 'bg-cyan-500',
    ];
    $pilihanFilter = [
        'semua' => ['Semua baris', $total],
        'belum' => ['Belum diperiksa', $hitung[B::BELUM] ?? 0],
        'perlu' => ['Perlu perbaikan PD', $hitung[B::PERLU_PD] ?? 0],
        'diubah' => ['Yang diubah', ($hitung[B::DIUBAH] ?? 0) + ($hitung[B::DIHAPUS] ?? 0) + ($hitung[B::PERLU_PD] ?? 0) + ($hitung[B::DISESUAIKAN] ?? 0)],
        'berubah' => ['Berubah sejak giliran lalu', null],
    ];
    $tabs = ['kerja' => ['table', 'Matriks'], 'naskah' => ['file-earmark-text', 'Naskah'], 'berkas' => ['paperclip', 'Berkas'], 'riwayat' => ['clock-history', 'Riwayat']];
    if (! $bisaUnduh) {
        unset($tabs['berkas']);
    }
    $aksiModal = $aksiTerpilih ? ($aksi[$aksiTerpilih] ?? null) : null;
@endphp

{{-- Bagian awal ada di atribut sendiri: x-data ikut tertimpa saat render ulang, sehingga perpindahan bagian tidak terdeteksi. --}}
<div class="flex h-dvh flex-col" data-sorot-awal="{{ json_encode($blokAktif['ids'] ?? []) }}"
    x-data="{
        pratinjau: false,
        bandingPratinjau: false,
        sorot: [],
        fokus: null,
        init() {
            this.sorot = JSON.parse(this.$el.dataset.sorotAwal);
            let tersimpan = null;
            try { tersimpan = localStorage.getItem('v2-pratinjau'); } catch (e) {}
            this.pratinjau = tersimpan !== null ? tersimpan === '1' : window.matchMedia('(min-width: 1280px)').matches;
            this.$watch('pratinjau', (buka) => {
                try { localStorage.setItem('v2-pratinjau', buka ? '1' : '0'); } catch (e) {}
                if (buka) this.$nextTick(() => this.gulir());
            });
        },
        get gaya() {
            const pilih = (ids) => ids.map((id) => `.kertas-pratinjau [data-baris='${id}']`).join(',');
            return (this.sorot.length ? `${pilih(this.sorot)}{background:rgb(94 114 228 / .1)}` : '')
                + (this.fokus ? `${pilih([this.fokus])}{background:rgb(94 114 228 / .26)}` : '');
        },
        gulir() {
            const panel = this.$refs.pratinjauGulir;
            const tujuan = [this.fokus, ...this.sorot].map((id) => panel.querySelector(`[data-baris='${id}']`)).find(Boolean);
            if (tujuan) panel.scrollTo({ top: panel.scrollTop + tujuan.getBoundingClientRect().top - panel.getBoundingClientRect().top - 16, behavior: 'smooth' });
        },
        aturSorot(ids) {
            const sama = ids.join() === this.sorot.join();
            this.sorot = ids;
            if (! sama) this.fokus = null;
            if (! sama || this.$refs.pratinjauGulir.scrollTop === 0) this.$nextTick(() => this.gulir());
        },
        aturFokus(id) {
            if (id !== this.fokus) { this.fokus = id; this.gulir(); }
        },
        bukaBaris(id) {
            setTimeout(() => {
                const baris = document.getElementById('baris-' + id);
                baris?.scrollIntoView({ block: 'start' });
                baris?.dispatchEvent(new CustomEvent('buka-editor'));
                this.aturFokus(id);
            }, 120);
        },
    }"
    x-on:pratinjau-sorot.window="aturSorot($event.detail.ids)"
    x-on:pratinjau-fokus.window="aturFokus($event.detail.id)"
    x-on:pratinjau-dimuat.window="setTimeout(() => gulir(), 60)"
    x-on:fokus-baris.window="bukaBaris($event.detail.id)">
    {{-- Header: posisi naskah seperti jalur, dengan badge jenis dokumen. Bagian yang kurang penting disembunyikan saat layar sempit. --}}
    <header class="relative z-30 flex h-14 shrink-0 items-center gap-2.5 border-b border-slate-200 bg-white px-4 text-slate-600 sm:px-5 dark:border-navy-800 dark:bg-navy-900 dark:text-slate-300">
        <button type="button" class="-ml-1 grid size-10 shrink-0 place-items-center rounded-lg text-current/80 transition hover:bg-current/10 hover:text-current lg:hidden"
            x-on:click="sidebarOpen = true" aria-label="Buka daftar bagian">
            <i class="bi bi-list text-2xl" aria-hidden="true"></i>
        </button>

        <a href="{{ route('v2.beranda') }}" class="hidden size-9 shrink-0 place-items-center rounded-lg transition hover:bg-slate-100 sm:grid dark:hover:bg-navy-800" aria-label="Dashboard" title="Dashboard">
            <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="" class="size-7">
        </a>

        <div class="flex min-w-0 flex-1 items-center gap-2.5 text-sm">
            <span class="hidden text-slate-300 md:inline dark:text-navy-600" aria-hidden="true">/</span>
            <span class="hidden shrink-0 font-medium md:inline">{{ $naskah->nomor }}</span>
            <span class="hidden text-slate-300 sm:inline dark:text-navy-600" aria-hidden="true">/</span>
            <h1 class="hidden min-w-0 truncate font-semibold text-navy-900 sm:block dark:text-white" title="{{ $naskah->tentang }}">{{ $naskah->tentang }}</h1>
            <span @class([
                'shrink-0 rounded-full px-3 py-1 text-sm font-semibold whitespace-nowrap text-white',
                'bg-accent-500' => $naskah->jenis === 'Keputusan Bupati',
                'bg-primary-500' => $naskah->jenis !== 'Keputusan Bupati',
            ])>{{ $naskah->jenis }}</span>
            <span class="hidden text-slate-300 lg:inline dark:text-navy-600" aria-hidden="true">/</span>
            <span class="hidden shrink-0 font-medium lg:inline">{{ $tahap->label() }}</span>
            @if ($naskah->diajukan_at)
                <span class="hidden shrink-0 rounded-full border border-slate-200 px-2.5 py-0.5 whitespace-nowrap xl:inline dark:border-navy-700" title="Tanggal pengajuan">{{ tanggal($naskah->diajukan_at) }}</span>
            @endif
        </div>

        <div class="ml-auto flex shrink-0 items-center gap-1">
            @include('layouts.partials.menu-pengguna', ['modeV2' => true])
        </div>
    </header>

    <div class="flex min-h-0 flex-1">
    {{-- Latar gelap saat panel terbuka (mobile) --}}
    <div x-show="sidebarOpen" x-cloak x-transition.opacity x-on:click="sidebarOpen = false"
        class="fixed inset-0 z-40 bg-navy-950/50 backdrop-blur-[2px] lg:hidden"></div>

    {{-- Panel kiri: bagian naskah --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-80 max-w-[85vw] shrink-0 -translate-x-full flex-col bg-white shadow-xl transition-transform duration-200 lg:static lg:z-auto lg:w-72 lg:translate-x-0 lg:shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5"
        x-bind:class="sidebarOpen && 'translate-x-0'" aria-label="Bagian naskah">
        <div class="space-y-3 border-b border-slate-100 p-4 dark:border-navy-800">
            <x-ui.button :href="$kembali" variant="outline" size="rapat" icon="arrow-left" class="w-full">Kembali ke Daftar</x-ui.button>

            {{-- Di layar lebar keterangan ini ada di header. --}}
            <div class="lg:hidden">
                <p class="flex flex-wrap items-center gap-2 text-sm font-semibold text-slate-500 dark:text-slate-400">
                    {{ $naskah->nomor }}
                    <x-ui.badge :color="$tahap->warna()">{{ $tahap->label() }}</x-ui.badge>
                </p>
                <p class="mt-1 line-clamp-2 text-sm font-bold text-navy-900 dark:text-white">{{ $naskah->tentang }}</p>
            </div>

            @if ($mode === A::MODE_SESUAIKAN)
                <div class="flex items-baseline justify-between gap-2 text-sm">
                    <span class="font-semibold text-navy-900 dark:text-white">Perlu diperbaiki</span>
                    <span class="text-slate-500 dark:text-slate-400">{{ $hitung[B::PERLU_PD] ?? 0 }} baris</span>
                </div>
            @elseif ($tahap !== TahapNaskah::Draf)
                <div>
                    <div class="flex items-baseline justify-between gap-2 text-sm">
                        <span class="font-semibold text-navy-900 dark:text-white">Baris diperiksa</span>
                        <span class="text-slate-500 dark:text-slate-400">{{ $diperiksa }} dari {{ $total }}</span>
                    </div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100" aria-label="Baris diperiksa">
                        <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $persen }}%"></div>
                    </div>
                </div>
            @endif
        </div>

        <nav class="tanpa-scrollbar flex-1 space-y-1 overflow-y-auto p-3" aria-label="Daftar bagian"
            x-on:click="
                const tautan = $event.target.closest('a[href^=\'#baris-\']');
                if (tautan && ! document.querySelector(tautan.getAttribute('href'))) {
                    $event.preventDefault();
                    $wire.lompatKe(tautan.getAttribute('href').replace('#baris-', ''))
                        .then(() => setTimeout(() => document.querySelector(tautan.getAttribute('href'))?.scrollIntoView(), 50));
                }
            ">
            @foreach ($daftarBlok as $b)
                @php $aktif = $blokAktif['kunci'] === $b['kunci'] && $tab === 'kerja'; @endphp

                @if ($b['anak'] && ! ($daftarBlok->get($loop->index - 1)['anak'] ?? false))
                    <p class="px-3 pt-4 pb-1.5 text-sm font-bold text-slate-500 dark:text-slate-400">Batang Tubuh</p>
                @endif

                <button type="button" wire:click="pilihBlok('{{ $b['kunci'] }}')" x-on:click="sidebarOpen = false" wire:key="blok-{{ $b['kunci'] }}"
                    @if ($aktif) aria-current="step" @endif
                    @class([
                        'flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition',
                        'bg-primary-500 text-white shadow-soft' => $aktif,
                        'text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-navy-800' => ! $aktif,
                    ])>
                    <span class="min-w-0 flex-1">
                        <span class="block text-sm font-semibold">{{ $b['nama'] }}</span>
                        @if ($b['sub'])
                            <span @class(['mt-0.5 line-clamp-2 block text-sm', 'text-white/85' => $aktif, 'text-slate-500 dark:text-slate-400' => ! $aktif])>{{ $b['sub'] }}</span>
                        @endif
                    </span>
                    @if ($b['keadaan'] === 'perlu')
                        <i @class(['bi bi-exclamation-circle-fill', 'text-white' => $aktif, 'text-rose-500' => ! $aktif]) title="Ada baris yang perlu diperbaiki"></i>
                    @elseif ($b['keadaan'] === 'selesai')
                        <i @class(['bi bi-check-circle-fill', 'text-white' => $aktif, 'text-emerald-500' => ! $aktif]) title="Selesai"></i>
                    @else
                        <i @class(['bi bi-circle', 'text-white/70' => $aktif, 'text-slate-300 dark:text-navy-600' => ! $aktif]) title="Belum selesai"></i>
                    @endif
                </button>

                @if ($aktif && $b['bagian'] === 'batang_tubuh')
                    <ul class="ml-4 space-y-0.5 border-l-2 border-slate-100 py-1 pl-2 dark:border-navy-800">
                        @foreach ($b['ids'] as $id)
                            @continue(str_starts_with((string) $perId[$id]->label, 'BAB'))
                            <li wire:key="anak-{{ $id }}">
                                <a href="#baris-{{ $id }}" class="flex min-h-9 items-center gap-2.5 rounded-lg px-2.5 py-1.5 text-sm text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-navy-800">
                                    <span class="size-2 shrink-0 rounded-full {{ $titik[$perId[$id]->status] }}" title="{{ B::LABEL_STATUS[$perId[$id]->status] }}"></span>
                                    <span class="truncate">{{ $penanda[$id] ?: 'Baris' }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            @endforeach
        </nav>

        @if ($tahap !== TahapNaskah::Draf)
            <div class="border-t border-slate-100 p-4 dark:border-navy-800">
                <x-form.field label="Tampilkan" for="filterBaris">
                    <x-form.select id="filterBaris" wire:model.live="filter" icon="funnel">
                        <option value="">Sesuai pekerjaan Anda</option>
                        @foreach ($pilihanFilter as $kunci => [$nama, $jumlah])
                            <option value="{{ $kunci }}">{{ $nama }}{{ $jumlah !== null ? " ({$jumlah})" : '' }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
            </div>
        @endif
    </aside>

    {{-- Kolom kerja --}}
    <div class="flex min-w-0 flex-1 flex-col">
        <div class="@container flex shrink-0 flex-wrap items-center gap-x-2 gap-y-2 bg-white px-4 py-2.5 lg:gap-x-4 shadow-card sm:px-6 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
            {{-- Ikon tab disembunyikan saat lajurnya sempit. Bila tab dan tombol aksi tidak muat sebaris, tombol aksi turun ke baris berikutnya. --}}
            <x-ui.tabs class="max-w-full max-lg:grow max-sm:[&_[role=tab]]:px-2.5 [&_i]:hidden @3xl:[&_i]:inline-block">
                @foreach ($tabs as $kunci => [$ikon, $nama])
                    <x-ui.tab :active="$tab === $kunci" :icon="$ikon" wire:click="$set('tab', '{{ $kunci }}')" wire:key="tab-{{ $kunci }}">{{ $nama }}</x-ui.tab>
                @endforeach
            </x-ui.tabs>

            <div class="ml-auto flex flex-wrap items-center justify-end gap-2">
                <x-ui.button variant="outline" icon="layout-sidebar-reverse" x-cloak x-show="! pratinjau && $wire.tab === 'kerja'" x-on:click="pratinjau = true">Pratinjau Naskah</x-ui.button>
                @if ($tahap === TahapNaskah::Draf && $naskah->pemrakarsa_id === auth()->id())
                    <x-ui.button variant="outline-danger" icon="trash" wire:click="buangDraf"
                        wire:confirm="Buang draf ini beserta semua barisnya? Tindakan ini tidak bisa dibatalkan.">Buang Draf</x-ui.button>
                @endif
                @foreach ($aksi as $kunci => $tombol)
                    <span @if ($tombol['hambatan']) title="{{ $tombol['hambatan'] }}" @endif wire:key="aksi-{{ $kunci }}">
                        <x-ui.button :variant="$tombol['varian']" :icon="$tombol['ikon']"
                            wire:click="pilihAksi('{{ $kunci }}')" :disabled="(bool) $tombol['hambatan']" loading="pilihAksi">
                            {{ $tombol['label'] }}
                        </x-ui.button>
                    </span>
                @endforeach
            </div>
        </div>

        <div x-data x-on:blok-berubah.window="$nextTick(() => $el.scrollTop = 0)" class="flex-1 overflow-y-auto">
            <main class="mx-auto w-full max-w-[1600px] space-y-5 px-4 py-6 transition-opacity duration-150 sm:px-6" wire:loading.class="opacity-60" wire:target="tab, pilihBlok">
                @if ($tab === 'kerja')
                    @foreach ($aksi as $tombol)
                        @if ($tombol['hambatan'])
                            <x-ui.alert type="warning">{{ $tombol['hambatan'] }}</x-ui.alert>
                            @break
                        @endif
                    @endforeach
                @endif

                @if (filled($naskah->catatan_tahap))
                    <x-ui.alert type="warning" icon="chat-left-text" title="Catatan:">{{ $naskah->catatan_tahap }}</x-ui.alert>
                @endif

                @if ($tahap->terkunci())
                    <x-ui.card title="Isi naskah terkunci" description="Isi tidak bisa diubah lagi. Yang berjalan sekarang adalah proses tanda tangan dan arsip.">
                        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-6">
                            <div class="lg:col-span-2">
                                <dt class="text-sm text-slate-500 dark:text-slate-400">Nota Dinas</dt>
                                <dd class="mt-1 text-sm font-semibold">
                                    @if ($nota && $bisaUnduh)
                                        <x-ui.file-link :href="route('v2.naskah.cetak', [$naskah, 'nota-dinas'])" path="nota.pdf" :label="$nota->nomor_nota" />
                                    @elseif ($nota)
                                        {{ $nota->nomor_nota }}
                                    @else
                                        Belum terbit
                                    @endif
                                </dd>
                            </div>
                            @foreach (A::TAHAPAN_TTD as $kunci => $nama)
                                <div wire:key="ttd-{{ $kunci }}">
                                    <dt class="text-sm text-slate-500 dark:text-slate-400">{{ $nama }}</dt>
                                    <dd class="mt-1 text-sm font-semibold">
                                        @if (isset($naskah->tahapan_ttd[$kunci]))
                                            <i class="bi bi-check-circle-fill text-emerald-500"></i> {{ tanggal($naskah->tahapan_ttd[$kunci]) }}
                                        @else
                                            <span class="font-normal text-slate-400">Belum</span>
                                        @endif
                                    </dd>
                                </div>
                            @endforeach
                        </dl>
                        @if ($naskah->dokumentasi_id)
                            <div class="mt-4 text-sm">
                                <x-ui.file-link :href="route('jdih.show', $naskah->dokumentasi_id)" label="Lihat di JDIH" />
                            </div>
                        @endif
                    </x-ui.card>
                @endif

                @if ($peringatan)
                    <x-ui.alert type="warning" icon="exclamation-triangle-fill">
                        <details>
                            <summary class="-my-2 py-2 font-semibold">{{ count($peringatan) }} hal pada penomoran perlu diperiksa</summary>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($peringatan as $p)
                                    <li wire:key="peringatan-{{ $loop->index }}">{{ $p['pesan'] }}</li>
                                @endforeach
                            </ul>
                        </details>
                    </x-ui.alert>
                @endif

                @unless ($naskah->giliranMilik(auth()->user()) || $tahap === TahapNaskah::Selesai)
                    <x-ui.alert type="primary" icon="eye-fill">
                        Hanya baca. Saat ini giliran <strong>{{ $tahap->peran() }}</strong>{{ $tahap->peran() === 'Peneliti' && $naskah->peneliti ? ' ('.$naskah->peneliti->nama_user.')' : '' }}.
                    </x-ui.alert>
                @endunless
                @if ($tahap === TahapNaskah::Draf)
                    <x-ui.alert type="primary" dismissible>
                        Periksa setiap bagian di panel kiri. Setelah diajukan, rumusan ini beku dan perubahan hanya lewat kolom penyempurnaan.
                    </x-ui.alert>
                @endif

        @if ($tab === 'kerja')
            @if ($blokAktif)
                @php $selesaiBlok = collect($blokAktif['ids'])->filter(fn ($id) => $perId[$id]->status !== B::BELUM)->count(); @endphp

                <x-ui.card :padding="false"
                    :title="$blokAktif['nama'].($blokAktif['sub'] ? ': '.$blokAktif['sub'] : '')"
                    :description="match ($mode) {
                        A::MODE_SUSUN => TemplateNaskah::PETUNJUK[$blokAktif['bagian']],
                        A::MODE_TELITI => 'Tetap bila rumusan sudah benar. Ubah bila Anda bisa memperbaikinya sendiri. Minta PD Perbaiki bila hanya PD yang tahu isinya, misalnya data atau besaran.',
                        default => null,
                    }">
                    @if ($tahap !== TahapNaskah::Draf && $mode !== A::MODE_SESUAIKAN)
                        <x-slot:actions>
                            <x-ui.badge :color="$selesaiBlok === count($blokAktif['ids']) ? 'success' : 'slate'" size="lg">{{ $selesaiBlok }} dari {{ count($blokAktif['ids']) }} baris diperiksa</x-ui.badge>
                        </x-slot:actions>
                    @endif

                    <div class="divide-y divide-slate-100 dark:divide-navy-800">
                        @foreach ($daftarBaris as $b)
                            <livewire:v2.baris-kerja :baris="$b" :naskah="$naskah" :penanda="$penanda[$b->id] ?? null"
                                :wire:key="'baris-'.$b->id.'-'.$naskah->giliran.'-'.($penanda[$b->id] ?? '')" />
                        @endforeach
                    </div>
                </x-ui.card>
            @else
                <x-ui.card>
                    <x-ui.empty-state icon="check2-circle" title="Tidak ada baris pada tampilan ini." class="py-12">
                        <x-ui.button variant="outline-primary" class="mt-2" wire:click="$set('filter', 'semua')">Tampilkan Semua Baris</x-ui.button>
                    </x-ui.empty-state>
                </x-ui.card>
            @endif
        @elseif ($tab === 'naskah')
            <div class="mx-auto flex max-w-4xl flex-wrap items-center justify-between gap-2">
                @if ($tahap !== TahapNaskah::Draf)
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Tampilan naskah">
                        <x-ui.button size="rapat" icon="file-earmark-text" :variant="$banding ? 'outline' : 'primary'" wire:click="$set('tampilNaskah', 'cetak')">Naskah</x-ui.button>
                        <x-ui.button size="rapat" icon="layout-split" :variant="$banding ? 'primary' : 'outline'" wire:click="$set('tampilNaskah', 'banding')">Perbandingan</x-ui.button>
                    </div>
                @endif
                @if ($bisaUnduh)
                    <div class="ml-auto flex flex-wrap justify-end gap-2">
                        <x-ui.button size="rapat" variant="outline" icon="file-earmark-word" :href="route('v2.naskah.cetak', [$naskah, 'naskah-word'])">Naskah Word</x-ui.button>
                        <x-ui.button size="rapat" variant="outline" icon="file-earmark-pdf" :href="route('v2.naskah.cetak', [$naskah, 'naskah-pdf'])">Naskah PDF</x-ui.button>
                        <x-ui.button size="rapat" variant="outline" icon="table" :href="route('v2.naskah.cetak', [$naskah, 'matriks-pdf'])">Matriks PDF</x-ui.button>
                        @if ($nota)
                            <x-ui.button size="rapat" variant="outline" icon="file-earmark-text" :href="route('v2.naskah.cetak', [$naskah, 'nota-dinas'])">Nota Dinas</x-ui.button>
                        @endif
                    </div>
                @endif
            </div>

            @if ($banding)
                <p class="v2-diff v2-kertas mx-auto max-w-4xl text-sm text-slate-500 dark:text-slate-400">
                    {{ collect(B::BERUBAH)->sum(fn (string $s) => $hitung[$s] ?? 0) }} baris berubah dari {{ $total }} baris.
                    <del>Merah</del> dihapus, <ins>hijau</ins> ditambah. Tekan tombol di tepi kertas untuk menuju barisnya di Matriks.
                </p>
                @include('v2.partials.isi-naskah', ['blok' => $blokNaskah, 'tandaiUbahan' => true, 'tombolBaris' => true, 'bisaUbah' => (bool) $mode])
            @else
                <p class="mx-auto max-w-4xl text-sm text-slate-500 dark:text-slate-400">Bentuk naskah bila dicetak saat ini. Nomor, tanggal, dan tanda tangan diisi saat penandatanganan basah.</p>
                @include('v2.partials.isi-naskah', ['blok' => $blokNaskah])
            @endif
        @elseif ($tab === 'berkas')
            <div class="mx-auto max-w-4xl space-y-4">
                <div class="divide-y divide-slate-100 overflow-hidden rounded-2xl bg-white ring-1 ring-slate-200/70 dark:divide-navy-800 dark:bg-navy-900 dark:ring-navy-800">
                    @forelse ($berkas as $file)
                        <div wire:key="berkas-{{ $file->id }}" @class(['flex items-center gap-3 px-4 py-3', 'opacity-60' => ! $file->aktif])>
                            <i class="bi bi-file-earmark-text text-xl text-slate-400"></i>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-navy-900 dark:text-slate-100">{{ $file->nama_asli }}</p>
                                <p class="text-sm text-slate-500">
                                    {{ NaskahBerkas::LABEL[$file->jenis] }}{{ $file->aktif ? '' : ' (versi lama)' }} · {{ $file->pengunggah->nama_user ?? '-' }} · {{ $file->created_at->translatedFormat('d M Y H:i') }}
                                </p>
                            </div>
                            <x-ui.button size="rapat" variant="outline" icon="download" :href="route('v2.naskah.berkas', [$naskah, $file])">Unduh</x-ui.button>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada berkas.</p>
                    @endforelse
                </div>

                @if ($bolehUnggah)
                    <form wire:submit="unggahBerkas" class="flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
                        <x-form.field label="Jenis" for="jenisBerkas" error="jenisBerkas">
                            <select id="jenisBerkas" wire:model="jenisBerkas" class="min-h-10 rounded-lg border-0 py-2 pl-3 text-sm ring-1 ring-slate-200 dark:bg-navy-800 dark:ring-navy-700">
                                <option value="{{ NaskahBerkas::LAMPIRAN }}">{{ NaskahBerkas::LABEL[NaskahBerkas::LAMPIRAN] }}</option>
                                <option value="{{ NaskahBerkas::BAHAN_PENDUKUNG }}">{{ NaskahBerkas::LABEL[NaskahBerkas::BAHAN_PENDUKUNG] }}</option>
                            </select>
                        </x-form.field>
                        <x-form.field label="File" for="berkasBaru" error="berkasBaru" class="min-w-56 flex-1">
                            <input id="berkasBaru" type="file" wire:model="berkasBaru" class="block min-h-10 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:file:bg-navy-800 dark:file:text-slate-200">
                        </x-form.field>
                        <x-ui.button type="submit" icon="upload" loading="unggahBerkas">Unggah</x-ui.button>
                    </form>
                @endif
            </div>
        @else
            <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_16rem]">
                <ol class="space-y-2">
                    @forelse ($riwayat as $r)
                        @php
                            $rincian = $r->aksi === 'ubah_baris' ? json_decode((string) $r->sesudah, true) : null;
                            $sasaran = $r->baris ? ($r->baris->label ?: TemplateNaskah::BAGIAN[$r->baris->bagian]) : null;
                        @endphp
                        <li wire:key="riwayat-{{ $r->id }}" class="rounded-lg bg-white px-4 py-2.5 text-sm shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p>
                                    <strong class="text-navy-900 dark:text-white">{{ $r->user->nama_user ?? 'Sistem' }}</strong>
                                    @if ($r->aksi === 'tahap')
                                        memindahkan naskah: {{ $r->sebelum }} <i class="bi bi-arrow-right"></i> {{ $r->sesudah }}
                                    @elseif ($r->aksi === 'tahapan')
                                        mencatat tahap {{ $r->sesudah }} selesai
                                    @elseif ($r->aksi === 'tambah_baris')
                                        menambah baris {{ $sasaran ?? $r->sesudah }}
                                    @elseif ($r->aksi === 'buang_baris')
                                        menghapus baris sisipan {{ $r->sesudah }}
                                    @else
                                        menandai {{ $sasaran }}: {{ B::LABEL_STATUS[$rincian['status'] ?? 'belum'] ?? '' }}
                                    @endif
                                </p>
                                <time class="text-sm text-slate-400">{{ $r->created_at->translatedFormat('d M Y H:i') }}</time>
                            </div>
                            @if ($r->catatan)
                                <p class="mt-1 text-slate-500">{{ $r->catatan }}</p>
                            @endif
                            @if ($rincian)
                                @php
                                    $lama = json_decode((string) $r->sebelum, true) ?? [];
                                    $teksLama = ($lama['penyempurnaan'] ?? null) ?: ($lama['awal'] ?? '');
                                    $teksBaru = ($rincian['penyempurnaan'] ?? null) ?: ($rincian['awal'] ?? '');
                                @endphp
                                @if ($teksLama !== $teksBaru)
                                    <details class="mt-1">
                                        <summary class="-my-2 cursor-pointer py-2 text-sm font-semibold text-primary-600 dark:text-primary-300">Lihat perubahan</summary>
                                        <p class="v2-diff mt-1 whitespace-pre-line text-slate-600 dark:text-slate-300">{!! App\Support\DiffKata::html($teksLama, $teksBaru) !!}</p>
                                    </details>
                                @endif
                            @endif
                        </li>
                    @empty
                        <li class="rounded-lg bg-white px-5 py-10 text-center text-sm text-slate-500 shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">Belum ada riwayat.</li>
                    @endforelse
                </ol>
                <aside class="rounded-2xl bg-white p-4 shadow-card dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
                    <h2 class="text-sm font-bold text-slate-500 dark:text-slate-400">Versi tersimpan</h2>
                    <ul class="mt-2 space-y-1.5 text-sm">
                        @forelse ($versi as $v)
                            <li wire:key="versi-{{ $v->id }}">
                                <button type="button" wire:click="lihatVersi({{ $v->id }})" class="block w-full rounded-lg px-2 py-1 text-left transition hover:bg-slate-100 dark:hover:bg-navy-800">
                                    <span class="font-semibold text-primary-600 dark:text-primary-300">Versi {{ $v->versi }}</span>
                                    <span class="block text-sm text-slate-500">{{ TahapNaskah::from($v->tahap)->label() }} · {{ $v->created_at->translatedFormat('d M Y H:i') }}</span>
                                </button>
                            </li>
                        @empty
                            <li class="text-slate-500">Versi dibuat setiap kali naskah berpindah giliran.</li>
                        @endforelse
                    </ul>
                </aside>
            </div>
        @endif
            </main>
        </div>

        {{-- Berpindah antarbagian --}}
        @if ($tab === 'kerja' && $blokAktif)
            <footer class="grid shrink-0 grid-cols-2 items-center gap-3 bg-white px-4 py-3 shadow-card sm:grid-cols-[1fr_auto_1fr] sm:px-6 dark:bg-navy-900 dark:shadow-none dark:ring-1 dark:ring-white/5">
                <div>
                    @if ($blokSebelumnya)
                        <x-ui.button variant="outline" icon="arrow-left" wire:click="pilihBlok('{{ $blokSebelumnya['kunci'] }}')">Sebelumnya</x-ui.button>
                    @endif
                </div>

                <p class="hidden max-w-md truncate text-center text-sm text-slate-500 sm:block dark:text-slate-400">
                    Bagian {{ $posisiBlok + 1 }} dari {{ $daftarBlok->count() }}
                </p>

                <div class="flex justify-end">
                    @if ($blokBerikutnya)
                        <x-ui.button wire:click="pilihBlok('{{ $blokBerikutnya['kunci'] }}')">Berikutnya <i class="bi bi-arrow-right" aria-hidden="true"></i></x-ui.button>
                    @else
                        <x-ui.button icon="file-earmark-text" wire:click="$set('tab', 'naskah')">Lihat Hasil Naskah</x-ui.button>
                    @endif
                </div>
            </footer>
        @endif
    </div>

    {{-- Pratinjau naskah: bisa digulir sendiri dan menyorot bagian yang sedang dikerjakan. --}}
    <aside x-show="pratinjau && $wire.tab === 'kerja'" x-cloak aria-label="Pratinjau naskah"
        class="fixed inset-0 z-40 flex flex-col bg-surface xl:static xl:z-auto xl:w-[28rem] xl:shrink-0 2xl:w-[34rem] dark:bg-navy-950 dark:ring-1 dark:ring-white/5">
        <style wire:ignore x-text="gaya"></style>
        <div class="flex shrink-0 items-center justify-between gap-2 bg-white px-4 py-2.5 shadow-card dark:bg-navy-900 dark:shadow-none">
            <h2 class="text-sm font-bold">Pratinjau Naskah</h2>
            <div class="flex items-center gap-2">
                @if ($tahap !== TahapNaskah::Draf)
                    <x-ui.button size="rapat" icon="layout-split" variant="outline" x-show="! bandingPratinjau"
                        x-on:click="bandingPratinjau = true; $dispatch('pratinjau-banding', { aktif: true })">Perbandingan</x-ui.button>
                    <x-ui.button size="rapat" icon="layout-split" variant="primary" x-show="bandingPratinjau" x-cloak
                        x-on:click="bandingPratinjau = false; $dispatch('pratinjau-banding', { aktif: false })">Perbandingan</x-ui.button>
                @endif
                <x-ui.button size="rapat" square variant="ghost" icon="x-lg" title="Tutup pratinjau" aria-label="Tutup pratinjau" x-on:click="pratinjau = false" />
            </div>
        </div>
        @if ($tahap !== TahapNaskah::Draf)
            <p x-show="bandingPratinjau" x-cloak class="v2-diff v2-kertas shrink-0 bg-white px-4 pb-2.5 text-sm text-slate-500 dark:bg-navy-900 dark:text-slate-400">
                <del>Merah</del> dihapus, <ins>hijau</ins> ditambah.
            </p>
        @endif
        <div x-ref="pratinjauGulir" class="flex-1 overflow-y-auto p-3">
            <livewire:v2.pratinjau-naskah :naskah="$naskah" :wire:key="'pratinjau-'.$naskah->id" />
        </div>
    </aside>
    </div>

    {{-- Konfirmasi perpindahan tahap --}}
    <x-ui.modal id="modalAksi" :title="$aksiModal['label'] ?? 'Konfirmasi'" size="lg">
        @error('aksi')
            <x-ui.alert color="danger">{{ $message }}</x-ui.alert>
        @enderror

        @if (! empty($aksiModal['peneliti']))
            <x-form.field label="Peneliti" for="penelitiId" error="penelitiId" required>
                <select id="penelitiId" wire:model="penelitiId" class="w-full rounded-lg border-0 py-2 pl-3 text-sm ring-1 ring-slate-200 dark:bg-navy-800 dark:ring-navy-700">
                    <option value="">Pilih peneliti</option>
                    @foreach ($peneliti as $orang)
                        <option value="{{ $orang->id }}">{{ $orang->nama_user }}</option>
                    @endforeach
                </select>
            </x-form.field>
        @endif

        @php
            $isian = 'w-full rounded-lg border-0 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700';
            $bentuk = $aksiModal['form'] ?? null;
        @endphp

        @if ($bentuk === 'nota')
            <div class="space-y-3">
                <x-form.field label="Nomor nota dinas" for="nomorInputan" error="nomorInputan" required hint="Angka tengah pada 180/…/KUM/{{ now()->year }}.">
                    <input id="nomorInputan" type="text" inputmode="numeric" maxlength="3" wire:model="nomorInputan" class="{{ $isian }}">
                </x-form.field>
                <x-form.field label="Kepada" for="kepada" error="kepada" required>
                    <input id="kepada" type="text" wire:model="kepada" class="{{ $isian }}">
                </x-form.field>
                <x-form.field label="Tanda tangan" for="tandaTanganId" error="tandaTanganId" required hint="Nomor dan tanda tangan ini otomatis masuk ke matriks.">
                    <select id="tandaTanganId" wire:model="tandaTanganId" class="{{ $isian }}">
                        <option value="">Pilih tanda tangan</option>
                        @foreach ($daftarTtd as $ttd)
                            <option value="{{ $ttd->id }}">{{ $ttd->nama_ttd }} ({{ $ttd->jabatan }})</option>
                        @endforeach
                    </select>
                </x-form.field>
            </div>
        @elseif ($bentuk === 'tahapan')
            <x-form.field label="Tanggal selesai" for="tanggalTahapan" error="tanggalTahapan" required>
                <input id="tanggalTahapan" type="date" max="{{ now()->toDateString() }}" wire:model="tanggalTahapan" class="{{ $isian }}">
            </x-form.field>
        @elseif ($bentuk === 'arsip')
            <div class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-form.field label="Nomor produk hukum" for="nomorArsip" error="nomorArsip" required>
                        <input id="nomorArsip" type="text" inputmode="numeric" maxlength="3" wire:model="nomorArsip" class="{{ $isian }}">
                    </x-form.field>
                    <x-form.field label="Tanggal penetapan" for="tanggalPenetapan" error="tanggalPenetapan" required>
                        <input id="tanggalPenetapan" type="date" wire:model="tanggalPenetapan" class="{{ $isian }}">
                    </x-form.field>
                    <x-form.field label="Nomor Berita Daerah" for="nomorBerita" error="nomorBerita">
                        <input id="nomorBerita" type="text" inputmode="numeric" maxlength="3" wire:model="nomorBerita" class="{{ $isian }}">
                    </x-form.field>
                    <x-form.field label="Tahun Berita Daerah" for="tahunBerita" error="tahunBerita">
                        <input id="tahunBerita" type="text" inputmode="numeric" maxlength="4" wire:model="tahunBerita" class="{{ $isian }}">
                    </x-form.field>
                </div>
                <x-form.field label="PDF bertanda tangan basah" for="fileArsip" error="fileArsip" required hint="Dokumen ini langsung tampil untuk umum di JDIH.">
                    <input id="fileArsip" type="file" accept="application/pdf" wire:model="fileArsip" class="block min-h-10 w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:file:bg-navy-800 dark:file:text-slate-200">
                </x-form.field>
            </div>
        @else
            <x-form.field :label="! empty($aksiModal['catatan']) ? 'Alasan' : 'Catatan (opsional)'" for="catatan" error="catatan" :required="! empty($aksiModal['catatan'])" class="mt-3">
                <textarea id="catatan" wire:model="catatan" rows="3" class="{{ $isian }}"></textarea>
            </x-form.field>
        @endif

        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button icon="check2" wire:click="jalankanAksi" loading="jalankanAksi,fileArsip">Lanjutkan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Isi naskah pada versi lama --}}
    <x-ui.modal id="modalVersi" :title="$isiVersi ? 'Versi '.$isiVersi->versi : 'Versi'" :description="$isiVersi ? TahapNaskah::from($isiVersi->tahap)->label().' · '.$isiVersi->created_at->translatedFormat('d M Y H:i') : null" size="5xl">
        @if ($isiVersi)
            <table class="w-full table-fixed border-collapse text-sm">
                <thead class="text-left text-sm text-slate-500">
                    <tr>
                        <th class="w-28 pb-2 font-semibold">Baris</th>
                        <th class="pb-2 font-semibold">Rumusan awal</th>
                        <th class="pb-2 font-semibold">Penyempurnaan</th>
                        <th class="w-40 pb-2 font-semibold">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-navy-800">
                    @foreach ($isiVersi->isi as $b)
                        <tr wire:key="versi-baris-{{ $b['id'] }}" class="align-top">
                            <td class="py-2 pr-2 font-semibold">{{ $b['label'] ?: TemplateNaskah::BAGIAN[$b['bagian']] }}</td>
                            <td class="py-2 pr-3 whitespace-pre-line">{{ $b['rumusan_awal'] }}</td>
                            <td class="py-2 pr-3 whitespace-pre-line">{{ $b['status'] === B::DIHAPUS ? 'Dihapus' : ($b['status'] === B::TETAP ? 'Tetap' : $b['rumusan_penyempurnaan']) }}</td>
                            <td class="py-2 text-slate-500">{{ $b['keterangan'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </x-ui.modal>
</div>
