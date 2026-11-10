@php
    use App\Models\PesanWa;
    use App\Support\Wa\AntreanWa;
    use App\Support\Wa\NomorWa;

    $mode = $status['mode'];
    // QR berlaku sekitar 30 detik dan login dideteksi dari status, jadi halaman diperbarui lebih sering saat menunggu scan.
    $poll = $mode === 'gowa' && $status['gateway'] && ! $status['masuk'] ? '5s' : '30s';
    $kotakAngka = 'rounded-xl bg-slate-50 px-4 py-3 dark:bg-navy-800/60';
@endphp

<div class="mx-auto max-w-6xl">
    {{-- Diberi key agar elemen polling diganti saat intervalnya berubah. --}}
    <div wire:poll.{{ $poll }} wire:key="poll-{{ $poll }}" class="hidden"></div>

    <x-ui.page-header title="WhatsApp" description="Notifikasi naskah lewat WhatsApp nomor kantor" icon="whatsapp" />

    @if ($mode === 'log')
        <x-ui.alert type="info" class="mb-6" title="Mode uji.">
            Pesan belum benar-benar dikirim, hanya dicatat di <code>storage/logs/laravel.log</code>.
            Untuk mengirim sungguhan, jalankan program GOWA lalu isi <code>WA_DRIVER=gowa</code> di <code>.env</code> (lihat docs/DEPLOY.md).
        </x-ui.alert>
    @endif

    @if ($putus && $status['masuk'] === false && ($hitung[PesanWa::MENUNGGU] ?? 0) > 0)
        <x-ui.alert type="warning" class="mb-6" title="Pesan tertunda.">
            {{ $hitung[PesanWa::MENUNGGU] }} pesan menunggu sambungan WhatsApp. Pesan otomatis terkirim begitu nomor kantor tersambung lagi.
        </x-ui.alert>
    @endif

    <div class="grid items-start gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-ui.card title="Sambungan Nomor Kantor">
                @if ($mode === 'gowa' && ! $status['gateway'])
                    <div class="flex items-start gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-rose-50 text-xl text-rose-600 dark:bg-rose-500/10 dark:text-rose-300"><i class="bi bi-x-lg" aria-hidden="true"></i></span>
                        <div class="text-sm">
                            <p class="font-semibold text-navy-900 dark:text-white">Program WhatsApp belum berjalan</p>
                            <p class="mt-1 text-slate-600 dark:text-slate-300">{{ $status['galat'] }}</p>
                            <p class="mt-2 text-slate-500 dark:text-slate-400">Jalankan GOWA di server (lihat docs/DEPLOY.md bagian WhatsApp). Halaman ini memeriksa ulang setiap 30 detik.</p>
                        </div>
                    </div>
                @elseif ($status['masuk'])
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-50 text-xl text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-300"><i class="bi bi-check-lg" aria-hidden="true"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-navy-900 dark:text-white">{{ $mode === 'log' ? 'Mode uji aktif' : 'Tersambung' }}</p>
                            <p class="text-sm text-slate-500 dark:text-slate-400">
                                {{ $status['nomor'] ? 'Nomor ' . NomorWa::tampil($status['nomor']) : 'Pesan dicatat di log.' }}
                            </p>
                        </div>
                        @if ($mode === 'gowa')
                            <div class="flex flex-wrap gap-2">
                                <x-ui.button variant="outline" size="sm" icon="arrow-repeat" wire:click="sambungUlang" loading="sambungUlang">Sambung Ulang</x-ui.button>
                                <x-ui.button variant="outline-danger" size="sm" icon="box-arrow-right" wire:click="putuskan" loading="putuskan"
                                    wire:confirm="Putuskan nomor ini? Notifikasi WhatsApp berhenti sampai nomor disambungkan lagi dengan scan QR.">Putuskan</x-ui.button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="grid items-center gap-6 sm:grid-cols-[auto_1fr]">
                        <div class="mx-auto grid size-56 place-items-center rounded-xl bg-white p-2 ring-1 ring-slate-200">
                            @if ($qr)
                                <img src="{{ $qr['gambar'] }}" alt="QR untuk menyambungkan WhatsApp" class="size-full">
                            @else
                                <x-ui.spinner class="size-8 text-primary-500" />
                            @endif
                        </div>
                        <div class="text-sm">
                            <p class="font-semibold text-navy-900 dark:text-white">Sambungkan nomor kantor</p>
                            <ol class="mt-2 list-decimal space-y-1 pl-5 text-slate-600 dark:text-slate-300">
                                <li>Buka WhatsApp di HP kantor.</li>
                                <li>Ketuk <strong>Setelan</strong> (iPhone) atau menu <strong>⋮</strong> (Android), lalu <strong>Perangkat tertaut</strong>.</li>
                                <li>Ketuk <strong>Tautkan perangkat</strong>, lalu arahkan kamera ke QR ini.</li>
                            </ol>
                            <p class="mt-3 text-slate-500 dark:text-slate-400">QR berganti otomatis. Setelah tersambung, HP tidak perlu menyala terus, tetapi buka WhatsApp di HP itu paling tidak seminggu sekali.</p>
                        </div>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card title="Kirim Pesan Uji" description="Langsung dikirim, untuk memastikan sambungannya berjalan.">
                <form wire:submit="kirimUji" class="flex flex-col gap-3 sm:flex-row sm:items-start">
                    <x-form.field label="Nomor tujuan" for="nomorUji" error="nomorUji" class="flex-1">
                        <x-form.input id="nomorUji" wire:model="nomorUji" icon="phone" inputmode="tel" placeholder="{{ NomorWa::CONTOH }}" />
                    </x-form.field>
                    <x-ui.button type="submit" icon="send" class="sm:mt-7" loading="kirimUji">Kirim</x-ui.button>
                </form>
            </x-ui.card>
        </div>

        <div class="space-y-6">
            <x-ui.card title="Yang Dikirim ke WhatsApp">
                <fieldset class="space-y-2">
                    <legend class="sr-only">Cakupan notifikasi WhatsApp</legend>
                    @foreach ([AntreanWa::CAKUPAN_TINDAKAN => ['Perlu tindakan saja', 'Giliran mengerjakan dan naskah yang dikembalikan. Kabar lain cukup di lonceng. Disarankan.'], AntreanWa::CAKUPAN_SEMUA => ['Semua notifikasi', 'Termasuk kabar posisi naskah. Pesan lebih banyak.']] as $nilai => [$label, $keterangan])
                        <label @class([
                            'flex cursor-pointer gap-3 rounded-xl p-3 ring-1 ring-inset transition',
                            'bg-primary-50/60 ring-primary-300 dark:bg-primary-500/10 dark:ring-primary-500/40' => $cakupan === $nilai,
                            'ring-slate-200 hover:bg-slate-50 dark:ring-navy-700 dark:hover:bg-navy-800' => $cakupan !== $nilai,
                        ])>
                            <input type="radio" wire:model.live="cakupan" value="{{ $nilai }}" class="mt-0.5 text-primary-600 focus:ring-primary-300">
                            <span class="text-sm">
                                <span class="block font-semibold text-navy-900 dark:text-white">{{ $label }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ $keterangan }}</span>
                            </span>
                        </label>
                    @endforeach
                </fieldset>
                <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
                    Hanya ke pengguna yang mengisi nomor WhatsApp dan tidak mematikannya di Profil.
                    @if ($tanpaNomor)
                        {{ $tanpaNomor }} pengguna belum punya nomor.
                    @endif
                </p>
            </x-ui.card>

            <x-ui.card title="Antrean">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="{{ $kotakAngka }}"><p class="text-slate-500 dark:text-slate-400">Menunggu</p><p class="text-xl font-bold text-orange-600 dark:text-orange-300">{{ $hitung[PesanWa::MENUNGGU] ?? 0 }}</p></div>
                    <div class="{{ $kotakAngka }}"><p class="text-slate-500 dark:text-slate-400">Terkirim hari ini</p><p class="text-xl font-bold text-emerald-600 dark:text-emerald-300">{{ $terkirimHariIni }}</p></div>
                    <div class="{{ $kotakAngka }}"><p class="text-slate-500 dark:text-slate-400">Gagal</p><p class="text-xl font-bold text-rose-600 dark:text-rose-300">{{ $hitung[PesanWa::GAGAL] ?? 0 }}</p></div>
                    <div class="{{ $kotakAngka }}"><p class="text-slate-500 dark:text-slate-400">Dilewati</p><p class="text-xl font-bold text-slate-600 dark:text-slate-300">{{ $hitung[PesanWa::DILEWATI] ?? 0 }}</p></div>
                </div>
                @if (($hitung[PesanWa::MENUNGGU] ?? 0) > 0)
                    <x-ui.button variant="outline" icon="send" class="mt-4 w-full" wire:click="kirimSekarang" loading="kirimSekarang">Kirim Sekarang</x-ui.button>
                @endif
            </x-ui.card>
        </div>
    </div>

    <x-ui.card title="Pesan Terakhir" description="30 pesan terbaru. Pesan yang sudah dibuka di lonceng sebelum terkirim dilewati." class="mt-6">
        <ul class="divide-y divide-slate-100 dark:divide-navy-800">
            @forelse ($pesan as $p)
                @php $judul = trim(str_replace('*', '', explode("\n", $p->isi)[2] ?? '')); @endphp
                <li wire:key="wa-{{ $p->id }}" class="flex flex-wrap items-start gap-x-4 gap-y-2 py-3 text-sm">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-navy-900 dark:text-white">{{ $p->user->nama_user ?? 'Pengguna dihapus' }} <span class="font-normal text-slate-500 dark:text-slate-400">· {{ NomorWa::tampil($p->nomor) }}</span></p>
                        <p class="truncate text-slate-600 dark:text-slate-300">{{ $judul }}</p>
                        @if ($p->galat)
                            <p class="text-slate-500 dark:text-slate-400">{{ $p->galat }}</p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-3">
                        <time class="text-slate-400">{{ ($p->dikirim_at ?? $p->created_at)->translatedFormat('d M H:i') }}</time>
                        <x-ui.badge :color="PesanWa::WARNA[$p->status] ?? 'slate'">{{ PesanWa::LABEL[$p->status] ?? $p->status }}</x-ui.badge>
                        @if (in_array($p->status, [PesanWa::GAGAL, PesanWa::DILEWATI], true))
                            <x-ui.button size="xs" variant="ghost" icon="arrow-repeat" wire:click="kirimUlang({{ $p->id }})" title="Masukkan lagi ke antrean">Ulang</x-ui.button>
                        @endif
                    </div>
                </li>
            @empty
                <li class="py-8 text-center text-sm text-slate-500">Belum ada pesan WhatsApp.</li>
            @endforelse
        </ul>
    </x-ui.card>
</div>
