@php
    use App\Enums\TahapNaskah;

    $keteranganTahap = match ($tahap) {
        TahapNaskah::Penandatanganan => 'Catat setiap paraf dan tanda tangan basah setelah selesai. Tanggal masih bisa dibetulkan bila salah pilih.',
        TahapNaskah::Arsip => 'Keempat tahapan sudah tercatat. Naskah menunggu diarsipkan Bagian Hukum; tanggal masih bisa dibetulkan.',
        TahapNaskah::Selesai => 'Naskah sudah diarsipkan ke JDIH. Catatan tanda tangan tidak bisa diubah lagi.',
        default => $tahap->keterangan(),
    };
@endphp

<div class="mx-auto max-w-6xl">
    <x-ui.page-header title="Pantau Tanda Tangan" :description="$naskah->nomor . ' · ' . $naskah->jenis" icon="vector-pen" :back="route('v2.daftar', 'penandatanganan')" />

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Tahapan Tanda Tangan" :description="$keteranganTahap">
                @include('v2.partials.tahapan-ttd', ['bolehKelola' => $bolehKelola])
            </x-ui.card>

            <x-ui.card title="Riwayat" description="Catatan tanda tangan dan perpindahan tahap naskah ini.">
                <ol class="space-y-3">
                    @forelse ($riwayat as $r)
                        <li wire:key="riwayat-{{ $r->id }}" class="flex gap-3 text-sm">
                            <span class="mt-1.5 size-2 shrink-0 rounded-full {{ $r->aksi === 'tahapan_batal' ? 'bg-rose-400' : ($r->aksi === 'tahap' ? 'bg-primary-400' : 'bg-emerald-400') }}" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-slate-700 dark:text-slate-200">
                                    <strong class="text-navy-900 dark:text-white">{{ $r->user->nama_user ?? 'Sistem' }}</strong>
                                    @if ($r->aksi === 'tahapan')
                                        menandai {{ $r->sesudah }} selesai
                                    @elseif ($r->aksi === 'tahapan_tanggal')
                                        mengubah tanggal {{ $r->catatan }}: {{ tanggal($r->sebelum) }} <i class="bi bi-arrow-right" aria-hidden="true"></i> {{ tanggal($r->sesudah) }}
                                    @elseif ($r->aksi === 'tahapan_batal')
                                        membatalkan tanda {{ $r->sebelum }}
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

            <x-ui.button variant="outline" :icon="$lihatIsi ? 'eye-slash' : 'eye'" class="mt-5 w-full" wire:click="$toggle('lihatIsi')">
                {{ $lihatIsi ? 'Sembunyikan Isi Naskah' : 'Lihat Isi Naskah' }}
            </x-ui.button>
        </x-ui.card>
    </div>

    @if ($lihatIsi)
        <section class="mt-6" aria-label="Isi naskah">
            <p class="mx-auto mb-3 max-w-4xl text-sm text-slate-500 dark:text-slate-400">Isi naskah yang ditandatangani. Hanya untuk dibaca.</p>
            @include('v2.partials.isi-naskah', ['blok' => $blokNaskah])
        </section>
    @endif
</div>
