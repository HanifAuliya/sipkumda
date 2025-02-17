{{-- Sidenav --}}
<nav class="sidenav navbar navbar-vertical fixed-left navbar-expand-xs navbar-light bg-white" id="sidenav-main">
    <div class="scrollbar-inner">
        {{-- Sidenav Header --}}
        <div class="sidenav-header d-flex align-items-center justify-content-between position-relative">
            {{-- Logo --}}
            <a class="navbar-brand d-flex align-items-center" href="javascript:void(0)">
                <img src="{{ asset('assets/img/brand/favicon.ico') }}" class="navbar-brand-img" alt="Logo" />
                <span class="brand-text ms-2">SIPKUMDA</span>
                {{-- Tambahkan teks SIPKUMDA di samping logo --}}
            </a>
        </div>

        {{-- Sidebar Menu --}}
        <div class="navbar-inner">
            {{-- Collapse --}}
            <div class="collapse navbar-collapse" id="sidenav-collapse-main">
                {{-- Nav items --}}
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link {{ Route::currentRouteName() == 'dashboard' ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">
                            <i class="bi bi-window-fullscreen"></i>
                            <span class="nav-link-text text">Dashboard</span>
                        </a>
                    </li>
                </ul>

                {{-- Divider --}}
                <hr class="my-3" />
                @if (!Auth::user()->hasRole('Tamu'))
                    {{-- Heading --}}
                    <h6 class="navbar-heading p-0 text-muted">
                        <span class="docs-normal">Manajemen User</span>
                    </h6>
                    {{-- User Navigation --}}
                    <ul class="navbar-nav mb-md--1">
                        <li class="nav-item">
                            <a class="nav-link {{ Route::currentRouteName() == 'profile.edit' ? 'active' : '' }}"
                                href="{{ route('profile.edit') }}">
                                <i class="bi bi-person-fill"></i>
                                <span class="nav-link-text">Profile</span>
                            </a>
                        </li>
                        {{-- Hanya untuk Admin, Verifikator, atau Super Admin --}}
                        @if (Auth::user()->hasRole('Admin') || Auth::user()->hasRole('Verifikator') || Auth::user()->hasRole('Super Admin'))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'user.management' ? 'active' : '' }}"
                                    href="{{ route('user.management') }}">
                                    <i class="bi bi-people-fill"></i>
                                    <span class="nav-link-text">Daftar Pengguna</span>
                                </a>
                            </li>
                        @endif
                    </ul>

                    {{-- Divider --}}
                    <hr class="my-3" />

                    {{-- Heading --}}
                    <h6 class="navbar-heading p-0 text-muted">
                        <span class="docs-normal">Rancangan Produk Hukum</span>
                    </h6>
                    {{-- Navigation --}}
                    <ul class="navbar-nav mb-md--1">
                        @if (Auth::user()->hasRole('Perangkat Daerah'))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'rancanganku' ? 'active' : '' }} rancangan"
                                    href="{{ route('rancanganku') }}">
                                    <i class="bi bi-clipboard2"></i>
                                    <span class="nav-link-text">Rancanganku</span>
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ Route::currentRouteName() == 'daftar-rancangan' ? 'active' : '' }} rancangan"
                                href="{{ route('daftar-rancangan') }}">
                                <i class="bi bi-clipboard2-data"></i>
                                <span class="nav-link-text">Daftar Rancangan</span>
                            </a>
                        </li>

                        @if (Auth::user()->hasRole('Admin'))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'admin.persetujuan' ? 'active' : '' }} rancangan"
                                    href="{{ route('admin.persetujuan') }}">
                                    <i class="bi bi-clipboard2-check"></i>
                                    <span class="nav-link-text">Persetujuan Berkas</span>
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->hasRole('Verifikator'))
                            <li class="nav-item">
                                <a class="nav-link  {{ Route::currentRouteName() == 'verifikator.pilih-peneliti' ? 'active' : '' }} rancangan"
                                    href="{{ route('verifikator.pilih-peneliti') }}">
                                    <i class="bi bi-person-check"></i>
                                    <span class="nav-link-text">Pilih Peneliti</span>
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->hasRole('Peneliti'))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'revisi.rancangan' ? 'active' : '' }} rancangan"
                                    href="{{ route('revisi.rancangan') }}">
                                    <i class="bi bi-pencil-square"></i>
                                    <span class="nav-link-text">Upload Revisi</span>
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->hasRole('Verifikator'))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'verifikator.validasi-rancangan' ? 'active' : '' }} rancangan "
                                    href="{{ route('verifikator.validasi-rancangan') }}">
                                    <i class="bi bi-clipboard2-check"></i>
                                    <span class="nav-link-text">Validasi Rancangan</span>
                                </a>
                            </li>
                        @endif

                    </ul>

                    {{-- Divider --}}
                    <hr class="my-3" />

                    {{-- Heading --}}
                    <h6 class="navbar-heading p-0 text-muted">
                        <span class="docs-normal">Fasilitasi Produk Hukum</span>
                    </h6>

                    {{-- Navigation --}}
                    <ul class="navbar-nav mb-md--1">
                        @if (Auth::user()->hasRole('Perangkat Daerah'))
                            <li class="nav-item">
                                <a class="nav-link nav-link {{ Route::currentRouteName() == 'fasilitasiku.main' ? 'active' : '' }} fasilitasi"
                                    href="{{ route('fasilitasiku.main') }}">
                                    <i class="bi bi-files"></i>
                                    <span class="nav-link-text">Fasilitasiku</span>
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->hasRole('Peneliti'))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'persetujuan-fasilitasi.main' ? 'active' : '' }} fasilitasi"
                                    href="{{ route('persetujuan-fasilitasi.main') }}">
                                    <i class="bi bi-file-check"></i>
                                    <span class="nav-link-text">Persetujuan Fasilitasi</span>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->hasRole('Verifikator'))
                            <li class="nav-item ">
                                <a class="nav-link {{ Route::currentRouteName() == 'validasi-fasilitasi' ? 'active' : '' }} fasilitasi"
                                    href="{{ route('validasi-fasilitasi') }}">
                                    <i class="bi bi-file-earmark-check"></i>
                                    <span class="nav-link-text">Validasi Fasilitasi </span>
                                </a>
                            </li>
                            <li class="nav-item ">
                                <a class="nav-link {{ Route::currentRouteName() == 'kelola-ttd.main' ? 'active' : '' }} fasilitasi"
                                    href="{{ route('kelola-ttd.main') }}">
                                    <i class="bi bi-pen"></i>
                                    <span class="nav-link-text">kelola TTD </span>
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->hasRole(['Admin', 'Verifikator', 'Perangkat Daerah']))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'nota-dinas.generate' ? 'active' : '' }} fasilitasi"
                                    href="{{ route('nota-dinas.generate') }}">
                                    <i class="bi bi-file-text"></i>
                                    <span class="nav-link-text">Daftar Nota Dinas</span>
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->hasRole(['Admin', 'Verifikator']))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'manajemen-fasilitasi' ? 'active' : '' }} fasilitasi"
                                    href="{{ route('manajemen-fasilitasi') }}">
                                    <i class="bi bi-file-earmark-break"></i>
                                    <span class="nav-link-text">Manajemen Fasilitasi</span>
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->hasRole(['Admin', 'Verifikator', 'Peneliti', 'Super Admin']))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'daftar-fasilitasi' ? 'active' : '' }} dokumentasi"
                                    href="{{ route('daftar-fasilitasi') }}">
                                    <i class="bi bi-files"></i>
                                    <span class="nav-link-text">Daftar Fasilitasi</span>
                                </a>
                            </li>
                        @endif
                    </ul>

                    {{-- Divider --}}
                    <hr class="my-3" />

                    {{-- Heading --}}
                    <h6 class="navbar-heading p-0 text-muted">
                        <span class="docs-normal">Dokumentasi Produk Hukum</span>
                    </h6>
                    {{-- Navigation --}}
                    <ul class="navbar-nav mb-md-3">
                        @if (Auth::user()->hasRole(['Admin', 'Verifikator', 'Perangkat Daerah', 'Peneliti']))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'dokumentasi.main' ? 'active' : '' }} dokumentasi"
                                    href="{{ route('dokumentasi.main') }}">
                                    <i class="bi bi-folder-check"></i>
                                    <span class="nav-link-text">Daftar Dokumentasi</span>
                                </a>
                            </li>
                        @endif
                        @if (Auth::user()->hasRole(['Admin', 'Verifikator', 'Peneliti', 'Super Admin']))
                            <li class="nav-item">
                                <a class="nav-link {{ Route::currentRouteName() == 'masterdata.main' ? 'active' : '' }} dokumentasi"
                                    href="{{ route('masterdata.main') }}">
                                    <i class="bi bi-journals"></i>
                                    <span class="nav-link-text">Master Data</span>
                                </a>
                            </li>
                        @endif
                        <br>
                        <br>
                        <br>
                        <br>
                        <br>
                        <br>

                    </ul>
                @endif

            </div>
        </div>
    </div>
</nav>
{{-- End of Sidenav --}}
