<nav class="navbar navbar-top navbar-expand navbar-dark bg-default border-bottom sticky-top">
    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <!-- Topnav name -->
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Topvbar Typing Animation -->
                <div class="d-flex flex-column align-items-start">
                    <span class="text-white d-none d-md-block" id="full-text">
                        <span class="typing-text-heading full-text-heading">
                            Sistem Informasi Produk Hukum Daerah
                        </span>
                    </span>
                    <span class="text-white d-block d-md-none" id="short-text">
                        <span class="typing-text-heading short-text-heading">
                            SIPKUMDA
                        </span>
                    </span>
                </div>

                <!-- Navbar links -->
                <ul class="navbar-nav align-items-center ml-md-auto">
                    <!-- Rest of your navbar code remains unchanged -->
                </ul>
            </div>

            <!-- End Topnav name -->

            <!-- Navbar links -->
            <ul class="navbar-nav align-items-center ml-md-auto">
                <li class="nav-item">
                    <!-- Sidenav toggler -->
                    <div class="pr-3 sidenav-toggler sidenav-toggler-dark" id="sidenav-toggler"
                        data-action="sidenav-pin" data-target="#sidenav-main">
                        <div class="sidenav-toggler-inner">
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                            <i class="sidenav-toggler-line"></i>
                        </div>
                    </div>
                </li>
                <li class="nav-item dropdown">
                    {{-- notification --}}
                    @livewire('notification-dropdown')
                </li>
            </ul>

            <!-- Topnav Profil -->
            <ul class="navbar-nav align-items-center ml-auto ml-md-0">
                <li class="nav-item dropdown">
                    <a class="nav-link pr-0" href="#" role="button" data-toggle="dropdown" aria-haspopup="true"
                        aria-expanded="false">
                        <div class="media align-items-center">
                            <span class="avatar avatar-sm rounded-circle">
                                <img alt="Image placeholder" src="{{ asset('assets/img/theme/team-4.jpg') }}" />
                            </span>
                            <div class="media-body ml-2 d-none d-lg-block">
                                <span class="mb-0 text-sm font-weight-bold">
                                    {{ Auth::user()->nama_user }}
                                </span>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome!</h6>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="dropdown-item">
                            <i class="ni ni-single-02"></i>
                            <span>My profile</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="ni ni-user-run"></i>
                                <span>Logout</span>
                            </button>
                        </form>

                    </div>
                </li>
            </ul>
            <!-- End Topnav Profil -->
        </div>
    </div>
</nav>
