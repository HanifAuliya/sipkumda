@section('header', 'Rancangan Pengajuan')

{{-- Header --}}
@section('title', 'Rancanganku')

@section('breadcrumb')
    <nav aria-label="breadcrumb" class="d-none d-md-inline-block ml-md-4">
        <ol class="breadcrumb breadcrumb-links bg-gradient-orange">

            <li class="breadcrumb-item">
                <a href="{{ route('rancanganku') }}" class="text-white">
                    <i class="fas fa-home"></i>
                </a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('rancanganku') }}" class="text-white">Rancanganku</a>
            </li>
            <li class="breadcrumb-item active text-white" aria-current="page">List</li>
        </ol>
    </nav>
@endsection

@section('actions')
    <a href="#" class="btn btn-sm btn-neutral">New</a>
    <a href="#" class="btn btn-sm btn-neutral">Filters</a>
@endsection
<div>
    <div class="card shadow-sm">
        {{-- Header --}}
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <h3 class="mb-0">Daftar Rancanganku</h3>
                <small>Pastikan perhatikan tab di bawah!</small>
            </div>
            <button class="btn btn-outline-default" data-toggle="modal" data-target="#ajukanRancanganModal">Ajukan
                Rancangan Baru</button>
        </div>

        {{-- Tabs --}}
        <div class="card-body">
            <ul class="nav nav-pills flex-row" role="tablist">
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 'sedang_diajukan' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('tab', 'sedang_diajukan')">
                        <i class="ni ni-send mr-2"></i> Sedang Diajukan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ $tab == 'riwayat_pengajuan' ? 'active' : '' }}" href="#"
                        wire:click.prevent="$set('tab', 'riwayat_pengajuan')">
                        <i class="ni ni-check-bold mr-2"></i> Riwayat Pengajuan
                    </a>
                </li>
            </ul>

            {{-- Content --}}
            <div class="tab-content mt-3">
                @if ($tab == 'sedang_diajukan')
                    @livewire('perangkatdaerah.rancangan.sedang-diajukan')
                @elseif ($tab == 'riwayat_pengajuan')
                    @livewire('perangkatdaerah.rancangan.riwayat-pengajuan')
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Ajukan Rancangan --}}
    @livewire('perangkatdaerah.rancangan.tambah-rancangan')

</div>
