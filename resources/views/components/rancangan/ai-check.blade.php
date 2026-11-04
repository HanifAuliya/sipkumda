{{--
    Status pemeriksaan kelengkapan berkas oleh AI di form pengajuan.
    <x-rancangan.ai-check target="rancangan, matrik, jalankanPrediksi" :gagal="$gagalCek" :sudah="$sudahDicek"
        :hasil="$hasilPrediksi" :catatan="$catatanKurang" />
--}}
@props(['target', 'gagal' => false, 'sudah' => false, 'hasil' => null, 'catatan' => []])

<div class="space-y-3">
    <div wire:loading.flex wire:target="{{ $target }}"
        class="items-center gap-3 rounded-xl bg-slate-50 px-4 py-3 ring-1 ring-slate-200 dark:bg-navy-950/40 dark:ring-navy-700">
        <x-ui.spinner class="size-5 text-primary-500" />
        <div class="text-sm">
            <p class="font-semibold text-slate-700 dark:text-slate-200">Memeriksa kelengkapan berkas...</p>
            <p class="text-slate-500">AI sedang menganalisis isi dokumen Anda.</p>
        </div>
    </div>

    @if ($gagal)
        <x-ui.alert type="info" icon="wifi-off" title="Pemeriksaan AI tidak tersedia.">
            Server AI tidak dapat dihubungi. Anda tetap bisa mengajukan, Admin akan memverifikasi secara manual.
        </x-ui.alert>
    @endif

    @if ($sudah && $hasil === 'Lengkap')
        <x-ui.alert type="success" title="Berkas terdeteksi lengkap.">
            Semua indikator dokumen terdeteksi oleh AI. Anda dapat langsung mengajukan.
        </x-ui.alert>
    @endif

    @if ($sudah && $hasil === 'Tidak Lengkap')
        <x-ui.alert type="warning" title="Berkas terdeteksi belum lengkap.">
            AI mendeteksi beberapa indikator yang belum terpenuhi. Anda <strong>tetap dapat mengajukan</strong>,
            namun Admin akan mencatat kekurangan ini dan mungkin meminta perbaikan.
            @if (count($catatan) > 0)
                <ul class="mt-2 list-disc space-y-0.5 pl-5">
                    @foreach ($catatan as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @endif
        </x-ui.alert>
    @endif
</div>
