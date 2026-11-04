@section('title', 'Nota Dinas')

<div>
    <x-ui.page-header title="Nota Dinas" description="Daftar nota dinas fasilitasi rancangan produk hukum" icon="file-text">
        @if ($bisaKelola)
            <x-slot:actions>
                <x-ui.button icon="file-earmark-plus" wire:click="openModalTambahNotaDinas" loading="openModalTambahNotaDinas">Tambah Nota Dinas</x-ui.button>
            </x-slot:actions>
        @endif
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.list-toolbar placeholder="Cari nomor nota, nomor rancangan, atau tentang..." :options="[5, 10, 20, 50]" />

        <x-ui.table>
            <x-slot:head>
                <th>Nomor Nota</th>
                <th>Rancangan</th>
                <th>Penanda Tangan</th>
                <th>Tanggal</th>
                <th class="text-right">Aksi</th>
            </x-slot:head>

            @forelse ($notaDinasList as $nota)
                @php $rancangan = $nota->fasilitasi?->rancangan; @endphp
                <tr wire:key="nota-{{ $nota->id }}">
                    <td class="font-semibold whitespace-nowrap text-navy-900 dark:text-white">{{ $nota->nomor_nota }}</td>
                    <td class="min-w-72">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium text-slate-700 dark:text-slate-200">{{ $rancangan->no_rancangan ?? '-' }}</span>
                            <x-ui.status-badge :value="$rancangan?->jenis_rancangan" />
                        </div>
                        <p class="mt-0.5 text-slate-500">{{ $rancangan->tentang ?? '-' }}</p>
                    </td>
                    <td class="min-w-40">{{ $nota->tandaTangan->nama_ttd ?? '-' }}</td>
                    <td class="whitespace-nowrap">{{ tanggal($nota->tanggal_nota) }}</td>
                    <td class="text-right whitespace-nowrap">
                        <x-ui.button variant="outline-primary" size="sm" icon="file-earmark-pdf"
                            x-on:click="showLoading('Menyiapkan PDF...'); $wire.generatePDF({{ $nota->id }})">Cetak</x-ui.button>
                        @if ($bisaKelola)
                            <x-ui.button variant="soft-danger" size="sm" square icon="trash" title="Hapus" data-nomor="{{ $nota->nomor_nota }}"
                                x-on:click="$confirm({ title: 'Hapus nota dinas?', text: `Nota ${$el.dataset.nomor} akan dihapus permanen.`, confirmText: 'Ya, hapus' })
                                    .then((ok) => ok && $wire.deleteNotaDinas({{ $nota->id }}))" />
                        @endif
                    </td>
                </tr>
            @empty
                <x-ui.empty-row colspan="5" icon="file-text" message="Belum ada nota dinas." />
            @endforelse
        </x-ui.table>

        <div class="mt-5">{{ $notaDinasList->links() }}</div>
    </x-ui.card>

    @if ($bisaKelola)
        <x-ui.modal id="modalTambahNota" title="Tambah Nota Dinas" description="Nota dinas dibuat untuk fasilitasi yang telah divalidasi."
            size="2xl" static on-close="resetForm">
            <form id="notaDinasForm" wire:submit="store" class="space-y-5">
                <x-form.field label="Fasilitasi" for="fasilitasiId" error="fasilitasiId" required>
                    <x-form.select id="fasilitasiId" wire:model.live="fasilitasiId" :disabled="$fasilitasiList->isEmpty()">
                        @if ($fasilitasiList->isEmpty())
                            <option value="">Tidak ada fasilitasi yang menunggu nota dinas</option>
                        @else
                            <option value="">Pilih fasilitasi</option>
                            @foreach ($fasilitasiList as $fasilitasi)
                                <option value="{{ $fasilitasi->id }}">{{ $fasilitasi->rancangan->no_rancangan }} — {{ Str::limit($fasilitasi->rancangan->tentang, 70) }}</option>
                            @endforeach
                        @endif
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Nomor Nota" for="nomorInputan" error="nomorInputan" required hint="Isi bagian nomor urut (maks. 3 digit).">
                    <div class="flex items-stretch overflow-hidden rounded-lg ring-1 ring-slate-200 focus-within:ring-2 focus-within:ring-primary-400 dark:ring-navy-700">
                        <span class="flex items-center bg-slate-50 px-3 font-mono text-sm text-slate-500 dark:bg-navy-800">180/</span>
                        <input id="nomorInputan" wire:model="nomorInputan" inputmode="numeric" maxlength="3" placeholder="###"
                            x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 3)"
                            class="w-20 min-w-0 flex-1 border-0 bg-white px-3 py-2.5 text-center font-mono text-sm focus:ring-0 dark:bg-navy-900">
                        <span class="flex items-center bg-slate-50 px-3 font-mono text-sm text-slate-500 dark:bg-navy-800">{{ $akhiranNomor }}</span>
                    </div>
                </x-form.field>

                <x-form.field label="Penanda Tangan" for="tandaTanganId" error="tandaTanganId" required>
                    <x-form.select id="tandaTanganId" wire:model="tandaTanganId">
                        <option value="" hidden>Pilih tanda tangan</option>
                        @foreach ($tandaTanganList as $ttd)
                            <option value="{{ $ttd->id }}">{{ $ttd->nama_ttd }} — {{ $ttd->jabatan }}</option>
                        @endforeach
                    </x-form.select>
                </x-form.field>

                <x-form.field label="Kepada" for="kepada" error="kepada" required hint="Contoh: Kepala Bagian Hukum Setda Kab. HST">
                    <x-form.input id="kepada" wire:model="kepada" placeholder="Penerima nota dinas" />
                </x-form.field>
            </form>

            <x-slot:footer>
                <x-ui.button variant="outline" x-on:click="close()">Batal</x-ui.button>
                <x-ui.button type="submit" form="notaDinasForm" icon="save" loading="store">Simpan</x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
