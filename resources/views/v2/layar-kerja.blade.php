@php
    use App\Enums\TahapNaskah;
    use App\Models\NaskahBaris as B;
    use App\Models\NaskahBerkas;
    use App\Services\AlurNaskah as A;
    use App\Support\TemplateNaskah;

    $tahap = $naskah->tahap;
    $persen = $total ? (int) round($diperiksa / $total * 100) : 0;
    $bagianJenis = TemplateNaskah::bagian($naskah->jenis);
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
    $tabs = ['kerja' => 'Matriks', 'naskah' => 'Naskah', 'berkas' => 'Berkas', 'riwayat' => 'Riwayat'];
    $aksiModal = $aksiTerpilih ? ($aksi[$aksiTerpilih] ?? null) : null;
@endphp

<div>
    {{-- Kepala kerja: selalu terlihat saat menggulir --}}
    <div class="sticky top-14 z-30 border-b border-slate-200 bg-white/95 backdrop-blur dark:border-navy-800 dark:bg-navy-900/95">
        <div class="px-4 pt-3 lg:px-6">
            <div class="flex flex-wrap items-start justify-between gap-x-6 gap-y-3">
                <div class="min-w-0 flex-1 basis-96">
                    <p class="flex flex-wrap items-center gap-2 text-xs font-semibold text-slate-500">
                        <a href="{{ route('v2.beranda') }}" wire:navigate class="hover:text-navy-900 dark:hover:text-white"><i class="bi bi-arrow-left"></i></a>
                        {{ $naskah->nomor }} · {{ $naskah->jenis }}
                        <x-ui.badge :color="$tahap->warna()">{{ $tahap->label() }}</x-ui.badge>
                    </p>
                    <h1 class="mt-1 line-clamp-1 text-base font-bold text-navy-900 dark:text-white" title="{{ $naskah->tentang }}">{{ $naskah->tentang }}</h1>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    @foreach ($aksi as $kunci => $tombol)
                        <span @if ($tombol['hambatan']) title="{{ $tombol['hambatan'] }}" @endif wire:key="aksi-{{ $kunci }}">
                            <x-ui.button size="sm" :variant="$tombol['varian']" :icon="$tombol['ikon']"
                                wire:click="pilihAksi('{{ $kunci }}')" :disabled="(bool) $tombol['hambatan']" loading="pilihAksi">
                                {{ $tombol['label'] }}
                            </x-ui.button>
                        </span>
                    @endforeach
                </div>
            </div>

            <div class="mt-2 flex items-center gap-3">
                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800" role="progressbar" aria-valuenow="{{ $persen }}" aria-valuemin="0" aria-valuemax="100" aria-label="Baris diperiksa">
                    <div class="h-full rounded-full bg-brand-500 transition-all" style="width: {{ $persen }}%"></div>
                </div>
                <p class="shrink-0 text-xs text-slate-500 dark:text-slate-400">
                    @if ($tahap === TahapNaskah::Draf)
                        <strong class="text-navy-900 dark:text-white">{{ $total }}</strong> baris
                    @else
                        <strong class="text-navy-900 dark:text-white">{{ $diperiksa }}/{{ $total }}</strong> diperiksa
                        · {{ $hitung[B::TETAP] ?? 0 }} tetap · {{ $pilihanFilter['diubah'][1] }} diubah
                    @endif
                    @foreach ($aksi as $tombol)
                        @if ($tombol['hambatan'])
                            · <span class="text-orange-600 dark:text-orange-300">{{ $tombol['hambatan'] }}</span>
                            @break
                        @endif
                    @endforeach
                </p>
            </div>

            <nav class="mt-1 -mb-px flex gap-1" aria-label="Bagian kerja">
                @foreach ($tabs as $kunci => $nama)
                    <button type="button" wire:click="$set('tab', '{{ $kunci }}')" wire:key="tab-{{ $kunci }}"
                        @class([
                            'border-b-2 px-3 py-2 text-sm font-semibold transition',
                            'border-primary-500 text-primary-700 dark:text-primary-300' => $tab === $kunci,
                            'border-transparent text-slate-500 hover:text-navy-900 dark:hover:text-white' => $tab !== $kunci,
                        ])>{{ $nama }}</button>
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Giliran & catatan terakhir --}}
    <div class="mx-auto mt-4 max-w-[1500px] space-y-2 px-4 lg:px-6">
        @if (filled($naskah->catatan_tahap))
            <p class="rounded-lg bg-orange-50 px-4 py-2.5 text-sm text-orange-900 ring-1 ring-orange-200 dark:bg-orange-500/10 dark:text-orange-200 dark:ring-orange-500/20">
                <i class="bi bi-chat-left-text"></i> <strong>Catatan:</strong> {{ $naskah->catatan_tahap }}
            </p>
        @endif
        @unless ($naskah->giliranMilik(auth()->user()))
            <p class="rounded-lg bg-slate-200/60 px-4 py-2.5 text-sm text-slate-600 dark:bg-navy-800 dark:text-slate-300">
                <i class="bi bi-eye"></i> Hanya baca. Saat ini giliran <strong>{{ $tahap->peran() }}</strong>{{ $tahap->peran() === 'Peneliti' && $naskah->peneliti ? ' ('.$naskah->peneliti->nama_user.')' : '' }}.
            </p>
        @endunless
        @if ($tahap === TahapNaskah::Draf)
            <p class="rounded-lg bg-primary-50 px-4 py-2.5 text-sm text-primary-900 ring-1 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-100 dark:ring-primary-500/20">
                <i class="bi bi-info-circle"></i> Periksa setiap baris. Setelah diajukan, rumusan ini beku dan perubahan hanya lewat kolom penyempurnaan.
            </p>
        @endif
    </div>

    <main class="mx-auto max-w-[1500px] px-4 py-4 lg:px-6">
        @if ($tab === 'kerja')
            <div class="grid items-start gap-5 lg:grid-cols-[15rem_minmax(0,1fr)]">
                {{-- Navigasi struktur --}}
                <aside x-data="{ strukturBuka: false }" class="lg:sticky lg:top-[11.5rem] lg:max-h-[calc(100vh-12.5rem)] lg:overflow-y-auto">
                    <div class="rounded-xl bg-white p-3 ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                        <label class="block">
                            <span class="text-[0.7rem] font-semibold tracking-wide text-slate-400 uppercase">Tampilkan</span>
                            <select wire:model.live="filter" class="mt-1 w-full rounded-lg border-0 bg-slate-50 py-1.5 pl-2.5 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700">
                                <option value="">Sesuai pekerjaan Anda</option>
                                @foreach ($pilihanFilter as $kunci => [$nama, $jumlah])
                                    <option value="{{ $kunci }}">{{ $nama }}{{ $jumlah !== null ? " ({$jumlah})" : '' }}</option>
                                @endforeach
                            </select>
                        </label>

                        <button type="button" x-on:click="strukturBuka = !strukturBuka" x-bind:aria-expanded="strukturBuka"
                            class="mt-2 flex w-full items-center justify-between rounded-lg px-1 py-1 text-xs font-semibold text-slate-500 lg:hidden">
                            Struktur naskah <i class="bi bi-chevron-down transition" x-bind:class="strukturBuka && 'rotate-180'"></i>
                        </button>

                        {{-- Baris yang disembunyikan filter dimunculkan dulu, baru dituju. --}}
                        <nav class="mt-3 hidden space-y-3 text-sm lg:block" x-bind:class="{ 'block!': strukturBuka }" aria-label="Struktur naskah"
                            x-on:click="
                                const tautan = $event.target.closest('a');
                                if (tautan && ! document.querySelector(tautan.getAttribute('href'))) {
                                    $event.preventDefault();
                                    $wire.lompatKe(tautan.getAttribute('href').replace('#baris-', ''))
                                        .then(() => setTimeout(() => document.querySelector(tautan.getAttribute('href'))?.scrollIntoView(), 50));
                                }
                            ">
                            @foreach ($bagianJenis as $kunci => $nama)
                                @php $isi = $semuaBaris->where('bagian', $kunci); @endphp
                                @continue($isi->isEmpty())
                                <div wire:key="nav-{{ $kunci }}">
                                    <p class="px-1 text-xs font-bold text-navy-900 dark:text-slate-200">{{ $nama }}</p>
                                    @if ($isi->count() === 1 && ! $isi->first()->label)
                                        <a href="#baris-{{ $isi->first()->id }}" class="mt-0.5 flex items-center gap-2 rounded px-1 py-0.5 text-xs text-slate-500 hover:bg-slate-100 dark:hover:bg-navy-800">
                                            <span class="size-2 rounded-full {{ $titik[$isi->first()->status] }}"></span> {{ $tahap === TahapNaskah::Draf ? 'Buka baris' : B::LABEL_STATUS[$isi->first()->status] }}
                                        </a>
                                    @elseif (in_array($kunci, ['menimbang', 'mengingat', 'diktum'], true) && $naskah->jenis !== 'Surat Keputusan' || in_array($kunci, ['menimbang', 'mengingat'], true))
                                        <p class="mt-1 flex flex-wrap gap-1 px-1">
                                            @foreach ($isi as $b)
                                                <a href="#baris-{{ $b->id }}" title="{{ $penanda[$b->id] }} {{ B::LABEL_STATUS[$b->status] }}" class="size-2.5 rounded-full {{ $titik[$b->status] }}"><span class="sr-only">{{ $penanda[$b->id] }}</span></a>
                                            @endforeach
                                        </p>
                                    @else
                                        <ul class="mt-0.5">
                                            @foreach ($isi as $b)
                                                <li>
                                                    <a href="#baris-{{ $b->id }}" @class(['flex items-center gap-2 rounded px-1 py-0.5 text-xs hover:bg-slate-100 dark:hover:bg-navy-800', 'font-semibold text-navy-900 dark:text-slate-200' => str_starts_with((string) $b->label, 'BAB'), 'pl-3 text-slate-500' => ! str_starts_with((string) $b->label, 'BAB')])>
                                                        <span class="size-2 shrink-0 rounded-full {{ $titik[$b->status] }}"></span>
                                                        <span class="truncate">{{ $penanda[$b->id] ?: 'Baris' }}</span>
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            @endforeach
                        </nav>
                    </div>
                </aside>

                {{-- Baris --}}
                <div class="min-w-0 space-y-3">
                    @forelse ($daftarBaris as $b)
                        <livewire:v2.baris-kerja :baris="$b" :naskah="$naskah" :penanda="$penanda[$b->id] ?? null"
                            :wire:key="'baris-'.$b->id.'-'.$naskah->giliran.'-'.($penanda[$b->id] ?? '')" />
                    @empty
                        <div class="rounded-xl border border-dashed border-slate-300 px-6 py-14 text-center dark:border-navy-700">
                            <i class="bi bi-check2-circle text-3xl text-brand-500"></i>
                            <p class="mt-2 font-semibold text-navy-900 dark:text-white">Tidak ada baris pada tampilan ini.</p>
                            <p class="mt-1 text-sm text-slate-500">Pilih "Semua baris" di panel kiri untuk melihat seluruh matriks.</p>
                        </div>
                    @endforelse

                    @if ($sisaBaris)
                        <div class="pt-1 text-center">
                            <x-ui.button variant="outline" icon="chevron-down" wire:click="muatLagi" loading="muatLagi">
                                Muat baris berikutnya ({{ $sisaBaris }} lagi)
                            </x-ui.button>
                        </div>
                    @endif
                </div>
            </div>
        @elseif ($tab === 'naskah')
            <div class="mx-auto mb-4 flex max-w-3xl flex-wrap items-center justify-between gap-2">
                <p class="text-xs text-slate-500 dark:text-slate-400">Bentuk naskah bila dicetak saat ini. Nomor, tanggal, dan tanda tangan diisi saat penandatanganan basah.</p>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" variant="outline" icon="file-earmark-word" :href="route('v2.naskah.cetak', [$naskah, 'naskah-word'])">Naskah Word</x-ui.button>
                    <x-ui.button size="sm" variant="outline" icon="file-earmark-pdf" :href="route('v2.naskah.cetak', [$naskah, 'naskah-pdf'])">Naskah PDF</x-ui.button>
                    <x-ui.button size="sm" variant="outline" icon="table" :href="route('v2.naskah.cetak', [$naskah, 'matriks-pdf'])">Matriks PDF</x-ui.button>
                </div>
            </div>
            @include('v2.partials.isi-naskah', ['blok' => $blokNaskah])
        @elseif ($tab === 'berkas')
            <div class="mx-auto max-w-3xl space-y-4">
                <div class="divide-y divide-slate-100 overflow-hidden rounded-xl bg-white ring-1 ring-slate-200/70 dark:divide-navy-800 dark:bg-navy-900 dark:ring-navy-800">
                    @forelse ($berkas as $file)
                        <div wire:key="berkas-{{ $file->id }}" @class(['flex items-center gap-3 px-4 py-3', 'opacity-60' => ! $file->aktif])>
                            <i class="bi bi-file-earmark-text text-xl text-slate-400"></i>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-navy-900 dark:text-slate-100">{{ $file->nama_asli }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ NaskahBerkas::LABEL[$file->jenis] }}{{ $file->aktif ? '' : ' (versi lama)' }} · {{ $file->pengunggah->nama_user ?? '-' }} · {{ $file->created_at->translatedFormat('d M Y H:i') }}
                                </p>
                            </div>
                            <x-ui.button size="sm" variant="outline" icon="download" :href="route('v2.naskah.berkas', [$naskah, $file])">Unduh</x-ui.button>
                        </div>
                    @empty
                        <p class="px-5 py-10 text-center text-sm text-slate-500">Belum ada berkas.</p>
                    @endforelse
                </div>

                @if ($bolehUnggah)
                    <form wire:submit="unggahBerkas" class="flex flex-wrap items-end gap-3 rounded-xl bg-white p-4 ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                        <x-form.field label="Jenis" for="jenisBerkas" error="jenisBerkas">
                            <select id="jenisBerkas" wire:model="jenisBerkas" class="rounded-lg border-0 py-2 pl-3 text-sm ring-1 ring-slate-200 dark:bg-navy-800 dark:ring-navy-700">
                                <option value="{{ NaskahBerkas::LAMPIRAN }}">{{ NaskahBerkas::LABEL[NaskahBerkas::LAMPIRAN] }}</option>
                                <option value="{{ NaskahBerkas::BAHAN_PENDUKUNG }}">{{ NaskahBerkas::LABEL[NaskahBerkas::BAHAN_PENDUKUNG] }}</option>
                            </select>
                        </x-form.field>
                        <x-form.field label="File" for="berkasBaru" error="berkasBaru" class="min-w-56 flex-1">
                            <input id="berkasBaru" type="file" wire:model="berkasBaru" class="block w-full text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold dark:file:bg-navy-800 dark:file:text-slate-200">
                        </x-form.field>
                        <x-ui.button type="submit" icon="upload" loading="unggahBerkas">Unggah</x-ui.button>
                    </form>
                @endif
            </div>
        @else
            <div class="mx-auto grid max-w-5xl items-start gap-5 lg:grid-cols-[minmax(0,1fr)_16rem]">
                <ol class="space-y-2">
                    @forelse ($riwayat as $r)
                        @php
                            $rincian = $r->aksi === 'ubah_baris' ? json_decode((string) $r->sesudah, true) : null;
                            $sasaran = $r->baris ? ($r->baris->label ?: TemplateNaskah::BAGIAN[$r->baris->bagian]) : null;
                        @endphp
                        <li wire:key="riwayat-{{ $r->id }}" class="rounded-lg bg-white px-4 py-2.5 text-sm ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p>
                                    <strong class="text-navy-900 dark:text-white">{{ $r->user->nama_user ?? 'Sistem' }}</strong>
                                    @if ($r->aksi === 'tahap')
                                        memindahkan naskah: {{ $r->sebelum }} <i class="bi bi-arrow-right"></i> {{ $r->sesudah }}
                                    @elseif ($r->aksi === 'tambah_baris')
                                        menambah baris {{ $sasaran }}
                                    @else
                                        menandai {{ $sasaran }}: {{ B::LABEL_STATUS[$rincian['status'] ?? 'belum'] ?? '' }}
                                    @endif
                                </p>
                                <time class="text-xs text-slate-400">{{ $r->created_at->translatedFormat('d M Y H:i') }}</time>
                            </div>
                            @if ($r->catatan)
                                <p class="mt-1 text-slate-500">{{ $r->catatan }}</p>
                            @endif
                        </li>
                    @empty
                        <li class="rounded-lg bg-white px-5 py-10 text-center text-sm text-slate-500 ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">Belum ada riwayat.</li>
                    @endforelse
                </ol>
                <aside class="rounded-xl bg-white p-4 ring-1 ring-slate-200/70 dark:bg-navy-900 dark:ring-navy-800">
                    <h2 class="text-xs font-bold tracking-wider text-slate-500 uppercase">Versi tersimpan</h2>
                    <ul class="mt-2 space-y-1.5 text-sm">
                        @forelse ($versi as $v)
                            <li wire:key="versi-{{ $v->id }}">
                                <span class="font-semibold">Versi {{ $v->versi }}</span>
                                <span class="block text-xs text-slate-500">{{ TahapNaskah::from($v->tahap)->label() }} · {{ $v->created_at->translatedFormat('d M Y H:i') }}</span>
                            </li>
                        @empty
                            <li class="text-slate-500">Versi dibuat setiap kali naskah berpindah giliran.</li>
                        @endforelse
                    </ul>
                </aside>
            </div>
        @endif
    </main>

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

        <x-form.field :label="! empty($aksiModal['catatan']) ? 'Alasan' : 'Catatan (opsional)'" for="catatan" error="catatan" :required="! empty($aksiModal['catatan'])" class="mt-3">
            <textarea id="catatan" wire:model="catatan" rows="3" class="w-full rounded-lg border-0 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700"></textarea>
        </x-form.field>

        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button icon="check2" wire:click="jalankanAksi" loading="jalankanAksi">Lanjutkan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
