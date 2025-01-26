{{--
=========================================================
* Argon Dashboard - v1.2.0
=========================================================
* Product Page: https://www.creative-tim.com/product/argon-dashboard

* Copyright  Creative Tim (http://www.creative-tim.com)
* Coded by www.creative-tim.com

=========================================================
* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
--}}
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Start your development with a Dashboard for Bootstrap 4." />
    <meta name="SIPKUMDA" content="Creative Tim" />
    <title>@yield('title', 'Default Title') | Sistem Informasi Produk Hukum Daerah Hulu Sungai Tengah</title>
    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('assets/img/brand/favicon.ico') }}" type="image/png" />

    {{-- Fonts --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" />

    {{-- Icons --}}
    <link rel="stylesheet" href="{{ asset('assets/vendor/nucleo/css/nucleo.css') }}" type="text/css" />
    <link rel="stylesheet" href="{{ asset('assets/vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"
        type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        type="text/css" />

    {{-- Bootstrap & Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />

    {{-- Sweet alert --}}
    <link href="https://cdn.jsdelivr.net/npm/@sweetalert2/themes/bootstrap-4/bootstrap-4.css" rel="stylesheet">
    {{-- Argon CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/argon.css?v=1.2.0') }}" type="text/css" />

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('assets/css/sipkumda.css') }}" />

    @livewireStyles
</head>

<body>
    {{-- Loading Screen --}}


    <div class="loading-screen" id="loading-screen" style="{{ session('loading', true) ? '' : 'display:none;' }}">
        <div class="loading-content">
            {{-- Logo and SIPKUMDA Text in Row --}}
            <div class="logo-text-wrapper">
                <div class="logo">
                    <img src="{{ asset('assets/img/brand/loading_screen.png') }}" alt="Logo" class="img-fluid" />
                </div>
            </div>
            <h1 class="sipkumda-title m-0">SIPKUMDA HST</h1>
            {{-- Text Content Below --}}
            <p class="typing-animation-loading m-0 mt-2">
                Sistem Informasi Produk Hukum Daerah
            </p>
        </div>
    </div>


    {{-- Sidebar --}}
    @include('layouts.partials.sidebar')
    {{-- End of Sidebar --}}

    {{-- Main content --}}
    <div class="main-content" id="panel">
        {{-- Topnav --}}
        @include('layouts.partials.topnav')
        {{-- End of Topnav --}}

        {{-- Header --}}
        <div class="header pb-6">
            <div class="container-fluid">
                <div class="header-body">
                    <div class="row align-items-center py-4">
                        <div class="col-lg-12">
                            <h6 class="h2 d-inline-block mb-0">
                                @yield('header') <!-- Placeholder untuk judul -->
                            </h6>
                            @yield('manual') <!-- Placeholder untuk breadcrumb -->
                        </div>
                        {{-- <div class="col-lg-6 col-5 text-right">
                            @yield('actions') <!-- Placeholder untuk tombol aksi -->
                        </div> --}}
                    </div>
                </div>
            </div>
        </div>
        {{-- End of Header --}}

        {{-- Page Content --}}
        <div class="container-fluid mt--6">
            @yield('content')
            {{ $slot }}
            @include('layouts.partials.footer')
        </div>

        {{-- Notification Modal --}}
        {{-- Modal Admin --}}
        <livewire:notification-modal.admin.modal-persetujuan-admin />

        {{-- Modal Detail Rancangan --}}
        <livewire:notification-modal.detail-rancangan />

        {{-- Modal Detail pilih peneliti --}}
        <livewire:notification-modal.verifikator.rancangan.pilih-peneliti />

        {{-- Modal Revisi Rancanganan --}}
        <livewire:notification-modal.peneliti.rancangan.notificationrevisi />

        {{-- Notification Modal Script --}}
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Livewire.on('openAdminPersetujuanModal', () => {
                    const modal = new bootstrap.Modal(document.getElementById('adminPersetujuanModal'));
                    modal.show();
                });

                Livewire.on('openModalDetailRancangan', () => {
                    const modal = new bootstrap.Modal(document.getElementById('modalDetailRancangan'));
                    modal.show();
                });
                Livewire.on('openNotificationPilihPeneliti', () => {
                    const modal = new bootstrap.Modal(document.getElementById('notificationPilihPeneliti'));
                    modal.show();
                });
                Livewire.on('openModalNotificationRevisi', () => {
                    const modal = new bootstrap.Modal(document.getElementById('notificationRevisi'));
                    modal.show();
                });

            });
        </script>



    </div>

    {{-- Core --}}
    <script src="https://code.jquery.com/jquery-3.4.1.slim.min.js"
        integrity="sha384-J6qa4849blE2+poT4WnyKhv5vZF5SrPo0iEjwBvKU7imGFAV0wwj1yYfoRSJoZ+n" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"
        integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/js/bootstrap.min.js"
        integrity="sha384-wfSDF2E50Y2D1uUdj0O3uMBJnjuUD4Ih7YwaYd1iqfktj0Uod8GCExl3Og8ifwB6" crossorigin="anonymous">
    </script>

    {{-- js-cookie and scrollbar --}}
    <script src="{{ asset('assets/vendor/js-cookie/js.cookie.js') }}"></script>
    <script src="{{ asset('assets/vendor/jquery.scrollbar/jquery.scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/vendor/jquery-scroll-lock/dist/jquery-scrollLock.min.js') }}"></script>

    {{-- Argon JS --}}
    <script src="{{ asset('assets/js/argon.js?v=1.2.0') }}"></script>
    <script src="{{ asset('assets/js/sipkumda.js') }}"></script>

    {{-- JavaScript untuk mengontrol tampilan detail --}}
    <script src="{{ asset('assets/js/filter.js') }}"></script>

    {{-- Include SweetAlert Script --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>

    @livewireScripts
</body>

</html>
