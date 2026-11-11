@php
    use App\Enums\TahapNaskah;

    $keteranganTahap = match ($tahap) {
        TahapNaskah::Penandatanganan => 'Catat setiap paraf dan tanda tangan basah setelah selesai. Tanggal masih bisa dibetulkan bila salah pilih.',
        TahapNaskah::Arsip => 'Keempat tahapan sudah tercatat. Naskah menunggu diarsipkan Bagian Hukum; tanggal masih bisa dibetulkan.',
        TahapNaskah::Selesai => 'Naskah sudah diarsipkan ke JDIH. Catatan tanda tangan tidak bisa diubah lagi.',
        default => 'Dimulai setelah nota dinas terbit.',
    };
    $sudahTtd = in_array($tahap, TahapNaskah::TERLIHAT_TAMU, true);
    $isian = 'w-full rounded-lg border-0 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700';
    $jumlahVersi = $versiNota->count();

    // Nomor jadi di bawah isian: bagian sebelum dan sesudah {nomor} sudah pasti, tinggal angkanya.
    $letakNomor = collect($potonganNomor)->search(fn ($p) => $p['jenis'] === 'nomor');
    $awalanNomor = collect($potonganNomor)->take((int) $letakNomor)->pluck('isi')->implode('');
    $akhiranNomor = collect($potonganNomor)->slice((int) $letakNomor + 1)->pluck('isi')->implode('');
@endphp

