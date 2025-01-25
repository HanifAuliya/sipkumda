<div>
    {{-- Daftar Revisi --}}
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0 text-warning "> Menunggu Persetujuan -
                <small class="text-default">Daftar Rancangan yang
                    menunggu persetujuan berkas ! </small>
            </h3>
        </div>

        <div class="card-body">
            {{-- Searching dan PerPage --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                {{-- Searching --}}
                <div class="input-group w-50">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                    </div>
                    <input type="text" class="form-control" placeholder="Cari berdasarkan No Rancangan atau Tentang"
                        wire:model.live="search">
                </div>

                {{-- Per Page Dropdown --}}
                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="ni ni-bullet-list-67"></i></span>
                        </div>
                        <select class="form-control" wire:model.live="perPage">
                            <option value="3">3 Data per Halaman</option>
                            <option value="5">5 Data per Halaman</option>
                            <option value="10">10 Data per Halaman</option>
                            <option value="50">50 Data per Halaman</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Daftar Rancangan --}}
            <div class="list-group">
                @forelse ($rancanganMenunggu as $item)
                    <div class="list-group-item d-flex flex-wrap justify-content-between align-items-center">
                        {{-- Informasi Utama --}}
                        <div class="d-flex flex-column">
                            <div class="d-flex align-items-center">
                                <h4 class="mb-1 font-weight-bold">
                                    {{ $item->no_rancangan }}
                                </h4>
                                <h5 class="ml-2">
                                    <mark
                                        class="badge-{{ $item->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                        {{ $item->jenis_rancangan }}
                                    </mark>
                                </h5>
                            </div>
                            <p class="mb-1 mt-2 font-weight-bold">{{ $item->tentang }}</p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-person"></i>
                                {{ $item->user->nama_user ?? 'N/A' }}
                                <span class="badge badge-secondary">
                                    Pemohon
                                </span>
                            </p>
                            <p class="mb-0 info-text small">
                                <i class="bi bi-houses"></i>
                                {{ $item->user->perangkatDaerah->nama_perangkat_daerah ?? '-' }}
                            </p>

                            <p class="info-text small mb-0">
                                <i class="bi bi-calendar"></i> Tanggal Pengajuan:
                                {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->translatedFormat('d F Y, H:i') }}
                            </p>


                        </div>

                        {{-- Bagian Kanan --}}
                        <div class="col-md-4 col-12 text-right d-flex flex-column align-items-end mt-3 mt-md-0">
                            {{-- Status Berkas --}}
                            <h4 class="mb-2">
                                <mark
                                    class="badge-{{ $item->status_berkas === 'Disetujui' ? 'success' : ($item->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                    <i
                                        class="{{ $item->status_berkas === 'Disetujui' ? 'bi bi-check-circle' : ($item->status_berkas === 'Ditolak' ? 'bi bi-x-circle-fill' : 'bi bi-gear-fill') }}"></i>
                                    <span>Berkas</span> {{ $item->status_berkas }}
                                </mark>
                            </h4>

                            <p class="info-text mb-1 small">
                                Pengajuan Rancangan Tahun {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->year }}
                            </p>

                            <div class="mt-2">
                                {{-- Tombol Tindakan --}}
                                <button class="btn btn-neutral" wire:click="openModal({{ $item->id_rancangan }})">
                                    Verifikasi Berkas <i class="bi bi-question-circle"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="list-group-item text-center info-text">
                        Tidak ada rancangan yang ditemukan.
                    </div>
                @endforelse
            </div>


            <div class="d-flex justify-content-center mt-3">
                {{ $rancanganMenunggu->links('pagination::bootstrap-4') }}
            </div>

            {{--  Modal Tindakan  --}}
            <div wire:ignore.self class="modal fade" id="modalPersetujuan" tabindex="-1" role="dialog"
                aria-hidden="true" data-backdrop="static" data-keyboard="false">
                <div class="modal-dialog modal-xl no-style-modal" role="document">
                    <div class="modal-content">
                        {{--  Header Modal  --}}
                        <div class="modal-header flex-column align-items-start"
                            style="border-bottom: 2px solid #dee2e6;">
                            <h4 class="modal-title w-100 mb-2">Persetujuan Rancangan</h4>
                            <p class="description mb-0 w-100 info-text">
                                Silakan cek informasi rancangan di bawah ini, termasuk file yang diajukan, lalu pilih
                                status
                                persetujuan.
                            </p>
                            <button type="button" class="close position-absolute" style="top: 10px; right: 10px;"
                                data-dismiss="modal" aria-label="Close" wire:click="resetForm">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>

                        {{--  Body Modal  --}}
                        @if ($selectedRancangan)
                            <div class="row mt-3">
                                {{--  Informasi Utama  --}}
                                <div class="col-md-6 mb-2">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h4 class="mb-0">Informasi Utama</h4>
                                        </div>
                                        <div class="card-body">
                                            <p class="description">Berikut adalah informasi dasar dari rancangan
                                                yang
                                                diajukan. Pastikan semua informasi sudah sesuai.</p>
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <th class="info-text w-25">Nomor</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->no_rancangan ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Jenis</th>
                                                        <td class="wrap-text w-75">
                                                            <mark
                                                                class="badge-{{ $selectedRancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                                {{ $selectedRancangan->jenis_rancangan ?? 'N/A' }}
                                                            </mark>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tentang</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->tentang ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Pengajuan</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($selectedRancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">User Pengaju</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->user->nama_user ?? 'N/A' }}</td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Perangkat Daerah</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Status Rancangan</th>
                                                        <td class="wrap-text w-75">
                                                            <mark
                                                                class="badge-{{ $selectedRancangan->status_rancangan === 'Disetujui' ? 'success' : ($selectedRancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                {{ $selectedRancangan->status_rancangan ?? 'N/A' }}
                                                            </mark>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                {{--  File Persetujuan --}}
                                <div class="col-md-6 mb-2">
                                    <div class="card shadow-sm">
                                        <div class="card-header">
                                            <h4 class="mb-0">File Persetujuan</h4>
                                        </div>
                                        <div class="card-body">
                                            <p class="description">Pastikan file yang diajukan sudah lengkap dan
                                                sesuai.
                                                Anda dapat mengunduh file untuk memverifikasinya.</p>
                                            <table class="table table-sm table-borderless">
                                                <tbody>
                                                    <tr>
                                                        <th class="info-text w-25">Nota Dinas</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->nota_dinas_pd)
                                                                <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRancangan->nota_dinas_pd)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-text mr-2 text-warning"></i>
                                                                    Lihat Nota
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Nota</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">File Rancangan</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->rancangan)
                                                                <a href="{{ url('/view-private/rancangan/rancangan/' . basename($selectedRancangan->rancangan)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-text mr-2 text-primary"></i>
                                                                    Lihat Rancangan
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Rancangan</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Matrik</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->matrik)
                                                                <a href="{{ url('/view-private/rancangan/matrik/' . basename($selectedRancangan->matrik)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                                    Lihat Matrik
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Matrik</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Bahan Pendukung</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($selectedRancangan->bahan_pendukung)
                                                                <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($selectedRancangan->bahan_pendukung)) }}"
                                                                    target="_blank">
                                                                    <i
                                                                        class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
                                                                    Lihat Bahan
                                                                </a>
                                                            @else
                                                                <span class="text-muted">Tidak Ada Bahan</span>
                                                            @endif
                                                        </td>
                                                    </tr>

                                                    <tr>
                                                        <th class="info-text w-25">Status Berkas</th>
                                                        <td class="wrap-text w-75">
                                                            <mark
                                                                class="badge badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                                {{ $selectedRancangan->status_berkas ?? 'N/A' }}
                                                            </mark>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->tanggal_berkas_disetujui
                                                                ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                                : 'Belum disetujui' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Catatan Berkas</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                        </td>
                                                    </tr>
                                                </tbody>

                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{--  Verifikasi Persetujuan  --}}


                            <div class="alert alert-warning mb-0" role="alert"
                                style="flex: 1; text-align: center;">
                                <strong> Berkas Rancangan {{ $selectedRancangan->status_berkas }}!</strong>
                                Periksa dan Lakukan Verifikasi
                            </div>
                            <div class="card shadow-sm mt-3">
                                {{-- Persetujuan --}}
                                <div class="card-body">
                                    {{-- Informasi --}}
                                    <p class="description info-text mb-3 info-text">
                                        Pilih status persetujuan untuk rancangan ini. Tambahkan catatan
                                        untuk memberikan masukan atau alasan penolakan.
                                    </p>

                                    {{-- Pilihan Persetujuan Berkas --}}
                                    <div class="form-group">
                                        <label class="font-weight-bold form-control-label">
                                            Pilih Persetujuan Berkas dibawah ini:
                                        </label>

                                        <div class="w-auto" style="max-width: 300px;">
                                            {{-- Batasi lebar dropdown --}}
                                            <select id="statusBerkasSelect" name="statusBerkas" class="form-control"
                                                wire:model="statusBerkas">
                                                <option hidden>Pilih Status</option>
                                                <option value="Disetujui">Disetujui </option>
                                                <option value="Ditolak">Ditolak</option>
                                            </select>
                                        </div>
                                        @error('statusBerkas')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Catatan --}}
                                    <div class="form-group">
                                        <label class="font-weight-bold form-control-label">Tambahkan Catatan
                                            <small class="text-danger"> wajib</small></label>
                                        <textarea wire:model.defer="catatan" class="form-control mb-3" rows="3" placeholder="Tambahkan catatan..."></textarea>
                                        @error('catatan')
                                            <span class="text-danger mt-2 d-block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    {{-- Tombol --}}
                                    <div class="d-flex justify-content-end">
                                        {{-- Tombol Tutup --}}
                                        <button type="button" class="btn btn-outline-warning mr-3"
                                            data-dismiss="modal" wire:click="resetForm"></i> Batalkan
                                        </button>
                                        {{-- Tombol Verifikasi --}}
                                        <button class="btn btn-outline-default" wire:click="updateStatus"
                                            wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="updateStatus"><i
                                                    class="bi bi-check-circle"></i>
                                                Verifikasi
                                                Rancangan</span>
                                            <span wire:loading wire:target="updateStatus"><i
                                                    class="bi bi-hourglass-split"></i>
                                                Memproses...</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="d-flex justify-content-center align-items-start"
                                style="min-height: 200px; padding-top: 50px;">
                                <div class="text-center">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <p class="mt-3 info-text">Sedang memuat data, harap tunggu...</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
