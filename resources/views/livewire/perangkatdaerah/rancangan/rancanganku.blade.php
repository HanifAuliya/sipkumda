@section('header', 'Daftar Rancangan Yang diajukan')
@section('title', 'Rancanganku')
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
