@section('title', 'Kelola TTD')

@php
    $urlPeneliti = fn ($u) => $u->file_ttd
        ? route('kelola-ttd.peneliti', ['user' => $u->id, 'v' => substr(pathinfo($u->file_ttd, PATHINFO_FILENAME), 0, 12)])
        : null;
@endphp

<div>
    <x-ui.page-header title="Kelola Tanda Tangan" icon="pen"
        description="Pemiliknya mengatur sendiri di Profil. Di sini Admin membantu mengunggah atau mengganti, dan mencatat Kabag lama atau Plt.">
        @if ($tab === 'kabag')
            <x-slot:actions>
                <x-ui.button icon="plus-lg" wire:click="openModalTambah" loading="openModalTambah">Tambah Penanda Tangan</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.tabs class="mb-5">
            <x-ui.tab :active="$tab === 'kabag'" icon="person-badge" :count="$jumlah['kabag']" wire:click="$set('tab', 'kabag')">Kepala Bagian Hukum</x-ui.tab>
            <x-ui.tab :active="$tab === 'peneliti'" icon="person-gear" :count="$jumlah['peneliti']" wire:click="$set('tab', 'peneliti')">Peneliti</x-ui.tab>
        </x-ui.tabs>

        @if ($tab === 'kabag')
            <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                Dicetak di nota dinas dan di kolom Kepala Bagian Hukum pada matriks. Yang terhubung ke akun Verifikator diatur pemiliknya di Profil.
            </p>
            <x-ui.list-toolbar placeholder="Cari nama, NIP, atau pangkat..." :options="[5, 10, 20, 50]" />

            <x-ui.table>
                <x-slot:head>
                    <th>Penanda Tangan</th>
                    <th class="max-sm:hidden">Tanda Tangan</th>
                    <th class="max-sm:hidden">Akun</th>
                    <th class="max-sm:hidden">Status</th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>

                @forelse ($ttdList as $ttd)
                    @php $gambar = private_file_url('tanda_tangan/ttd', $ttd->file_ttd); @endphp
                    <tr wire:key="ttd-{{ $ttd->id }}">
                        <td class="sm:min-w-56">
                            <p class="sel-utama">{{ $ttd->nama_ttd }}</p>
                            <p class="sel-kedua">{{ $ttd->jabatan ?: '-' }} · NIP {{ $ttd->nip_ttd ?: '-' }}</p>
                            <p class="sel-kedua">{{ $ttd->naskah_nota_dinas_count }} nota dinas</p>
                            <div class="mt-1.5 sm:hidden"><x-ui.badge :color="$ttd->status === 'Aktif' ? 'success' : 'slate'">{{ $ttd->status }}</x-ui.badge></div>
                        </td>
                        <td class="max-sm:hidden">
                            @if ($gambar)
                                <a href="{{ $gambar }}" target="_blank" title="Buka gambar"
                                    class="inline-flex h-12 w-20 items-center justify-center rounded-lg bg-white p-1 ring-1 ring-slate-200 transition hover:ring-primary-400 dark:ring-navy-700">
                                    <img src="{{ $gambar }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy"
                                        x-data x-on:error="$el.replaceWith(Object.assign(document.createElement('i'), { className: 'bi bi-image text-xl text-slate-300', title: 'Gambar tidak ditemukan' }))">
                                </a>
                            @else
                                <span class="text-slate-400">Belum ada</span>
                            @endif
                        </td>
                        <td class="max-sm:hidden">
                            @if ($ttd->pemilik)
                                <span class="inline-flex items-center gap-1.5 text-sm"><i class="bi bi-person-check text-emerald-500" aria-hidden="true"></i>{{ $ttd->pemilik->nama_user }}</span>
                            @else
                                <span class="sel-kedua">Tanpa akun</span>
                            @endif
                        </td>
                        <td class="max-sm:hidden"><x-ui.badge :color="$ttd->status === 'Aktif' ? 'success' : 'slate'">{{ $ttd->status }}</x-ui.badge></td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <x-ui.button variant="outline" size="sm" icon="pencil" class="max-sm:size-10 max-sm:px-0" title="Ubah" aria-label="Ubah tanda tangan {{ $ttd->nama_ttd }}"
                                    wire:click="openModalEdit({{ $ttd->id }})" loading="openModalEdit({{ $ttd->id }})"><span class="max-sm:hidden">Ubah</span></x-ui.button>
                                @if (! $ttd->naskah_nota_dinas_count)
                                    <x-ui.button variant="soft-danger" size="sm" icon="trash" class="max-sm:size-10 max-sm:px-0" title="Hapus" aria-label="Hapus tanda tangan {{ $ttd->nama_ttd }}" data-nama="{{ $ttd->nama_ttd }}"
                                        x-on:click="$confirm({ title: 'Hapus tanda tangan?', text: `Tanda tangan ${$el.dataset.nama} akan dihapus permanen.`, confirmText: 'Ya, hapus' })
                                            .then((ok) => ok && $wire.delete({{ $ttd->id }}))"><span class="max-sm:hidden">Hapus</span></x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="5" icon="pen" message="Belum ada penanda tangan. Kepala Bagian Hukum bisa mengunggahnya sendiri di Profil." />
                @endforelse
            </x-ui.table>

            <div class="mt-5">{{ $ttdList->links() }}</div>
        @else
            <p class="mb-4 text-sm text-slate-600 dark:text-slate-300">
                Dicetak di matriks naskah yang ditelitinya. Peneliti mengunggahnya sendiri di Profil; bantu di sini bila perlu.
            </p>
            <x-ui.list-toolbar placeholder="Cari nama atau NIP..." :options="[5, 10, 20, 50]" />

            <x-ui.table>
                <x-slot:head>
                    <th>Peneliti</th>
                    <th class="max-sm:hidden">Tanda Tangan</th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>

                @forelse ($penelitiList as $p)
                    @php $gambar = $urlPeneliti($p); @endphp
                    <tr wire:key="peneliti-{{ $p->id }}">
                        <td class="sm:min-w-56">
                            <p class="sel-utama">{{ $p->nama_user }}</p>
                            <p class="sel-kedua">NIP {{ $p->NIP ?: '-' }}</p>
                            @unless ($gambar)
                                <div class="mt-1.5"><x-ui.badge color="warning" icon="exclamation-circle">Belum ada tanda tangan</x-ui.badge></div>
                            @endunless
                        </td>
                        <td class="max-sm:hidden">
                            @if ($gambar)
                                <a href="{{ $gambar }}" target="_blank" title="Buka gambar"
                                    class="inline-flex h-12 w-20 items-center justify-center rounded-lg bg-white p-1 ring-1 ring-slate-200 transition hover:ring-primary-400 dark:ring-navy-700">
                                    <img src="{{ $gambar }}" alt="" class="max-h-full max-w-full object-contain" loading="lazy">
                                </a>
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <x-ui.button variant="outline" size="sm" :icon="$gambar ? 'arrow-repeat' : 'upload'" class="max-sm:size-10 max-sm:px-0"
                                    :title="$gambar ? 'Ganti' : 'Unggah'" aria-label="{{ $gambar ? 'Ganti' : 'Unggah' }} tanda tangan {{ $p->nama_user }}"
                                    wire:click="bukaPeneliti({{ $p->id }})" loading="bukaPeneliti({{ $p->id }})"><span class="max-sm:hidden">{{ $gambar ? 'Ganti' : 'Unggah' }}</span></x-ui.button>
                                @if ($gambar)
                                    <x-ui.button variant="soft-danger" size="sm" icon="trash" class="max-sm:size-10 max-sm:px-0" title="Hapus" aria-label="Hapus tanda tangan {{ $p->nama_user }}" data-nama="{{ $p->nama_user }}"
                                        x-on:click="$confirm({ title: 'Hapus tanda tangan?', text: `Tanda tangan ${$el.dataset.nama} hilang dari matriks yang dicetak berikutnya.`, confirmText: 'Ya, hapus' })
                                            .then((ok) => ok && $wire.hapusPeneliti({{ $p->id }}))"><span class="max-sm:hidden">Hapus</span></x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="3" icon="person-gear" message="Belum ada akun Peneliti." />
                @endforelse
            </x-ui.table>

            <div class="mt-5">{{ $penelitiList->links() }}</div>
        @endif
    </x-ui.card>

    {{-- Tambah / ubah penanda tangan Kepala Bagian Hukum --}}
    <x-ui.modal id="modalTtd" :title="$editId ? 'Ubah Penanda Tangan' : 'Tambah Penanda Tangan'"
        description="Dicetak pada nota dinas dan matriks, pastikan penulisannya benar." size="2xl" static on-close="resetForm">
        <form id="ttdForm" wire:submit="save" class="space-y-5">
            <x-form.field label="Akun Verifikator" for="user_id_ttd" error="user_id"
                hint="Hubungkan ke akun Kepala Bagian Hukum supaya ia bisa mengaturnya sendiri di Profil. Kosongkan untuk Kabag lama atau Plt/Plh.">
                <x-form.select id="user_id_ttd" wire:model="user_id">
                    <option value="">Tanpa akun</option>
                    @foreach ($akunTersedia as $akun)
                        <option value="{{ $akun->id }}">{{ $akun->nama_user }}</option>
                    @endforeach
                </x-form.select>
            </x-form.field>

            <x-form.field label="Nama Lengkap" for="nama_ttd" error="nama_ttd" required hint="Seperti tercetak, contoh: TAUFIK RAHMAN, S.H.">
                <x-form.input id="nama_ttd" wire:model="nama_ttd" placeholder="Nama penanda tangan" />
            </x-form.field>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-form.field label="NIP" for="nip_ttd" error="nip_ttd" required hint="18 digit angka.">
                    <x-form.input id="nip_ttd" wire:model="nip_ttd" inputmode="numeric" placeholder="19xxxxxxxxxxxxxxxx" />
                </x-form.field>
                <x-form.field label="Pangkat/Golongan" for="jabatan" error="jabatan" required hint="Contoh: Pembina Tk. I">
                    <x-form.input id="jabatan" wire:model="jabatan" placeholder="Pangkat/golongan" />
                </x-form.field>
            </div>

            <x-form.field label="Status" for="status_ttd" error="status" required hint="Yang tidak aktif tidak bisa dipilih saat menerbitkan nota dinas.">
                <x-form.select id="status_ttd" wire:model="status">
                    <option value="Aktif">Aktif</option>
                    <option value="Tidak Aktif">Tidak Aktif</option>
                </x-form.select>
            </x-form.field>

            <div class="grid gap-5 sm:grid-cols-[1fr_auto]">
                <x-form.upload model="file_ttd" :file="$file_ttd" :label="$existingFile ? 'Ganti Tanda Tangan' : 'Gambar Tanda Tangan'" accept="image/png,image/jpeg"
                    :required="! $existingFile" :hint="($existingFile ? 'Kosongkan jika tidak diganti. ' : '') . 'PNG transparan atau JPG berlatar putih, maks. 2MB. Tepi kosong dipangkas otomatis.'" />

                @php
                    $pratinjau = $file_ttd && ! $errors->has('file_ttd') && method_exists($file_ttd, 'temporaryUrl')
                        ? rescue(fn () => $file_ttd->temporaryUrl(), null, false)
                        : private_file_url('tanda_tangan/ttd', $existingFile);
                @endphp
                @if ($pratinjau)
                    <figure class="text-center">
                        <div class="flex h-24 w-32 items-center justify-center rounded-lg bg-white p-2 ring-1 ring-slate-200 dark:ring-navy-700">
                            <img src="{{ $pratinjau }}" alt="" class="max-h-full max-w-full object-contain"
                                x-data x-on:error="$el.replaceWith(Object.assign(document.createElement('i'), { className: 'bi bi-image text-2xl text-slate-300', title: 'Gambar tidak ditemukan' }))">
                        </div>
                        <figcaption class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $file_ttd ? 'Pratinjau baru' : 'Saat ini' }}</figcaption>
                    </figure>
                @endif
            </div>
            <x-ui.loading target="file_ttd">Mengunggah file...</x-ui.loading>

            @if ($file_ttd)
                <x-form.field label="Kata Sandi Anda" for="sandiAdminKabag" error="sandiAdmin" required
                    hint="Mengganti gambar tanda tangan orang lain perlu kata sandi Anda.">
                    <x-form.password id="sandiAdminKabag" name="sandiAdmin" wire:model="sandiAdmin" autocomplete="current-password" />
                </x-form.field>
            @endif
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="ttdForm" icon="save" loading="save">{{ $editId ? 'Simpan Perubahan' : 'Simpan' }}</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>

    {{-- Unggah / ganti tanda tangan peneliti --}}
    <x-ui.modal id="modalTtdPeneliti" :title="'Tanda Tangan ' . ($penelitiDipilih->nama_user ?? 'Peneliti')"
        description="Dicetak di matriks naskah yang ditelitinya, di atas nama dan NIP-nya." size="lg" static on-close="resetPeneliti">
        <form id="ttdPenelitiForm" wire:submit="simpanPeneliti" class="space-y-5">
            <x-ttd.pratinjau :src="$gambarPeneliti && ! $errors->has('gambarPeneliti') ? rescue(fn () => $gambarPeneliti->temporaryUrl(), null, false) : ($penelitiDipilih ? $urlPeneliti($penelitiDipilih) : null)"
                memuat="gambarPeneliti, simpanPeneliti" />

            <div>
                <label class="inline-flex min-h-10 cursor-pointer items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200 transition ring-inset hover:bg-slate-50 has-focus-visible:outline-2 has-focus-visible:outline-primary-400 dark:text-slate-200 dark:ring-navy-700 dark:hover:bg-navy-800">
                    <i class="bi bi-upload" aria-hidden="true"></i> Pilih Gambar
                    <input type="file" wire:model="gambarPeneliti" accept="image/png,image/jpeg" class="sr-only" aria-label="Pilih gambar tanda tangan">
                </label>
                @error('gambarPeneliti')
                    <p class="mt-2 text-sm font-medium text-rose-600 dark:text-rose-300">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">PNG transparan atau JPG berlatar putih, maks. 2MB. Tepi kosong dipangkas otomatis.</p>
            </div>

            <x-form.field label="Kata Sandi Anda" for="sandiAdminPeneliti" error="sandiAdmin" required
                hint="Mengganti gambar tanda tangan orang lain perlu kata sandi Anda.">
                <x-form.password id="sandiAdminPeneliti" name="sandiAdmin" wire:model="sandiAdmin" autocomplete="current-password" />
            </x-form.field>
        </form>

        <x-slot:footer>
            <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
            <x-ui.button type="submit" form="ttdPenelitiForm" icon="save" loading="simpanPeneliti">Simpan</x-ui.button>
        </x-slot:footer>
    </x-ui.modal>
</div>
