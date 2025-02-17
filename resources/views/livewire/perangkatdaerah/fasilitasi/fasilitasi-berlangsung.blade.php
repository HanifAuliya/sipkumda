<div>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="mb-0 text-warning">Pengajuan Fasilitasi Berlangsung -
                <small class="text-default">Daftar Pengajuan Fasilitasi Berlangsung!</small>
            </h3>

            @livewire('perangkat-daerah.fasilitasi.ajukan-fasilitasi')

        </div>

        <div class="card-body">
            {{-- Searching dan PerPage --}}
            <div class="d-flex justify-content-between align-items-center mb-3">
                {{-- Pencarian --}}
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
            <table class="table table-bordered table-sm">
                <thead>
                    <tr>
                        <th>No Rancangan</th>
                        <th>Tentang</th>
                        <th>Status Berkas Fasilitasi</th>
                        <th>Status Validasi Fasilitasi</th>
                        <th>Klik Informasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fasilitasiBerlangsung as $fasilitasi)
                        <tr>
                            <td class="wrap-text">{{ $fasilitasi->rancangan->no_rancangan }}</td>
                            <td class="wrap-text w-75">{{ $fasilitasi->rancangan->tentang }}</td>
                            <td class="wrap-text w-25">
                                <mark
                                    class="badge-{{ $fasilitasi->status_berkas_fasilitasi === 'Disetujui' ? 'success' : ($fasilitasi->status_berkas_fasilitasi === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                    {{ $fasilitasi->status_berkas_fasilitasi ?? 'N/A' }}
                                </mark>
                            </td>
                            <td>
                                <mark
                                    class="badge-{{ $fasilitasi->status_validasi_fasilitasi === 'Diterima'
                                        ? 'success'
                                        : ($fasilitasi->status_validasi_fasilitasi === 'Ditolak'
                                            ? 'danger'
                                            : ($fasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi'
                                                ? 'danger'
                                                : 'warning')) }} badge-pill">
                                    {{ $fasilitasi->status_validasi_fasilitasi ?? 'N/A' }}
                                </mark>
                            </td>
                            <td class="wrap-text">
                                <!-- Tombol untuk Memicu Toast -->
                                <button type="button" class="btn btn-sm btn-outline-primary showToastBtn"
                                    data-status="{{ $fasilitasi->status_berkas_fasilitasi }}"
                                    data-validasi="{{ $fasilitasi->status_validasi_fasilitasi }}"
                                    data-nota="{{ optional($fasilitasi->notaDinas)->id ? 'true' : 'false' }}">
                                    Lihat Status
                                </button>
                            </td>

                            {{--  Container Toast  --}}
                            <div class="position-fixed toast-container top-0 right-0 p-3">
                                <div id="statusToast" class="toast hide" role="alert" aria-live="assertive"
                                    aria-atomic="true" data-delay="20000">
                                    <div class="toast-header">
                                        <img src="{{ asset('assets/img/brand/favicon.ico') }}"
                                            class="rounded toast-icon" alt="Favicon">
                                        <strong class="mr-auto">Status Fasilitasi</strong>
                                        <small>Baru saja</small>
                                        <button type="button" class="ml-2 mb-1 close" data-dismiss="toast"
                                            aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="toast-body">
                                        <span id="toastMessage">Menunggu Persetujuan...</span>
                                    </div>
                                </div>
                            </div>
                            <td>
                                <div class="dropdown position-static">
                                    <button type="button" class="btn btn btn-neutral dropdown-toggle"
                                        data-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-gear"></i> </button>
                                    <div class="dropdown-menu shadow-lg dropdown-menu-right">
                                        {{-- Tombol Lihat Detail --}}
                                        <a class="dropdown-item d-flex align-items-center" href="#"
                                            wire:click.prevent="openDetailFasilitasi({{ $fasilitasi->id }})"
                                            data-target="#modalDetailFasilitasi" data-toggle="modal">
                                            <i class="bi bi-info-circle"></i> Lihat Detail Fasilitasi
                                        </a>
                                        @if ($fasilitasi->status_berkas_fasilitasi === 'Ditolak')
                                            {{-- Upload Ulang Berkas --}}
                                            <a class="dropdown-item d-flex align-items-center text-default"
                                                wire:click.prevent="openUploadUlangRevisi({{ $fasilitasi->id }})"
                                                data-target="modalUploadUlangFasilitasi" data-toggle="modal">
                                                <i class="bi bi-upload text-success"></i> Upload Ulang Fasilitasi
                                            </a>
                                        @endif
                                        {{-- Validasi Disetujui dan Nota Dinas Telah Dibuat --}}
                                        @if ($fasilitasi->status_validasi_fasilitasi === 'Diterima' && optional($fasilitasi->notaDinas)->id)
                                            <a class="dropdown-item d-flex align-items-center text-warning"
                                                href="javascript:void(0);"
                                                onclick="showLoadingSwal(); @this.generatePDF({{ optional($fasilitasi->notaDinas)->id }})">
                                                <i class="bi bi-file-earmark-pdf"></i> Cetak Nota Dinas
                                            </a>
                                        @endif

                                        <script>
                                            function showLoadingSwal() {
                                                Swal.fire({
                                                    title: "Sedang Memproses PDF...",
                                                    html: "Mohon tunggu sebentar...",
                                                    allowOutsideClick: false,
                                                    showConfirmButton: false,
                                                    didOpen: () => {
                                                        Swal.showLoading();
                                                    }
                                                });
                                            }

                                            // Event dari Livewire untuk menutup SweetAlert setelah proses selesai
                                            document.addEventListener('DOMContentLoaded', function() {
                                                Livewire.on('hideLoadingSwal', () => {
                                                    Swal.close(); // Menutup SweetAlert
                                                });
                                            });
                                        </script>


                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">Tidak ada fasilitasi berlangsung</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            {{-- Kontrol Pagination --}}
            {{-- Pagination --}}
            <div class="d-flex justify-content-center mt-3">
                {{ $fasilitasiBerlangsung->links('pagination::bootstrap-4') }}
            </div>
        </div>

    </div>
    {{-- modal detail fasilitasi --}}
    <div wire:ignore.self class="modal fade" id="modalDetailFasilitasi" tabindex="-1" role="dialog"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Fasilitasi</h5>
                </div>
                <div class="modal-body">
                    @if ($selectedFasilitasi)
                        {{-- Tabel Detail --}}
                        <table class="table table-sm table-borderless">
                            <tbody>
                                <tr>
                                    <th class="info-text w-25">Nomor</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->rancangan->no_rancangan ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Jenis</th>
                                    <td class="wrap-text w-75">
                                        <mark
                                            class="badge-{{ $selectedFasilitasi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                            {{ $selectedFasilitasi->rancangan->jenis_rancangan ?? 'N/A' }}
                                        </mark>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Tentang</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->rancangan->tentang ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Tanggal Pengajuan Fasilitasi</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->tanggal_fasilitasi ? \Carbon\Carbon::parse($selectedFasilitasi->tanggal_fasilitasi)->translatedFormat('d F Y') : 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">User Pengaju</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->rancangan->user->nama_user ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Perangkat Daerah</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Status Berkas Fasiliatsi</th>
                                    <td class="wrap-text w-75">
                                        <mark
                                            class="badge-{{ $selectedFasilitasi->status_berkas_fasilitasi === 'Disetujui' ? 'success' : ($selectedFasilitasi->status_berkas_fasilitasi === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                            {{ $selectedFasilitasi->status_berkas_fasilitasi ?? 'N/A' }}
                                        </mark>
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Status Validasi Fasilitasi</th>
                                    <td class="wrap-text w-75">
                                        <mark
                                            class="badge-{{ $selectedFasilitasi->status_validasi_fasilitasi === 'Diterima'
                                                ? 'success'
                                                : ($selectedFasilitasi->status_validasi_fasilitasi === 'Ditolak'
                                                    ? 'danger'
                                                    : ($selectedFasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi'
                                                        ? 'danger'
                                                        : 'warning')) }} badge-pill">
                                            {{ $selectedFasilitasi->status_validasi_fasilitasi ?? 'N/A' }}
                                        </mark>

                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Tanggal Persetujuan Fasilitasi</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->tanggal_persetujuan_berkas ? \Carbon\Carbon::parse($selectedFasilitasi->tanggal_persetujuan_berkas)->translatedFormat('d F Y') : 'Belum di Verifikasi' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Catatan Fasilitasi</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->catatan_persetujuan_fasilitasi ?? 'Tidak Ada Catatan' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">File Rancangan Fasilitasi</th>
                                    <td class="wrap-text w-75">
                                        @if ($selectedFasilitasi->file_rancangan)
                                            <a href="{{ url('/view-private/fasilitasi/rancangan/' . basename($selectedFasilitasi->file_rancangan)) }}"
                                                target="_blank" class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark-pdf mr-2 text-primary"></i> Lihat File
                                            </a>
                                        @else
                                            <span class="text-muted">Tidak Ada File</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th class="info-text w-25">Status Paraf Koordinasi</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->status_paraf_koordinasi ?? 'Belum' }}
                                        @if ($selectedFasilitasi->tanggal_paraf_koordinasi)
                                            ({{ \Carbon\Carbon::parse($selectedFasilitasi->tanggal_paraf_koordinasi)->translatedFormat('d F Y') }})
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="info-text w-25">Status Asisten</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->status_asisten ?? 'Belum' }}
                                        @if ($selectedFasilitasi->tanggal_asisten)
                                            ({{ \Carbon\Carbon::parse($selectedFasilitasi->tanggal_asisten)->translatedFormat('d F Y') }})
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="info-text w-25">Status Sekda</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->status_sekda ?? 'Belum' }}
                                        @if ($selectedFasilitasi->tanggal_sekda)
                                            ({{ \Carbon\Carbon::parse($selectedFasilitasi->tanggal_sekda)->translatedFormat('d F Y') }})
                                        @endif
                                    </td>
                                </tr>

                                <tr>
                                    <th class="info-text w-25">Status Bupati</th>
                                    <td class="wrap-text w-75">
                                        {{ $selectedFasilitasi->status_bupati ?? 'Belum' }}
                                        @if ($selectedFasilitasi->tanggal_bupati)
                                            ({{ \Carbon\Carbon::parse($selectedFasilitasi->tanggal_bupati)->translatedFormat('d F Y') }})
                                        @endif
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        @if (
                            $selectedFasilitasi->status_berkas_fasilitasi === 'Menunggu Persetujuan' &&
                                $selectedFasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi')
                            <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center mb-2 mt-2"
                                role="alert">
                                <i class="bi bi-hourglass-split mr-2"></i>
                                <span class="alert-text">
                                    <strong>⏳ Harap Sabar!</strong> Fasilitasi Rancangan Menunggu Persetujuan Dari
                                    Peneliti.
                                </span>
                                <button type="button" class="close ml-auto" data-dismiss="alert"
                                    aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @elseif (
                            $selectedFasilitasi->status_berkas_fasilitasi === 'Ditolak' &&
                                $selectedFasilitasi->status_validasi_fasilitasi === 'Belum Tahap Validasi')
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-2 mt-2"
                                role="alert">
                                <i class="bi bi-x-circle mr-2"></i>
                                <span class="alert-text">
                                    <strong>❌ Fasilitasi Rancangan Ditolak!</strong> Silahkan Upload Ulang.
                                    Anda bisa ke kolom <strong>Aksi ⚙️</strong> -> pilih <strong>Upload Ulang
                                        Fasilitasi</strong>! 😏
                                </span>
                                <button type="button" class="close ml-auto" data-dismiss="alert"
                                    aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @elseif (
                            $selectedFasilitasi->status_berkas_fasilitasi === 'Ditolak' &&
                                $selectedFasilitasi->status_validasi_fasilitasi === 'Ditolak')
                            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center mb-2 mt-2"
                                role="alert">
                                <i class="bi bi-exclamation-triangle mr-2"></i>
                                <span class="alert-text">
                                    <strong>⚠️ Fasilitasi Ditolak!</strong>
                                    Harap periksa fasilitasi Anda sesuai catatan pengajuan sebelumnya 📑, lakukan
                                    perbaikan dan upload ulang.
                                    Anda bisa ke kolom <strong>Aksi ⚙️</strong> -> pilih <strong>Upload Ulang
                                        Fasilitasi</strong>! 😏
                                </span>
                                <button type="button" class="close ml-auto" data-dismiss="alert"
                                    aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @elseif (
                            $selectedFasilitasi->status_berkas_fasilitasi === 'Disetujui' &&
                                $selectedFasilitasi->status_validasi_fasilitasi === 'Menunggu Validasi')
                            <div class="alert alert-primary alert-dismissible fade show d-flex align-items-center mb-2 mt-2"
                                role="alert">
                                <i class="bi bi-shield-check mr-2"></i>
                                <span class="alert-text">
                                    <strong>✅ Fasilitasi Rancangan Telah Disetujui!</strong>
                                    Menunggu Konfirmasi dari Verifikator. Mohon Ditunggu 🙂!
                                </span>
                                <button type="button" class="close ml-auto" data-dismiss="alert"
                                    aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @elseif (
                            $selectedFasilitasi->status_validasi_fasilitasi === 'Diterima' &&
                                optional($selectedFasilitasi->notaDinas)->id === null)
                            <div class="alert alert-info alert-dismissible fade show d-flex align-items-center mb-2 mt-2"
                                role="alert">
                                <i class="bi bi-journal-check mr-2"></i>
                                <span class="alert-text">
                                    <strong>✅ Validasi Diterima!</strong>
                                    Menunggu Nota Dinas Dibuat. Harap sabar ya! 🤌
                                </span>
                                <button type="button" class="close ml-auto" data-dismiss="alert"
                                    aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @elseif ($selectedFasilitasi->status_validasi_fasilitasi === 'Diterima' && optional($selectedFasilitasi->notaDinas)->id)
                            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center mb-2 mt-2"
                                role="alert">
                                <i class="bi bi-file-earmark-text mr-2"></i>
                                <span class="alert-text">
                                    <strong>🗒️ Nota Dinas Telah Dibuat!</strong>
                                    Kamu bisa cetak di <strong>Aksi ⚙️ -> Cetak Nota Dinas</strong>, atau pergi ke
                                    halaman Nota dan cari Nota kamu!🔥
                                    Sekarang Anda dapat Mengajukan Fasilitasi secara daring. 📑⚖️
                                </span>
                                <button type="button" class="close ml-auto" data-dismiss="alert"
                                    aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif
                    @else
                        {{-- Spinner Loading --}}

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
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                        wire:click="resetDetail"><i class="bi bi-backspace"></i> Tutup Detail</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Upload Ulang --}}
    <div wire:ignore.self class="modal fade" id="modalUploadUlangFasilitasi" tabindex="-1" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Ulang Fasilitasi</h5>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Upload Berkas Baru (PDF)</label>

                        {{-- Input file (disabled setelah file dipilih) --}}
                        <input type="file" class="form-control" wire:model="fileRancanganUlang"
                            accept="application/pdf" wire:change="resetError('fileRancanganUlang')"
                            {{ $fileRancanganUlang ? 'disabled' : '' }}
                            style="{{ $fileRancanganUlang ? 'background-color: #e9ecef; cursor: not-allowed; opacity: 0.6;' : '' }}">

                        <small class="form-text text-muted"> * Maksimal 2MB. Format yang diterima: PDF.</small>

                        {{-- Indikator Loading --}}
                        <div wire:loading wire:target="fileRancanganUlang" class="text-primary mt-2">
                            <i class="spinner-border spinner-border-sm"></i> Mengunggah file...
                        </div>

                        @error('fileRancanganUlang')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                        {{-- Preview file & tombol hapus --}}
                        @if ($fileRancanganUlang)
                            <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                <span class="flex-grow-1">{{ $fileRancanganUlang->getClientOriginalName() }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                    wire:click="removeFile('fileRancanganUlang')">
                                    <i class="bi bi-trash"></i> Hapus File
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-dismiss="modal">
                        <i class="bi bi-backspace"></i> Batal
                    </button>
                    <button class="btn btn-outline-primary" wire:click="uploadUlangRevisi"
                        wire:loading.attr="disabled" {{ empty($fileRancanganUlang) ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="uploadUlangRevisi">
                            <i class="bi bi-upload"></i> Upload
                        </span>
                        <span wire:loading wire:target="uploadUlangRevisi">
                            <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                        </span>
                    </button>
                </div>

            </div>
        </div>
    </div>
</div>
