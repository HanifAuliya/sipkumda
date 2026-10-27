{{-- Beranda publik SIFKUMDA. JDIH hanya tampil sebagai cuplikan. Data dari JdihController@beranda. --}}
@php
    $fitur = [
        ['send-check', 'Pengajuan daring', 'Rancangan, matrik, dan nota dinas diajukan langsung dari kantor perangkat daerah tanpa berkas fisik bolak-balik.'],
        ['clipboard2-check', 'Cek kelengkapan otomatis', 'Berkas yang diunggah diperiksa kelengkapannya sebelum diajukan, sehingga lebih jarang dikembalikan.'],
        ['activity', 'Status yang transparan', 'Setiap tahap — verifikasi, penelitian, validasi, hingga paraf — tercatat dan bisa dipantau kapan saja.'],
        ['bell', 'Notifikasi setiap tahap', 'Perangkat daerah, verifikator, dan peneliti mendapat pemberitahuan begitu ada yang perlu ditindaklanjuti.'],
        ['file-earmark-text', 'Nota dinas otomatis', 'Nota dinas fasilitasi dibuat dari sistem lengkap dengan nomor dan tanda tangan, siap dicetak.'],
        ['archive', 'Arsip & JDIH', 'Produk hukum yang telah ditetapkan diarsipkan dan dapat dicari oleh masyarakat melalui JDIH.'],
    ];

    // Sesuai alur di public/assets/img/alur.
    $alur = [
        'rancangan' => ['Pengajuan Rancangan', 'file-earmark-plus', [
            ['Perangkat Daerah', 'Mengajukan rancangan', 'Unggah rancangan & matrik (Word) serta nota dinas (PDF).'],
            ['Admin Bagian Hukum', 'Memeriksa berkas', 'Berkas lengkap diteruskan; bila ditolak, perangkat daerah mengunggah ulang sesuai catatan.'],
            ['Verifikator', 'Menunjuk peneliti', 'Rancangan diteruskan kepada peneliti yang ditugaskan.'],
            ['Peneliti', 'Meneliti & merevisi', 'Peneliti meneliti rancangan dan mengunggah hasil revisi beserta matriknya.'],
            ['Verifikator', 'Memvalidasi hasil penelitian', 'Hasil penelitian divalidasi; bila belum sesuai, dikembalikan ke peneliti.'],
            ['Perangkat Daerah', 'Menyesuaikan rancangan', 'Rancangan diperbaiki sesuai hasil penelitian sebagai bahan fasilitasi.'],
        ]],
        'fasilitasi' => ['Fasilitasi & Penetapan', 'patch-check', [
            ['Perangkat Daerah', 'Mengajukan fasilitasi', 'Unggah rancangan hasil perbaikan (PDF) melalui menu Fasilitasiku.'],
            ['Peneliti', 'Memeriksa kesesuaian', 'Dicocokkan dengan hasil penelitian; bila belum sesuai, perangkat daerah mengunggah ulang.'],
            ['Verifikator', 'Memvalidasi fasilitasi', 'Fasilitasi yang sesuai divalidasi untuk dibuatkan nota dinas.'],
            ['Admin Bagian Hukum', 'Menerbitkan nota dinas', 'Nota dinas fasilitasi diterbitkan dan dapat dicetak perangkat daerah.'],
            ['Perangkat Daerah', 'Proses paraf', 'Paraf koordinasi, asisten, sekda, hingga bupati — setiap tahap dicatat di sistem.'],
            ['Admin Bagian Hukum', 'Mengarsipkan', 'Produk hukum yang ditetapkan diarsipkan dan tampil di JDIH untuk umum.'],
        ]],
    ];

    $peran = [
        ['building', 'Perangkat Daerah', 'Mengajukan rancangan dan fasilitasi, memantau status, serta mencetak nota dinas.'],
        ['person-check', 'Admin Bagian Hukum', 'Memeriksa kelengkapan berkas, menerbitkan nota dinas, dan mengarsipkan produk hukum.'],
        ['patch-check', 'Verifikator', 'Menunjuk peneliti dan memvalidasi hasil penelitian maupun fasilitasi.'],
        ['search', 'Peneliti', 'Meneliti rancangan, mengunggah revisi, dan memeriksa berkas fasilitasi.'],
    ];

    $faq = [
        ['Siapa yang dapat menggunakan SIFKUMDA?', 'Perangkat daerah di lingkungan Pemerintah Kabupaten Hulu Sungai Tengah serta pegawai Bagian Hukum Sekretariat Daerah. Akun disediakan oleh Bagian Hukum; tidak ada pendaftaran mandiri.'],
        ['Berkas apa saja yang perlu disiapkan untuk pengajuan?', 'Rancangan dan matrik dalam format Word (.doc/.docx), nota dinas dalam format PDF, dan bahan pendukung (opsional) dalam format PDF. Ukuran setiap berkas maksimal 20 MB.'],
        ['Bagaimana cara mengetahui status pengajuan?', 'Masuk ke SIFKUMDA lalu buka menu Rancanganku atau Fasilitasiku. Setiap perubahan status juga dikirim sebagai notifikasi di dalam aplikasi.'],
        ['Saya lupa password, apa yang harus dilakukan?', 'Gunakan tautan "Lupa password?" di halaman masuk untuk menerima tautan atur ulang melalui email yang terdaftar, atau hubungi Bagian Hukum.'],
        ['Apakah masyarakat umum dapat melihat produk hukum?', 'Ya. Produk hukum yang telah ditetapkan dapat dicari dan diunduh tanpa akun melalui halaman Produk Hukum (JDIH).'],
    ];

    $logo = fn ($file) => 'https://ik.imagekit.io/bertawedding/ppid/logo%20lembaga/' . $file;
    $links = [
        ['https://diskominfo.hstkab.go.id', $logo('Kominfo%20(1).png?updatedAt=1728977390499'), 'Diskominfo HST'],
        ['https://hstkab.go.id', $logo('Pemkab.png?updatedAt=1728977715130'), 'Pemkab HST'],
        ['https://opendata.hstkab.go.id', $logo('OpenData.png?updatedAt=1728978182768'), 'Open Data HST'],
        ['https://www.youtube.com/@mediacenterhst', $logo('Kominfo%20(2).png?updatedAt=1729128401636'), 'Media Center HST'],
        ['https://ppidutama.kalselprov.go.id', $logo('PPID%20UTAMA.png?updatedAt=1728978540123'), 'PPID Utama Kalsel'],
        ['https://lapor.go.id', $logo('LAPOR.png?updatedAt=1729127779226'), 'LAPOR'],
    ];

    $angka = fn (int $n) => number_format($n, 0, ',', '.');
    $hitung = fn (int $n) => "x-data=\"{ v: {$n} }\" x-intersect.once=\"v = 0; const to = {$n}, s = performance.now(); const t = (n) => { const p = Math.min((n - s) / 1200, 1); v = Math.round(to * (1 - Math.pow(1 - p, 3))); if (p < 1) requestAnimationFrame(t); }; requestAnimationFrame(t);\" x-text=\"v.toLocaleString('id-ID')\"";
    $reveal = 'x-data="{ shown: false }" x-intersect.once.margin.-80px="shown = true" x-bind:class="shown ? \'opacity-100 translate-y-0\' : \'opacity-0 translate-y-6\'"';
