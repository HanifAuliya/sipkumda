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
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'menunggu-peneliti' ? 'active' : '' }}" href="#"
                        wire:click.prevent="switchTab('menunggu-peneliti')">
                        Menunggu Peneliti
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $activeTab === 'peneliti-ditugaskan' ? 'active' : '' }}" href="#"
                        wire:click.prevent="switchTab('peneliti-ditugaskan')">
                        Peneliti Ditugaskan
                    </a>
                </li>
            </ul>

            {{-- Tab Content --}}
            <div class="tab-content mt-4">
                @if ($activeTab === 'menunggu-peneliti')
                    @livewire('verifikator.rancangan.menunggu-peneliti')
                @elseif ($activeTab === 'peneliti-ditugaskan')
                @endif
            </div>
        </div>
    </div>
</div>
