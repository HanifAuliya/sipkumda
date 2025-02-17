<div>
    <div>
        {{-- Filter --}}
        <div class="row mb-3 ">
            <div class="col-md-3">
                <select class="form-control bg-white" wire:model.live="tahun">
                    <option value="">Semua Tahun</option> {{-- Opsi tambahan --}}
                    @foreach (range(date('Y'), 2010) as $year)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <select class="form-control" wire:model.live="jenis">
                    <option value="all">Semua Jenis</option>
                    <option value="Peraturan Bupati">Peraturan Bupati</option>
                    <option value="Surat Keputusan">Surat Keputusan</option>
                </select>
            </div>


        </div>

        {{-- Card --}}
        <div class="row">
            @php
                $cards = [
                    'Rancangan' => [
                        'icon' => 'bi-clipboard2-data',
                        'color' => 'bg-gradient-red',
                        'count' => $countRancangan,
                    ],
                    'Revisi' => [
                        'icon' => 'bi-pencil-square',
                        'color' => 'bg-gradient-orange',
                        'count' => $countRevisi,
                    ],
                    'Fasilitasi' => [
                        'icon' => 'bi-file-text',
                        'color' => 'bg-gradient-primary',
                        'count' => $countFasilitasi,
                    ],
                    'Dokumentasi' => [
                        'icon' => 'bi-folder-fill',
                        'color' => 'bg-gradient-info',
                        'count' => $countDokumentasi,
                    ],
                ];
            @endphp

            @foreach ($cards as $type => $data)
                <div class="col-xl-3 col-md-6">
                    <div class="card card-stats {{ $dataType === $type ? '' : 'inactive-card' }}"
                        wire:click="loadData('{{ $type }}')" style="cursor: pointer;">

                        <div class="card-body">
                            <div class="row">
                                <div class="col">
                                    <h5 class="card-title text-uppercase text-muted mb-0">
                                        {{ $type }} Produk Hukum
                                    </h5>
                                    <span class="h2 font-weight-bold mb-0" wire:loading.remove
                                        wire:target="tahun, jenis">
                                        @if ($type === 'Rancangan')
                                            {{ $countRancangan }}
                                        @elseif ($type === 'Revisi')
                                            {{ $countRevisi }}
                                        @elseif ($type === 'Fasilitasi')
                                            {{ $countFasilitasi }}
                                        @elseif ($type === 'Dokumentasi')
                                            {{ $countDokumentasi }}
                                        @endif
                                        Data
                                    </span>
                                    <span wire:loading wire:target="tahun, jenis">
                                        <div class="d-flex align-items-center">
                                            <span
                                                class="spinner-border spinner-border-sm text-default me-2 mr-2"></span>
                                            <span class="text-muted">Memuat Data...</span>
                                        </div>
                                    </span>
                                </div>
                                <div class="col-auto">
                                    <div
                                        class="icon icon-shape 
                                    {{ $dataType === $type ? $data['color'] : 'bg-secondary' }} 
                                    {{ $dataType === $type ? 'text-white' : 'text-dark' }} 
                                    rounded-circle shadow">
                                        <i class="bi {{ $data['icon'] }}"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

        </div>

        {{-- Tabel --}}
        <div class="card shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                {{-- Spinner Loading --}}
                <h3 wire:loading.remove wire:target="loadData"
                    class=" card-title
                          @if ($dataType === 'Rancangan') text-warning
                          @elseif ($dataType === 'Revisi') text-danger
                          @elseif ($dataType === 'Fasilitasi') text-primary
                          @elseif ($dataType === 'Dokumentasi') text-info @endif">
                    <span class="text-default"> Daftar - </span>
                    {{ $dataType }} Produk Hukum

                </h3>

                <span wire:loading wire:target="loadData">
                    <div class="d-flex align-items-center">
                        <span class="spinner-border spinner-border-sm text-default me-2 mr-2"></span>
                        <span class="text-muted">Memuat Data...</span>
                    </div>
                </span>

                @if ($dataType === 'Rancangan')
                    <a href="{{ route('export.masterdata', ['tahun' => $tahun, 'jenis' => $jenis]) }}"
                        class="btn btn-success d-flex align-items-center" wire:click.prevent="export"
                        wire:loading.attr="disabled" wire:target="export">

                        <!-- Ikon sebelum loading -->
                        <i class="bi bi-file-earmark-spreadsheet me-2 " wire:loading.remove wire:target="export"></i>
                        <span wire:loading.remove wire:target="export">Export Excel</span>

                        <!-- Spinner saat loading -->
                        <div class="spinner-border spinner-border-sm text-light me-2 mr-2" role="status" wire:loading
                            wire:target="export">
                        </div>
                        <span wire:loading wire:target="export"> Sedang Mengunduh...</span>
                    </a>
                @endif
            </div>
            <div class="card-body">
                {{-- Search Bar --}}
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="ni ni-zoom-split-in"></i></span>
                            </div>
                            <input type="text" class="form-control" wire:model.live="search"
                                placeholder="Cari tentang, Nomor Rancangan...">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>No Rancangan</th>
                                <th>Jenis</th>
                                <th>Tentang</th>
                                <th>Perangkat Daerah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($selectedData as $index => $data)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $data->no_rancangan ?? $data->rancangan->no_rancangan }}</td>
                                    <td>{{ $data->jenis_rancangan ?? $data->rancangan->jenis_rancangan }}</td>
                                    <td class="wrap-text w-50">{{ $data->tentang ?? $data->rancangan->tentang }}</td>
                                    <td>{{ $data->user->nama_user ?? $data->rancangan->user->nama_user }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center">Tidak ada data ditemukan.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ $selectedData->links('pagination::bootstrap-4') }}
            </div>
        </div>
    </div>

    {{-- css --}}
    <style>
        .inactive-card {
            background-color: #f8f9fa !important;
            opacity: 0.6;
            transition: all 0.3s ease-in-out;
        }

        .inactive-card:hover {
            opacity: 0.9;
            transform: scale(1.02);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            visibility: hidden;
            opacity: 0;
            transition: opacity 0.3s, visibility 0.3s;
        }

        .loading-overlay.show {
            visibility: visible;
            opacity: 1;
        }

        .card-stats {
            min-height: 120px;
        }

        /* Animasi Tabel Saat Filter Diterapkan */
        .table-responsive {
            transition: opacity 0.3s ease-in-out;
        }

        .fade-in {
            opacity: 0;
            animation: fadeIn 0.5s forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }
    </style>


    {{-- script --}}
    <script>
        document.addEventListener("livewire:load", function() {
            Livewire.hook('message.sent', () => {
                document.querySelector('.loading-overlay').classList.add('show');
            });

            Livewire.hook('message.received', () => {
                setTimeout(() => {
                    document.querySelector('.loading-overlay').classList.remove('show');
                }, 500);
            });
        });
    </script>
</div>