@endphp

<x-publik-layout :transparan="true">
    {{-- Hero --}}
    <section class="relative isolate overflow-hidden bg-navy-950 pt-32 pb-24 sm:pt-40 lg:pb-32">
        <img src="{{ asset('assets/img/theme/sulangking.jpg') }}" alt="" class="absolute inset-0 -z-20 size-full object-cover opacity-30" fetchpriority="high">
        <div class="absolute inset-0 -z-10 bg-linear-to-br from-navy-950 via-navy-950/90 to-primary-900/70"></div>
        <div class="pointer-events-none absolute -top-40 -right-40 -z-10 size-[32rem] rounded-full bg-primary-500/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-48 -left-24 -z-10 size-[28rem] rounded-full bg-brand-500/15 blur-3xl"></div>

        <div class="mx-auto grid max-w-7xl items-center gap-14 px-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)] lg:px-8">
            <div class="text-white">
                <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-1.5 text-xs font-semibold tracking-wide ring-1 ring-white/15 backdrop-blur">
                    <i class="bi bi-bank text-brand-500"></i> Bagian Hukum Setda Kabupaten Hulu Sungai Tengah
                </p>
                <h1 class="mt-6 text-4xl leading-[1.1] font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl">
                    Sistem Informasi <span class="bg-linear-to-r from-brand-500 to-emerald-300 bg-clip-text text-transparent">Fasilitasi</span> Produk Hukum Daerah
                </h1>
                <p class="mt-6 max-w-xl text-base leading-relaxed text-white/75 sm:text-lg">
                    Pengajuan, penelitian, fasilitasi, hingga dokumentasi Peraturan Bupati dan Keputusan Bupati dalam satu sistem — lebih cepat, tertib, dan transparan.
                </p>

                <div class="mt-9 flex flex-wrap items-center gap-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 rounded-xl bg-brand-500 px-6 py-3.5 text-sm font-semibold text-white shadow-xl shadow-brand-500/25 transition hover:-translate-y-0.5 hover:bg-brand-600">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk ke SIFKUMDA
                    </a>
                    <a href="{{ route('jdih.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-6 py-3.5 text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/15">
                        <i class="bi bi-search"></i> Cari Produk Hukum
                    </a>
                </div>

                <p class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-white/60">
                    <span class="inline-flex items-center gap-1.5"><i class="bi bi-shield-check text-brand-500"></i> Akses khusus perangkat daerah</span>
                    <a href="{{ asset('assets/document/SIFKUMDA-PanduanLogin.pdf') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 hover:text-white">
                        <i class="bi bi-book"></i> Panduan login
                    </a>
                </p>
            </div>

            {{-- Ilustrasi pelacakan status (dekoratif) --}}
            <div class="relative mx-auto w-full max-w-md lg:max-w-none" aria-hidden="true">
                <div class="absolute -inset-4 rounded-[2rem] bg-linear-to-br from-primary-500/30 to-brand-500/20 blur-2xl"></div>
                <div class="relative rounded-3xl bg-white/95 p-6 shadow-2xl ring-1 ring-white/20 backdrop-blur dark:bg-navy-900/95">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold tracking-wider text-slate-400 uppercase">Rancangan Peraturan Bupati</p>
                            <p class="mt-1 font-bold text-navy-900 dark:text-white">Pengelolaan Barang Milik Daerah</p>
                        </div>
                        <span class="rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-600 ring-1 ring-orange-200 dark:bg-orange-500/10 dark:ring-orange-500/30">Penelitian</span>
                    </div>

                    <ol class="mt-6 space-y-4">
                        @foreach ([['Diajukan perangkat daerah', 'selesai'], ['Berkas diverifikasi', 'selesai'], ['Diteliti peneliti', 'aktif'], ['Validasi verifikator', 'menunggu'], ['Fasilitasi & nota dinas', 'menunggu']] as [$langkah, $status])
                            <li class="flex items-center gap-3">
                                <span @class([
                                    'grid size-8 shrink-0 place-items-center rounded-full text-sm',
                                    'bg-emerald-500 text-white' => $status === 'selesai',
                                    'bg-primary-500 text-white ring-4 ring-primary-100 dark:ring-primary-500/20' => $status === 'aktif',
                                    'bg-slate-100 text-slate-400 dark:bg-navy-800' => $status === 'menunggu',
                                ])>
                                    <i @class(['bi', 'bi-check-lg' => $status === 'selesai', 'bi-hourglass-split' => $status === 'aktif', 'bi-circle' => $status === 'menunggu'])></i>
                                </span>
                                <span @class(['text-sm', 'font-semibold text-navy-900 dark:text-white' => $status !== 'menunggu', 'text-slate-400' => $status === 'menunggu'])>{{ $langkah }}</span>
                            </li>
                        @endforeach
                    </ol>

                    <div class="mt-6 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800">
                        <div class="h-full w-1/2 rounded-full bg-linear-to-r from-emerald-500 to-primary-500"></div>
                    </div>
                </div>

                <div class="absolute -bottom-12 -left-6 hidden items-center gap-3 rounded-2xl bg-white p-3 pr-5 shadow-xl ring-1 ring-slate-100 sm:flex dark:bg-navy-900 dark:ring-navy-800">
                    <span class="grid size-10 place-items-center rounded-xl bg-emerald-50 text-xl text-emerald-600 dark:bg-emerald-500/15"><i class="bi bi-bell"></i></span>
                    <span class="text-xs leading-tight"><strong class="block text-navy-900 dark:text-white">Notifikasi baru</strong><span class="text-slate-500">Hasil penelitian siap divalidasi</span></span>
                </div>
                <div class="absolute -top-5 left-6 hidden items-center gap-2 rounded-2xl bg-white px-4 py-2.5 shadow-xl ring-1 ring-slate-100 sm:flex dark:bg-navy-900 dark:ring-navy-800">
                    <i class="bi bi-patch-check-fill text-primary-500"></i>
                    <span class="text-xs font-semibold text-navy-900 dark:text-white">Berkas lengkap</span>
                </div>
            </div>
        </div>
    </section>

    {{-- Statistik --}}
    <section class="relative z-10 mx-auto -mt-12 max-w-6xl px-5 lg:px-8">
        <dl class="grid grid-cols-2 gap-px overflow-hidden rounded-3xl bg-slate-200 shadow-card ring-1 ring-slate-200 lg:grid-cols-4 dark:bg-navy-800 dark:ring-navy-800">
            @foreach ([
                ['file-earmark-text', $statistik['rancangan'], 'Rancangan diajukan'],
                ['hourglass-split', $statistik['diproses'], 'Sedang diproses'],
                ['patch-check', $statistik['fasilitasi'], 'Fasilitasi'],
                ['journal-bookmark', $statistik['ditetapkan'], 'Produk hukum terarsip'],
            ] as [$ikon, $nilai, $label])
                <div class="flex items-center gap-4 bg-white p-5 sm:p-6 dark:bg-navy-900">
                    <span class="hidden size-12 shrink-0 place-items-center rounded-2xl bg-primary-50 text-xl text-primary-500 sm:grid dark:bg-primary-500/15"><i class="bi bi-{{ $ikon }}"></i></span>
                    <div>
                        <dd class="text-2xl font-extrabold text-navy-900 sm:text-3xl dark:text-white" {!! $hitung($nilai) !!}>{{ $angka($nilai) }}</dd>
                        <dt class="mt-0.5 text-xs text-slate-500 sm:text-sm dark:text-slate-400">{{ $label }}</dt>
                    </div>
                </div>
            @endforeach
        </dl>
    </section>

    {{-- Keunggulan --}}
    <section id="layanan" class="scroll-mt-20 py-20 lg:py-28">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <div class="mx-auto max-w-2xl text-center">
                <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Layanan</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-navy-900 sm:text-4xl dark:text-white">Fasilitasi produk hukum tanpa berkas bolak-balik</h2>
                <p class="mt-4 text-slate-600 dark:text-slate-400">Semua pihak bekerja pada sistem yang sama, dari pengajuan pertama hingga produk hukum diarsipkan.</p>
            </div>

            <div class="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($fitur as $i => [$ikon, $judul, $isi])
                    <div class="group rounded-3xl border border-slate-100 bg-white p-7 transition duration-700 hover:-translate-y-1 hover:border-primary-100 hover:shadow-card dark:border-navy-800 dark:bg-navy-900 dark:hover:border-primary-500/30"
                        style="transition-delay: {{ $i * 60 }}ms" {!! $reveal !!}>
                        <span class="grid size-12 place-items-center rounded-2xl bg-linear-to-br from-primary-500 to-primary-600 text-xl text-white shadow-lg shadow-primary-500/25 transition group-hover:scale-110">
                            <i class="bi bi-{{ $ikon }}"></i>
                        </span>
                        <h3 class="mt-5 text-lg font-bold text-navy-900 dark:text-white">{{ $judul }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $isi }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Alur --}}
    <section id="alur" class="scroll-mt-20 bg-slate-50 py-20 lg:py-28 dark:bg-navy-900" x-data="{ tab: 'rancangan' }">
        <div class="mx-auto max-w-7xl px-5 lg:px-8">
            <div class="flex flex-col items-start justify-between gap-6 lg:flex-row lg:items-end">
                <div class="max-w-2xl">
                    <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Alur</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-navy-900 sm:text-4xl dark:text-white">Dari pengajuan sampai penetapan</h2>
                    <p class="mt-4 text-slate-600 dark:text-slate-400">Dua tahap besar dengan peran yang jelas di setiap langkahnya.</p>
                </div>

                <div class="inline-flex rounded-2xl bg-white p-1.5 shadow-sm ring-1 ring-slate-200 dark:bg-navy-950 dark:ring-navy-800" role="tablist">
                    @foreach ($alur as $kunci => [$label, $ikon])
                        <button type="button" role="tab" x-on:click="tab = '{{ $kunci }}'" x-bind:aria-selected="tab === '{{ $kunci }}'"
                            x-bind:class="tab === '{{ $kunci }}' ? 'bg-navy-900 text-white shadow dark:bg-primary-500' : 'text-slate-600 hover:text-navy-900 dark:text-slate-300 dark:hover:text-white'"
                            class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition">
                            <i class="bi bi-{{ $ikon }}"></i> {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach ($alur as $kunci => [$label, $ikon, $langkah])
                <ol x-show="tab === '{{ $kunci }}'" @if (! $loop->first) x-cloak @endif
                    x-transition:enter="transition duration-300" x-transition:enter-start="opacity-0 translate-y-2"
                    class="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-3" role="tabpanel" aria-label="{{ $label }}">
                    @foreach ($langkah as $i => [$siapa, $judul, $isi])
                        <li class="relative rounded-3xl bg-white p-6 ring-1 ring-slate-200/70 dark:bg-navy-950 dark:ring-navy-800">
                            <div class="flex items-center justify-between">
                                <span class="grid size-11 place-items-center rounded-2xl bg-navy-900 text-lg font-extrabold text-white dark:bg-primary-500">{{ $i + 1 }}</span>
                                <span class="rounded-full bg-primary-50 px-3 py-1 text-xs font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-300">{{ $siapa }}</span>
                            </div>
                            <h3 class="mt-5 font-bold text-navy-900 dark:text-white">{{ $judul }}</h3>
                            <p class="mt-1.5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $isi }}</p>
                        </li>
                    @endforeach
                </ol>
            @endforeach
        </div>
    </section>

    {{-- Peran --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 lg:grid-cols-2 lg:px-8">
            <div class="relative" {!! $reveal !!}>
                <img src="{{ asset('assets/img/theme/Surat-Keputusan.jpg') }}" alt="Penandatanganan dokumen produk hukum" class="aspect-[4/3] w-full rounded-3xl object-cover shadow-2xl" loading="lazy">
                <div class="absolute -right-4 -bottom-6 hidden rounded-2xl bg-white p-5 shadow-xl ring-1 ring-slate-100 sm:block dark:bg-navy-900 dark:ring-navy-800">
                    <p class="text-3xl font-extrabold text-navy-900 dark:text-white">{{ $angka($statistik['instansi']) }}</p>
                    <p class="text-sm text-slate-500">perangkat daerah terhubung</p>
                </div>
            </div>

            <div>
                <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Peran Pengguna</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-navy-900 sm:text-4xl dark:text-white">Setiap pihak tahu apa yang harus dikerjakan</h2>
                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    @foreach ($peran as [$ikon, $judul, $isi])
                        <div class="rounded-2xl border border-slate-100 p-5 transition hover:border-primary-100 hover:bg-slate-50/60 dark:border-navy-800 dark:hover:bg-navy-900">
                            <span class="grid size-10 place-items-center rounded-xl bg-brand-500/10 text-lg text-brand-600 dark:text-brand-500"><i class="bi bi-{{ $ikon }}"></i></span>
                            <h3 class="mt-3 font-bold text-navy-900 dark:text-white">{{ $judul }}</h3>
                            <p class="mt-1 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $isi }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Cuplikan JDIH --}}
    <section class="relative overflow-hidden bg-navy-950 py-20 lg:py-24">
        <div class="pointer-events-none absolute -top-32 left-1/3 size-96 rounded-full bg-primary-500/20 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-5 lg:px-8">
            <div class="grid items-end gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                <div class="text-white">
                    <p class="inline-flex items-center gap-2 text-sm font-bold tracking-wider text-brand-500 uppercase">
                        <i class="bi bi-journal-bookmark"></i> JDIH · Produk Hukum
                    </p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-white sm:text-4xl">Produk hukum yang ditetapkan terbuka untuk umum</h2>
                    <p class="mt-4 text-white/70">
                        Cari Peraturan Bupati dan Keputusan Bupati Kabupaten Hulu Sungai Tengah berdasarkan judul atau nomor — tanpa perlu akun.
                    </p>
                </div>
                <x-jdih.form-cari :opsi="$opsi" />
            </div>

            @if ($terbaru->isNotEmpty())
                <div class="mt-10 grid gap-5 md:grid-cols-3">
                    @foreach ($terbaru as $dokumen)
                        <x-jdih.kartu :dokumen="$dokumen" class="dark:border-white/10 dark:bg-white/5" />
                    @endforeach
                </div>
            @endif

            <div class="mt-8 text-center">
                <a href="{{ route('jdih.index') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-5 py-3 text-sm font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/15">
                    Jelajahi semua produk hukum <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section class="py-20 lg:py-28">
        <div class="mx-auto grid max-w-7xl gap-12 px-5 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:px-8">
            <div>
                <p class="text-sm font-bold tracking-wider text-brand-600 uppercase">Tanya Jawab</p>
                <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-navy-900 sm:text-4xl dark:text-white">Pertanyaan yang sering diajukan</h2>
                <p class="mt-4 text-slate-600 dark:text-slate-400">Belum menemukan jawabannya? Hubungi Bagian Hukum Sekretariat Daerah.</p>
                <a href="#kontak" class="mt-6 inline-flex items-center gap-2 text-sm font-semibold text-primary-600 hover:underline dark:text-primary-300">
                    Lihat kontak <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="divide-y divide-slate-200 rounded-3xl border border-slate-200 dark:divide-navy-800 dark:border-navy-800" x-data="{ buka: 0 }">
                @foreach ($faq as $i => [$tanya, $jawab])
                    <div>
                        <h3>
                            <button type="button" class="flex w-full items-center justify-between gap-4 px-6 py-5 text-left font-semibold text-navy-900 dark:text-white"
                                x-on:click="buka = buka === {{ $i }} ? null : {{ $i }}" x-bind:aria-expanded="buka === {{ $i }}">
                                {{ $tanya }}
                                <i class="bi bi-chevron-down shrink-0 text-slate-400 transition" x-bind:class="buka === {{ $i }} && 'rotate-180'"></i>
                            </button>
                        </h3>
                        <div x-show="buka === {{ $i }}" x-collapse @if ($i !== 0) x-cloak @endif>
                            <p class="px-6 pb-5 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $jawab }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Tautan penting --}}
    <section class="border-y border-slate-100 bg-slate-50 py-10 dark:border-navy-800 dark:bg-navy-900">
        <h2 class="text-center text-sm font-bold tracking-wider text-slate-500 uppercase">Tautan Penting</h2>
        <div class="group relative mt-6 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]">
            <div class="flex w-max animate-marquee items-center gap-16 group-hover:[animation-play-state:paused]">
                @foreach ([...$links, ...$links] as $i => [$href, $img, $alt])
                    <a href="{{ $href }}" target="_blank" rel="noopener" @if ($i >= count($links)) aria-hidden="true" tabindex="-1" @endif
                        class="opacity-70 grayscale transition hover:opacity-100 hover:grayscale-0">
                        <img src="{{ $img }}" alt="{{ $alt }}" class="h-12 w-auto" loading="lazy">
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Kontak --}}
    <section id="kontak" class="scroll-mt-20 py-20 lg:py-28">
        <div class="mx-auto grid max-w-7xl gap-8 px-5 lg:grid-cols-[minmax(0,0.8fr)_minmax(0,1.2fr)] lg:px-8">
            <div class="flex flex-col justify-between rounded-3xl bg-navy-900 p-8 text-white">
                <div>
                    <p class="text-sm font-bold tracking-wider text-brand-500 uppercase">Kontak</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-white">Bagian Hukum Setda Kabupaten Hulu Sungai Tengah</h2>
                    <ul class="mt-8 space-y-5 text-sm text-white/80">
                        <li class="flex gap-3"><i class="bi bi-geo-alt text-lg text-brand-500"></i> C98M+5R3, Jl. Perwira, Barabai Sel., Kec. Barabai, Kabupaten Hulu Sungai Tengah, Kalimantan Selatan 71315</li>
                        <li class="flex gap-3"><i class="bi bi-facebook text-lg text-brand-500"></i> <a href="https://www.facebook.com/SetdaHSTMenyala" target="_blank" rel="noopener" class="hover:text-white">SetdaHSTMenyala</a></li>
                        <li class="flex gap-3"><i class="bi bi-instagram text-lg text-brand-500"></i> <a href="https://www.instagram.com/setdahstmenyala/" target="_blank" rel="noopener" class="hover:text-white">@setdahstmenyala</a></li>
                    </ul>
                </div>
                <a href="https://maps.google.com/?q=Jl.+Perwira,+Barabai" target="_blank" rel="noopener"
                    class="mt-8 inline-flex items-center gap-2 self-start rounded-xl bg-white/10 px-4 py-2.5 text-sm font-semibold ring-1 ring-white/15 transition hover:bg-white/15">
                    <i class="bi bi-signpost-split"></i> Petunjuk arah
                </a>
            </div>
            <div class="overflow-hidden rounded-3xl shadow-card ring-1 ring-slate-100 dark:ring-navy-800">
                <iframe class="h-full min-h-80 w-full border-0" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen
                    title="Lokasi Sekretariat Daerah Kabupaten Hulu Sungai Tengah"
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d15934.265820741635!2d115.38191621594372!3d-2.5891951377680394!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2de420cf5f1f93b7%3A0xa81c2d6ec6b4e62d!2sJl.%20Perwira%2C%20Barabai%20Sel.%2C%20Kec.%20Barabai%2C%20Kabupaten%20Hulu%20Sungai%20Tengah%2C%20Kalimantan%20Selatan%2071315!5e0!3m2!1sen!2sid!4v1713456789012!5m2!1sen!2sid"></iframe>
            </div>
        </div>
    </section>
</x-publik-layout>
