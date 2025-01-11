@section('title', 'Verifikasi Berkas')

{{-- Header --}}
@section('header', 'Menu Verifikasi Berkas')
@section('breadcrumb')
    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
        <ol class="breadcrumb breadcrumb-links bg-gradient-orange">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.persetujuan') }}" class="text-white">
                    <i class="fas fa-home"></i>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('user.management') }}" class="text-white">Daftar Pengguna</a>
            </li>
            <li class="breadcrumb-item active text-white" aria-current="page">Tables</li>
        </ol>
    </nav>
@endsection

@section('actions')
    <a href="#" class="btn btn-sm btn-neutral">New</a>
    <a href="#" class="btn btn-sm btn-neutral">Filters</a>
@endsection

<div class="card shadow-sm">
    {{-- Header --}}
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h3 class="mb-2">Daftar Berkas untuk Verifikasi</h3>
        </div>
    </div>

    {{-- Penjelasan Menu --}}
    <div class="card-body">
        {{-- Tab --}}
        <ul class="nav nav-pills flex-row" role="tablist">
            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'menunggu' ? 'active' : '' }}"
                    wire:click.prevent="set('activeTab','menunggu')">
                    <i class="ni ni-send mr-2"></i>Berkas Menunggu Persetujuan
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'ditolak' ? 'active' : '' }}"
                    wire:click.prevent="set('activeTab','ditolak')">
                    <i class="ni ni-fat-remove mr-2"></i>Berkas Rancangan Ditolak
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link {{ $activeTab === 'riwayat' ? 'active' : '' }}"
                    wire:click.prevent="set('activeTab','riwayat')">
                    <i class="ni ni-check-bold mr-2"></i>Berkas Rancangan Disetujui
                </a>
            </li>
        </ul>

        {{-- Content --}}
        <div class="tab-content mt-3">
            @if ($activeTab === 'menunggu')
                @livewire('admin.rancangan.persetujuan-menunggu')
            @elseif ($activeTab === 'ditolak')
                @livewire('admin.rancangan.persetujuan-ditolak')
            @elseif ($activeTab === 'riwayat')
                @livewire('admin.rancangan.persetujuan-riwayat')
            @endif
        </div>
    </div>
</div>
