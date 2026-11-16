{{--
    Jendela-jendela layar kerja: konfirmasi perpindahan tahap (modalAksi), pratinjau PDF berkas, dan
    Ubah Judul. Dipisah dari layar-kerja.blade.php karena kompilator Blade Livewire gagal memproses
    satu templat yang terlalu besar. Memakai variabel yang sama dengan layar-kerja.
--}}
@php
    use App\Enums\TahapNaskah;
    use App\Models\NaskahBaris as B;
    use App\Models\NaskahBerkas;
    use App\Services\AlurNaskah as A;
    use App\Support\TemplateNaskah;
@endphp

    <x-ui.modal id="modalAksi" :title="$aksiModal['label'] ?? 'Konfirmasi'" size="lg">
        @error('aksi')
            <x-ui.alert color="danger">{{ $message }}</x-ui.alert>
        @enderror

        @if (isset($aksiModal['ke']))
            {{-- Setiap perpindahan menyebut asal, tujuan, dan pemegang giliran berikutnya. --}}
            <x-ui.alert :type="! empty($aksiModal['mundur']) ? 'warning' : 'primary'" :icon="! empty($aksiModal['mundur']) ? 'arrow-return-left' : 'arrow-right'" class="mb-4">
                Naskah berpindah dari <strong>{{ $tahap->label() }}</strong> ke <strong>{{ $aksiModal['ke']->label() }}</strong>.
                Giliran berikutnya: <strong>{{ $aksiModal['ke']->peran() }}</strong>.
            </x-ui.alert>
        @endif

        @if ($aksiTerpilih === 'ganti_peneliti')
            <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                Peneliti sekarang: <strong class="text-navy-900 dark:text-white">{{ $naskah->peneliti->nama_user ?? '-' }}</strong>.
                Tahap naskah tidak berubah. Peneliti baru langsung bisa membuka naskah ini, sedangkan peneliti lama tidak lagi.
            </p>
        @endif

        @if (! empty($aksiModal['peneliti']))
            {{-- Rancangan yang sedang dipegang peneliti terpilih tampil di bawahnya, supaya tugas terbagi rata. --}}
            <div x-data="{ pilih: '{{ $penelitiId }}' }" x-on:change="pilih = $event.target.value">
                <x-form.field :label="$aksiTerpilih === 'ganti_peneliti' ? 'Peneliti baru' : 'Peneliti'" for="penelitiId" error="penelitiId" required>
                    <x-form.select id="penelitiId" wire:model="penelitiId">
                        <option value="" hidden>Pilih peneliti</option>
                        @foreach ($peneliti as $orang)
                            <option value="{{ $orang->id }}">{{ $orang->nama_user }} · {{ $orang->beban_aktif }} naskah berjalan, {{ $orang->beban_menunggu }} menunggu dikerjakan</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>
                @foreach ($peneliti as $orang)
                    <div x-show="pilih === '{{ $orang->id }}'" x-cloak class="mb-4 rounded-lg bg-slate-50 px-3 py-2 dark:bg-navy-800/60">
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Rancangan yang dipegang {{ $orang->nama_user }}</p>
                        <div class="max-h-48 overflow-y-auto">
                            @include('v2.partials.naskah-peneliti', ['daftar' => $orang->naskahAktif])
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @php
            $isian = 'w-full rounded-lg border-0 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700';
        @endphp

        @if ($aksiTerpilih === 'setujui_berkas')
            {{-- Daftar periksa Admin: semua butir wajib dicentang sebelum berkas dinyatakan lengkap. --}}
            <div class="mb-4">
                <p class="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Daftar periksa <span class="text-rose-500">*</span></p>
                @php $notaPd = $naskah->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS_PD)->where('aktif', true)->latest('id')->first(); @endphp
                @if ($notaPd)
                    {{-- Tab baru, supaya daftar periksa ini tetap terbuka sambil membaca nota dinasnya. --}}
                    <a href="{{ route('naskah.berkas', [$naskah, $notaPd, 'lihat' => 1]) }}" target="_blank" rel="noopener"
                        class="mb-2 inline-flex items-center gap-1.5 text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">
                        <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Lihat Nota Dinas PD (tab baru)
                    </a>
                @endif
                <div class="space-y-2">
                    @foreach (\App\Livewire\V2\LayarKerja::PERIKSA_BERKAS as $kunciPeriksa => $teksPeriksa)
                        <label class="flex items-start gap-2.5 text-sm text-slate-700 dark:text-slate-200" wire:key="periksa-{{ $kunciPeriksa }}">
                            <input type="checkbox" value="{{ $kunciPeriksa }}" wire:model.live="periksaBerkas" class="mt-0.5 rounded text-primary-600 focus:ring-primary-500">
                            {{ $teksPeriksa }}
                        </label>
                    @endforeach
                </div>
                @error('periksaBerkas')
                    <p class="mt-2 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
            </div>
        @endif

        @if ($aksiTerpilih === 'ajukan_fasilitasi')
            {{-- Naskah hasil perbaikan adalah naskah yang dibuat dari isi matriks; tidak perlu mengunggah file. --}}
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2.5 dark:bg-navy-800/60">
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Naskah hasil perbaikan dibuat otomatis dari matriks yang sudah memuat seluruh hasil penelitian; tidak perlu mengunggah file.
                    Periksa naskahnya, lalu Lanjutkan. Peneliti mengeceknya sebelum nota dinas terbit.
                </p>
                <x-ui.button size="sm" variant="outline" icon="eye" :data-url="route('naskah.cetak', [$naskah, 'naskah-pdf', 'lihat' => 1])" data-judul="Naskah hasil perbaikan"
                    x-on:click="$dispatch('lihat-pdf', { url: $el.dataset.url, judul: $el.dataset.judul })">Lihat naskah</x-ui.button>
            </div>
        @endif

        @if ($barisUntukPd->isNotEmpty())
            <div class="mb-4">
                <p class="mb-1.5 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ $barisUntukPd->count() }} baris dikirim untuk diperbaiki PD:</p>
                <ul class="space-y-1.5 text-sm text-slate-600 dark:text-slate-300">
                    @foreach ($barisUntukPd as $bp)
                        <li class="rounded-lg bg-orange-50 px-3 py-2 dark:bg-orange-500/10" wire:key="untuk-pd-{{ $bp->id }}">
                            <strong class="text-orange-800 dark:text-orange-200">{{ $bp->label ?: \App\Support\TemplateNaskah::BAGIAN[$bp->bagian] }}</strong>
                            @if (filled($bp->keterangan)) · {{ \Illuminate\Support\Str::limit($bp->keterangan, 120) }} @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (isset($aksiModal['ke']) && empty($aksiModal['mundur']) && $peringatan)
            {{-- Hanya mengingatkan: keputusan tetap di tangan pengguna. --}}
            <x-ui.alert type="warning" icon="exclamation-triangle-fill" class="mb-4">
                <p class="font-semibold">Masih ada {{ count($peringatan) }} hal yang perlu diperiksa:</p>
                <ul class="mt-1 list-disc pl-5">
                    @foreach (array_slice($peringatan, 0, 5) as $p)
                        <li>{{ $p['pesan'] }}</li>
                    @endforeach
                </ul>
                @if (count($peringatan) > 5)
                    <p class="mt-1">dan {{ count($peringatan) - 5 }} lainnya.</p>
                @endif
            </x-ui.alert>
        @endif

        @if (! empty($aksiModal['ttd_peneliti']))
            {{-- Langkah terakhir Peneliti di fasilitasi: menandatangani matriks. --}}
            <div class="mb-4">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Tanda Tangan Matriks <span class="text-rose-500">*</span></p>
                <p class="mb-3 text-sm text-slate-500 dark:text-slate-400">Dicetak di matriks di atas nama dan NIP Anda setelah nota dinas terbit.</p>
                <x-ttd.pilih sumber="sumberTtd" langsung="ttdLangsung" simpan="simpanTtd" error="ttd" :src="auth()->user()->ttdUrl()" />
            </div>
        @endif

        <x-form.field :label="! empty($aksiModal['catatan']) ? 'Alasan' : 'Catatan (opsional)'" for="catatan" error="catatan" :required="! empty($aksiModal['catatan'])" class="mt-3">
            <textarea id="catatan" wire:model="catatan" rows="3" class="{{ $isian }}"></textarea>
        </x-form.field>

        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button icon="check2" wire:click="jalankanAksi" loading="jalankanAksi"
                :disabled="$aksiTerpilih === 'setujui_berkas' && count($periksaBerkas) < count(\App\Livewire\V2\LayarKerja::PERIKSA_BERKAS)">Lanjutkan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Pratinjau PDF berkas tanpa mengunduh: $dispatch('lihat-pdf', { url, judul }) --}}
    <div x-data="{ url: '', judul: '' }" x-on:lihat-pdf.window="url = $event.detail.url; judul = $event.detail.judul; openModal('modalPdf')">
        <x-ui.modal id="modalPdf" title="Pratinjau berkas" size="5xl">
            <p class="-mt-2 mb-3 truncate text-sm text-slate-500 dark:text-slate-400" x-text="judul"></p>
            <iframe x-bind:src="url" class="h-[70vh] w-full rounded-lg bg-slate-100 ring-1 ring-slate-200 dark:bg-navy-800 dark:ring-navy-700" title="Pratinjau PDF"></iframe>
            <x-slot:footer>
                <a x-bind:href="url" target="_blank" rel="noopener" class="inline-flex min-h-10 items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 ring-inset hover:bg-slate-50 dark:text-slate-200 dark:ring-navy-700 dark:hover:bg-navy-800">
                    <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Buka di tab baru
                </a>
                <x-ui.button variant="ghost" x-on:click="close()">Tutup</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    </div>

    {{-- Ubah Judul: judul pengajuan, judul naskah, dan "Menetapkan" Peraturan Bupati diubah bersama. --}}
    <x-ui.modal id="modalJudul" title="Ubah Judul" size="lg"
        description="Judul ini dipakai di daftar naskah, judul naskah, bunyi Menetapkan, nota dinas, dan JDIH.">
        <form id="formJudul" wire:submit="ubahJudul" class="space-y-3">
            <x-form.field label="Tentang (Judul Rancangan)" for="judulBaru" error="tentang" required
                :hint="$tahap === TahapNaskah::Draf ? null : 'Perubahan tercatat sebagai rumusan penyempurnaan pada baris Judul' . ($naskah->jenis === 'Peraturan Bupati' ? ' dan Diktum.' : '.')">
                <x-form.judul id="judulBaru" wire:model="judulBaru" />
            </x-form.field>
        </form>
        <x-slot:footer>
            <x-ui.button variant="ghost" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="formJudul" icon="check2" loading="ubahJudul">Simpan Judul</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
