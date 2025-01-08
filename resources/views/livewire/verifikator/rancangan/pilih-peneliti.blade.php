@section('title', 'Pilih Peneliti')

{{-- Header --}}
@section('header', 'Pilih Peneliti')
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
<div>
    {{-- Header --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Pilih Peneliti</h3>
        </div>
        <div class="card-body">
            {{-- Tabs --}}
            <ul class="nav nav-pills nav-fill flex-column flex-md-row" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'menunggu-peneliti' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('activeTab', 'menunggu-peneliti')">
                        <i class="ni ni-send mr-2"></i> Sedang Diajukan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'peneliti-ditugaskan' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('activeTab', 'peneliti-ditugaskan')">
                        <i class="ni ni-check-bold mr-2"></i> Riwayat Pengajuan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab == 'daftar-peneliti' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('activeTab', 'daftar-peneliti')">
                        <i class="ni ni-single-02 mr-2"></i> Daftar Peneliti
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu-peneliti')
                    @livewire('verifikator.rancangan.menunggu-peneliti')
                @elseif ($activeTab === 'peneliti-ditugaskan')
                    @livewire('verifikator.rancangan.peneliti-ditugaskan')
                @elseif ($activeTab === 'daftar-peneliti')
                    @livewire('verifikator.rancangan.daftar-peneliti')
                @endif
            </div>
        </div>
    </div>
</div>