<div class="mx-auto max-w-6xl">
    <x-ui.page-header :title="$judul" :description="$naskah->nomor_tampil . ' · ' . $naskah->jenis" icon="vector-pen"
        :back="$pemantau ? route('v2.daftar', 'penandatanganan') : false">
        @unless ($pemantau)
            <x-slot:actions>
                <x-ui.button variant="outline" icon="table" :href="route('v2.naskah.kerja', $naskah)">Buka Matriks</x-ui.button>
            </x-slot:actions>
        @endunless
    </x-ui.page-header>

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="flex flex-col gap-6 lg:col-span-2">
            {{-- Tamu datang untuk mencatat tanda tangan, jadi kartu tahapan tampil lebih dulu. --}}
            <x-ui.card title="Nota Dinas" :class="$pemantau ? 'order-2' : ''"
                :description="$nota ? 'Surat pengantar naskah dari Bagian Hukum kepada Perangkat Daerah.' : ($bolehTerbit ? 'Isi nomor, tujuan, dan penandatangan. Sesudah terbit, naskah masuk tahap penandatanganan.' : null)">
                @if ($nota)
                    <dl class="grid gap-4 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Nomor</dt>
                            <dd class="mt-0.5 font-semibold text-navy-900 dark:text-white">{{ $nota->nomor_nota }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Tanggal</dt>
                            <dd class="mt-0.5 font-medium">{{ tanggal($nota->tanggal_nota) }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Kepada</dt>
                            <dd class="mt-0.5 font-medium">{{ $nota->kepada }}</dd>
                        </div>
                        <div>
                            <dt class="text-slate-500 dark:text-slate-400">Penandatangan</dt>
                            <dd class="mt-0.5 font-medium">{{ $nota->tandaTangan->nama_ttd ?? '-' }}</dd>
                        </div>
                    </dl>

                    <div class="mt-5 border-t border-slate-100 pt-5 dark:border-navy-800">
                        <h3 class="mb-3 text-sm font-bold text-navy-900 dark:text-white">Berkas Nota Dinas</h3>
                        <ul class="space-y-2">
                            @forelse ($versiNota as $v)
                                @php $nomorVersi = $jumlahVersi - $loop->index; @endphp
                                <li wire:key="versi-nota-{{ $v->id }}" @class([
                                    'flex flex-wrap items-center gap-x-4 gap-y-3 rounded-xl p-3 ring-1 ring-inset sm:flex-nowrap',
                                    'bg-emerald-50/60 ring-emerald-100 dark:bg-emerald-500/5 dark:ring-emerald-500/15' => $v->aktif,
                                    'ring-slate-100 dark:ring-navy-800' => ! $v->aktif,
                                ])>
                                    <i class="bi bi-file-earmark-pdf-fill shrink-0 text-2xl {{ $v->aktif ? 'text-rose-500' : 'text-slate-300 dark:text-navy-600' }}" aria-hidden="true"></i>
                                    <div class="min-w-0 flex-1">
                                        <p class="flex flex-wrap items-center gap-2 font-semibold text-navy-900 dark:text-white">
                                            Versi {{ $nomorVersi }}
                                            <x-ui.badge :color="$v->aktif ? 'success' : 'slate'">{{ $v->aktif ? 'Berlaku' : 'Diganti' }}</x-ui.badge>
                                        </p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">
                                            {{ $nomorVersi === 1 ? 'Terbit' : 'Diterbitkan ulang' }} {{ $v->created_at->translatedFormat('d M Y H:i') }} · {{ $v->pengunggah->nama_user ?? '-' }}
                                        </p>
                                        @if (filled($v->keterangan))
                                            <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-300">Alasan: {{ $v->keterangan }}</p>
                                        @endif
                                    </div>
                                    @if ($bisaUnduh)
                                        <x-ui.button size="sm" :variant="$v->aktif ? 'outline-primary' : 'outline'" icon="download" class="max-sm:w-full"
                                            :href="route('v2.naskah.berkas', [$naskah, $v])">Unduh</x-ui.button>
                                    @endif
                                </li>
                            @empty
                                {{-- Nota dinas yang terbit sebelum ada penyimpanan versi: PDF dibuat saat diunduh. --}}
                                <li class="flex flex-wrap items-center gap-x-4 gap-y-3 rounded-xl p-3 ring-1 ring-slate-100 ring-inset sm:flex-nowrap dark:ring-navy-800">
                                    <i class="bi bi-file-earmark-pdf-fill shrink-0 text-2xl text-rose-500" aria-hidden="true"></i>
                                    <div class="min-w-0 flex-1">
                                        <p class="font-semibold text-navy-900 dark:text-white">Nota Dinas</p>
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Belum disimpan sebagai versi; dibuat dari pengaturan Format Dokumen saat diunduh.</p>
                                    </div>
                                    @if ($bisaUnduh)
                                        <x-ui.button size="sm" variant="outline-primary" icon="download" class="max-sm:w-full"
                                            :href="route('v2.naskah.cetak', [$naskah, 'nota-dinas'])">Unduh</x-ui.button>
                                    @endif
                                </li>
                            @endforelse
                        </ul>

                        @if ($bolehTerbitUlang)
                            <div x-data="{ buka: false }" x-on:nota-terbit-ulang.window="buka = false" class="mt-4">
                                <div x-show="! buka" class="flex flex-wrap items-center justify-between gap-3">
                                    <p class="text-sm text-slate-500 dark:text-slate-400">Salah pilih tanda tangan atau tujuan, atau Format Dokumen berubah? Betulkan lalu terbitkan ulang sebagai versi baru.</p>
                                    <x-ui.button size="sm" variant="outline" icon="arrow-clockwise" x-on:click="buka = true; $nextTick(() => $refs.alasan.focus())">Perbaiki &amp; Terbitkan Ulang</x-ui.button>
                                </div>
                                <form x-show="buka" x-cloak wire:submit="terbitUlangNota" class="space-y-3 rounded-xl bg-slate-50 p-4 dark:bg-navy-950/50">
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <x-form.field label="Tanda tangan" for="ttdUlangId" error="ttdUlangId" required>
                                            <select id="ttdUlangId" wire:model="ttdUlangId" class="{{ $isian }}">
                                                @foreach ($daftarTtd as $ttd)
                                                    <option value="{{ $ttd->id }}">{{ $ttd->nama_ttd }} ({{ $ttd->jabatan }})</option>
                                                @endforeach
                                                {{-- Penandatangan yang sudah dinonaktifkan tetap tampil supaya pilihan sekarang tidak hilang. --}}
                                                @if ($nota->tandaTangan && ! $daftarTtd->contains('id', $nota->tanda_tangan_id))
                                                    <option value="{{ $nota->tanda_tangan_id }}" disabled>{{ $nota->tandaTangan->nama_ttd }} (tidak aktif)</option>
                                                @endif
                                            </select>
                                        </x-form.field>
                                        <x-form.field label="Kepada" for="kepadaUlang" error="kepadaUlang" required>
                                            <input id="kepadaUlang" type="text" maxlength="255" wire:model="kepadaUlang" class="{{ $isian }}">
                                        </x-form.field>
                                    </div>
                                    <x-form.field label="Alasan penerbitan ulang" for="alasanTerbitUlang" error="alasanTerbitUlang" required
                                        hint="Nomor dan tanggal tetap sama. Versi sekarang tetap tersimpan sebagai Diganti, dan perubahan penandatangan atau tujuan ikut tercatat.">
                                        <textarea id="alasanTerbitUlang" x-ref="alasan" wire:model="alasanTerbitUlang" rows="2" maxlength="500"
                                            placeholder="Contoh: salah pilih tanda tangan" class="{{ $isian }}"></textarea>
                                    </x-form.field>
                                    <div class="flex justify-end gap-2">
                                        <x-ui.button variant="ghost" x-on:click="buka = false">Batal</x-ui.button>
                                        <x-ui.button type="submit" icon="arrow-clockwise" loading="terbitUlangNota">Terbitkan Ulang</x-ui.button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                @elseif ($bolehTerbit)
                    <form wire:submit="terbitkanNota" class="space-y-5">
                        <x-form.field label="Nomor nota dinas" for="nomorInputan" error="nomorInputan" required>
                            {{-- Hanya nomor urut yang diisi; bagian lain mengikuti pola di menu Format Dokumen. --}}
                            <div class="flex flex-wrap items-center gap-1.5">
                                @foreach ($potonganNomor as $p)
                                    @if ($p['jenis'] === 'nomor')
                                        <input id="nomorInputan" type="text" inputmode="numeric" maxlength="3" placeholder="000" autocomplete="off" autofocus
                                            wire:model="nomorInputan" x-on:input="$el.value = $el.value.replace(/\D/g, '')"
                                            class="w-20 rounded-lg border-0 text-center text-sm font-semibold ring-2 ring-primary-300 focus:ring-primary-500 dark:bg-navy-800 dark:ring-primary-500/50">
                                    @elseif ($p['jenis'] === 'pemisah')
                                        <span class="text-lg text-slate-400" aria-hidden="true">/</span>
                                    @else
                                        <span title="Mengikuti pola nomor di menu Format Dokumen"
                                            class="cursor-not-allowed rounded-lg bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-500 ring-1 ring-slate-200 ring-inset select-none dark:bg-navy-800 dark:text-slate-400 dark:ring-navy-700">{{ $p['isi'] }}</span>
                                    @endif
                                @endforeach
                            </div>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                Menjadi <strong class="text-navy-900 dark:text-white" x-data
                                    x-text="@js($awalanNomor) + ($wire.nomorInputan || '…') + @js($akhiranNomor)">{{ $awalanNomor }}…{{ $akhiranNomor }}</strong>
                            </p>
                        </x-form.field>

                        <x-form.field label="Kepada" for="kepada" error="kepada" required hint="Diisi otomatis dari Perangkat Daerah yang mengajukan. Ubah bila tujuannya berbeda.">
                            <input id="kepada" type="text" maxlength="255" wire:model="kepada" class="{{ $isian }}">
                        </x-form.field>

                        <x-form.field label="Tanda tangan" for="tandaTanganId" error="tandaTanganId" required hint="Nomor dan tanda tangan ini otomatis masuk ke matriks.">
                            <select id="tandaTanganId" wire:model="tandaTanganId" class="{{ $isian }}">
                                <option value="">Pilih tanda tangan</option>
                                @foreach ($daftarTtd as $ttd)
                                    <option value="{{ $ttd->id }}">{{ $ttd->nama_ttd }} ({{ $ttd->jabatan }})</option>
                                @endforeach
                            </select>
                        </x-form.field>

                        @error('aksi')
                            <x-ui.alert type="danger">{{ $message }}</x-ui.alert>
                        @enderror

                        <div class="flex justify-end">
                            <x-ui.button type="submit" icon="file-earmark-check" loading="terbitkanNota">Terbitkan Nota Dinas</x-ui.button>
                        </div>
                    </form>
                @else
                    <x-ui.empty-state icon="file-earmark-text" title="Nota dinas belum terbit."
                        :description="$tahap === TahapNaskah::Final ? 'Menunggu Bagian Hukum menerbitkannya.' : 'Nota dinas diterbitkan setelah naskah disetujui menjadi final.'" />
                @endif
            </x-ui.card>

            <x-ui.card title="Tahapan Tanda Tangan" :description="$keteranganTahap" :class="$pemantau ? 'order-1' : ''">
                @if ($sudahTtd)
                    @include('v2.partials.tahapan-ttd', ['bolehKelola' => $bolehKelola])

                @else
                    <x-ui.empty-state icon="vector-pen" title="Belum dimulai." description="Paraf dan tanda tangan basah dicatat di sini setelah nota dinas terbit." />
                @endif
            </x-ui.card>

            @if ($bolehArsip)
                <x-ui.card title="Arsipkan ke JDIH" description="Tanda tangan sudah lengkap. Isi nomor dan tanggal penetapan, lalu unggah naskah yang sudah ditandatangani." class="order-2">
                    <form wire:submit="arsipkan" class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2">
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
                        @error('aksi')
                            <x-ui.alert type="danger">{{ $message }}</x-ui.alert>
                        @enderror
                        <div class="flex justify-end">
                            <x-ui.button type="submit" variant="success" icon="archive" loading="arsipkan,fileArsip">Arsipkan ke JDIH</x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endif

            @if ($bolehBukaKunci)
                {{-- Sengaja di bawah dan tertutup: ini jalan mundur, bukan langkah biasa. --}}
                <x-ui.card class="order-2" x-data="{ buka: false }">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0 flex-1 basis-64">
                            <h3 class="font-bold text-navy-900 dark:text-white">Isi naskah perlu diubah?</h3>
                            <p class="text-sm text-slate-500 dark:text-slate-400">Isi sudah terkunci. Untuk mengubahnya, naskah dikembalikan ke tahap Validasi Fasilitasi.</p>
                        </div>
                        <x-ui.button size="sm" variant="outline-danger" icon="unlock" x-show="! buka" x-on:click="buka = true; $nextTick(() => $refs.alasanBuka.focus())">Kembalikan ke Validasi Fasilitasi</x-ui.button>
                    </div>
                    <form x-show="buka" x-cloak wire:submit="bukaKunci" class="mt-4 space-y-3">
                        <x-ui.alert type="warning" icon="exclamation-triangle">
                            Naskah berpindah dari <strong>{{ $tahap->label() }}</strong> ke <strong>Validasi Fasilitasi</strong>. Giliran berikutnya: <strong>Verifikator</strong>.
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                <li>Isi naskah bisa diubah lagi dan matriks kembali berstatus draf.</li>
                                @if ($nota)
                                    <li>Catatan paraf dan tanda tangan yang sudah ditandai <strong>dihapus</strong> dan harus dicatat ulang.</li>
                                    <li>Nota dinas <strong>{{ $nota->nomor_nota }}</strong> tetap berlaku. Setelah disetujui lagi, naskah langsung masuk tahap Penandatanganan tanpa nota dinas baru.</li>
                                @endif
                            </ul>
                        </x-ui.alert>
                        <x-form.field label="Alasan" for="alasanBukaKunci" error="alasanBukaKunci" required>
                            <textarea id="alasanBukaKunci" x-ref="alasanBuka" wire:model="alasanBukaKunci" rows="2" placeholder="Contoh: koreksi dari Sekda pada Pasal 5" class="{{ $isian }}"></textarea>
                        </x-form.field>
                        <div class="flex justify-end gap-2">
                            <x-ui.button variant="ghost" x-on:click="buka = false">Batal</x-ui.button>
                            <x-ui.button type="submit" variant="danger" icon="unlock" loading="bukaKunci">Kembalikan ke Validasi Fasilitasi</x-ui.button>
                        </div>
                    </form>
                </x-ui.card>
            @endif

            <x-ui.card title="Riwayat" description="Catatan nota dinas, tanda tangan, dan perpindahan tahap naskah ini." class="order-3">
                <ol class="space-y-3">
                    @forelse ($riwayat as $r)
                        <li wire:key="riwayat-{{ $r->id }}" class="flex gap-3 text-sm">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $r->aksi === 'tahapan_batal' ? 'bg-rose-400' : (in_array($r->aksi, ['tahap', 'terbit_ulang_nota'], true) ? 'bg-primary-400' : 'bg-emerald-400') }}" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-slate-700 dark:text-slate-200">
                                    <strong class="text-navy-900 dark:text-white">{{ $r->user->nama_user ?? 'Sistem' }}</strong>
                                    @if ($r->aksi === 'tahapan')
                                        menandai {{ $r->sesudah }} selesai
                                    @elseif ($r->aksi === 'tahapan_tanggal')
                                        mengubah tanggal {{ $r->catatan }}: {{ tanggal($r->sebelum) }} <i class="bi bi-arrow-right" aria-hidden="true"></i> {{ tanggal($r->sesudah) }}
                                    @elseif ($r->aksi === 'tahapan_batal')
                                        membatalkan tanda {{ $r->sebelum }}
                                    @elseif ($r->aksi === 'terbit_ulang_nota')
                                        menerbitkan ulang nota dinas: {{ $r->sesudah }}
                                    @else
                                        memindahkan naskah: {{ $r->sebelum }} <i class="bi bi-arrow-right" aria-hidden="true"></i> {{ $r->sesudah }}
                                    @endif
                                </p>
                                @if ($r->catatan && $r->aksi !== 'tahapan_tanggal')
                                    <p class="mt-0.5 text-slate-500 dark:text-slate-400">{{ $r->catatan }}</p>
                                @endif
                                <time class="mt-0.5 block text-slate-400">{{ $r->created_at->translatedFormat('d M Y H:i') }}</time>
                            </div>
                        </li>
                    @empty
                        <li class="py-4 text-center text-sm text-slate-500">Belum ada catatan.</li>
                    @endforelse
                </ol>
            </x-ui.card>
        </div>

        <x-ui.card title="Naskah">
            <p class="font-semibold text-navy-900 dark:text-white">{{ $naskah->tentang }}</p>
            <div class="mt-2 flex flex-wrap gap-2">
                <x-ui.badge :color="$naskah->jenis === 'Keputusan Bupati' ? 'warning' : 'primary'">{{ $naskah->jenis }}</x-ui.badge>
                <x-ui.badge :color="$tahap->warna()">{{ $tahap->label() }}</x-ui.badge>
            </div>

            <dl class="mt-4 space-y-3 border-t border-slate-100 pt-4 text-sm dark:border-navy-800">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Perangkat Daerah</dt>
                    <dd class="mt-0.5 font-medium">{{ $naskah->pemrakarsa->perangkatDaerah->nama_perangkat_daerah ?? $naskah->pemrakarsa->nama_user ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Peneliti</dt>
                    <dd class="mt-0.5 font-medium">{{ $naskah->peneliti->nama_user ?? '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Nota Dinas</dt>
                    <dd class="mt-0.5 font-medium">
                        @if ($nota)
                            {{ $nota->nomor_nota }}
                            <span class="block font-normal text-slate-500 dark:text-slate-400">{{ tanggal($nota->tanggal_nota) }} · Kepada {{ $nota->kepada }}</span>
                        @else
                            Belum terbit
                        @endif
                    </dd>
                </div>
                @if ($naskah->dokumentasi_id)
                    <div>
                        <dt class="text-slate-500 dark:text-slate-400">Arsip</dt>
                        <dd class="mt-0.5"><x-ui.file-link :href="route('jdih.show', $naskah->dokumentasi_id)" label="Lihat di JDIH" /></dd>
                    </div>
                @endif
            </dl>

            <x-ui.button variant="outline" :icon="$lihatIsi ? 'eye-slash' : 'eye'" class="mt-5 w-full" wire:click="$toggle('lihatIsi')" loading="$toggle('lihatIsi')">
                {{ $lihatIsi ? 'Sembunyikan Isi Naskah' : 'Lihat Isi Naskah' }}
            </x-ui.button>
        </x-ui.card>
    </div>

    @if ($lihatIsi)
        {{-- Isi naskah muncul di bawah halaman, jadi layar digulir ke sini supaya terlihat tombolnya bekerja. --}}
        <section class="mt-6 scroll-mt-24" aria-label="Isi naskah" wire:key="isi-naskah"
            x-data x-init="$nextTick(() => $el.scrollIntoView({ behavior: 'smooth', block: 'start' }))">
            <div class="mx-auto mb-3 flex max-w-4xl flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="font-bold text-navy-900 dark:text-white">Isi Naskah</h2>
                    <p class="text-sm text-slate-500 dark:text-slate-400">Isi naskah yang ditandatangani. Hanya untuk dibaca.</p>
                </div>
                <x-ui.button size="sm" variant="outline" icon="eye-slash" wire:click="$set('lihatIsi', false)" loading="$set('lihatIsi', false)"
                    x-on:click="$root.parentElement.scrollIntoView({ behavior: 'smooth', block: 'start' })">Sembunyikan</x-ui.button>
            </div>
            @include('v2.partials.isi-naskah', ['blok' => $blokNaskah])
        </section>
    @endif
</div>
