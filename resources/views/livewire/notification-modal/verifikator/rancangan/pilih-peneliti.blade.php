    {{-- Modal --}}
    <div wire:ignore.self class="modal fade" id="notificationPilihPeneliti" tabindex="-1" data-backdrop="static"
        data-keyboard="false">
        <div class="modal-dialog modal-xl no-style-modal">
            @if ($selectedRancangan)
                @if ($selectedRancangan && $selectedRancangan->status_berkas === 'Disetujui')
                    <div class="modal-content">
                        <div class="row">
                            {{--  Informasi Utama  --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">Informasi Utama</h4>
                                    </div>
                                    <div class="card-body table-responsive modal-table mt--3">

                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                <tr>
                                                    <th class="info-text">Nomor</th>
                                                    <td>{{ $selectedRancangan->no_rancangan ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Jenis</th>
                                                    <td>
                                                        <mark
                                                            class="badge-{{ $selectedRancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                            {{ $selectedRancangan->jenis_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Tentang</th>
                                                    <td>{{ $selectedRancangan->tentang ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Tanggal Pengajuan</th>
                                                    <td>{{ $selectedRancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($selectedRancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">User Pengaju</th>
                                                    <td>{{ $selectedRancangan->user->nama_user ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Perangkat Daerah</th>
                                                    <td>{{ $selectedRancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Nomor Nota</th>
                                                    <td class="wrap-text-td-70 ">
                                                        {{ $selectedRancangan->nomor_nota ?? 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Nota</th>
                                                    <td>
                                                        {{ $selectedRancangan->tanggal_nota ? \Carbon\Carbon::parse($selectedRancangan->tanggal_nota)->translatedFormat('d F Y') : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Status Rancangan</th>
                                                    <td>
                                                        <mark
                                                            class="badge-{{ $selectedRancangan->status_rancangan === 'Disetujui' ? 'success' : ($selectedRancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $selectedRancangan->status_rancangan ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Nota Dinas</th>
                                                    <td>
                                                        <a href="{{ $selectedRancangan->nota_dinas_pd ? asset('storage/' . $selectedRancangan->nota_dinas_pd) : '#' }}"
                                                            target="_blank">
                                                            <i class="bi bi-file-earmark-text mr-2 text-warning"></i>
                                                            {{ $selectedRancangan->nota_dinas_pd ? 'Download Nota' : 'Tidak Ada Nota' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">File Rancangan</th>
                                                    <td>
                                                        <a href="{{ $selectedRancangan->rancangan ? asset('storage/' . $selectedRancangan->rancangan) : '#' }}"
                                                            target="_blank">
                                                            <i class="bi bi-file-earmark-text mr-2 text-primary"></i>
                                                            {{ $selectedRancangan->rancangan ? 'Download Rancangan' : 'Tidak Ada Rancangan' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Matrik</th>
                                                    <td>
                                                        <a href="{{ $selectedRancangan->matrik ? asset('storage/' . $selectedRancangan->matrik) : '#' }}"
                                                            target="_blank">
                                                            <i
                                                                class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                            {{ $selectedRancangan->matrik ? 'Download Matrik' : 'Tidak Ada Matrik' }}
                                                        </a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th class="info-text">Bahan Pendukung</th>
                                                    <td>
                                                        @if ($selectedRancangan->bahan_pendukung)
                                                            <a href="{{ asset('storage/' . $selectedRancangan->bahan_pendukung) }}"
                                                                target="_blank" class="d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
                                                                <span>Download Bahan</span>
                                                            </a>
                                                        @else
                                                            <span class="text-muted d-flex align-items-center">
                                                                <i class="bi bi-file-earmark-x mr-2 text-danger"></i>
                                                                <span>File Tidak Tersedia</span>
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Status Berkas</th>
                                                    <td>
                                                        <mark
                                                            class="badge-{{ $selectedRancangan->status_berkas === 'Disetujui' ? 'success' : ($selectedRancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                            {{ $selectedRancangan->status_berkas ?? 'N/A' }}
                                                        </mark>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Tanggal Berkas Disetujui</th>
                                                    <td class="info-text">
                                                        {{ $selectedRancangan->tanggal_berkas_disetujui
                                                            ? \Carbon\Carbon::parse($selectedRancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                            : 'N/A' }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Catatan Berkas</th>
                                                    <td class="wrap-text">
                                                        {{ $selectedRancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{--  Detail Penelitian --}}
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm">
                                    <div class="card-header">
                                        <h4 class="mb-0">Detail Penelitian</h4>
                                    </div>
                                    <div class="card-body table-responsive modal-table mt--3">
                                        <table class="table table-sm table-borderless">
                                            <tbody>
                                                @foreach ($selectedRancangan->revisi as $revisi)
                                                    <tr>
                                                        <th class="info-text w-25">Status Revisi</th>
                                                        <td
                                                            class="wrap-text
                                                            w-75">
                                                            <mark
                                                                class="badge-{{ $revisi->status_revisi === 'Direvisi' ? 'success' : ($revisi->status_revisi === 'Menunggu Revisi' ? 'warning' : 'danger') }} badge-pill">
                                                                {{ $revisi->status_revisi }}
                                                            </mark>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Revisi</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->tanggal_revisi ? \Carbon\Carbon::parse($revisi->tanggal_revisi)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Peneliti</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->peneliti->nama_user ?? 'Belum Ditentukan' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Tanggal Peneliti Ditunjuk</th>
                                                        <td class="wrap-text w-75">
                                                            {{ $revisi->tanggal_peneliti_ditunjuk ? \Carbon\Carbon::parse($revisi->tanggal_peneliti_ditunjuk)->translatedFormat('d F Y, H:i') : 'Belum Ditentukan' }}
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th class="info-text w-25">Revisi Matrik</th>
                                                        <td class="wrap-text w-75">
                                                            @if ($revisi->revisi_matrik)
                                                                <a href="{{ asset('storage/' . $revisi->revisi_matrik) }}"
                                                                    target="_blank" class="d-flex align-items-center">
                                                                    <i
                                                                        class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                                    <span>Download Matrik Revisi</span>
                                                                </a>
                                                            @else
                                                                <span class="text-muted d-flex align-items-center">
                                                                    <i
                                                                        class="bi bi-file-earmark-x mr-2 text-danger"></i>
                                                                    <span>Matrik Tidak Tersedia</span>
                                                                </span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th>Catatan Revisi</th>
                                                        <td class="wrap-text">
                                                            {{ $revisi->catatan_revisi ?? 'Tidak Ada Catatan' }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    {{-- Select Dropdown dengan Select2 --}}
                                    <div class="card-body">
                                        {{-- Penjelasan Pilihan Peneliti --}}
                                        @if ($selectedRancangan && $selectedRancangan->revisi->first()?->id_user)
                                            {{-- Alert Peneliti Sudah Dipilih --}}
                                            <div class="alert alert-default " role="alert">
                                                <i class="bi bi-info-circle"></i>
                                                Peneliti
                                                <strong>{{ $selectedRancangan->revisi->first()?->peneliti->nama_user }}</strong>
                                                telah ditetapkan sebagai peneliti.
                                                Silahkan cek menu <strong>Peneliti Ditugaskan</strong> untuk informasi
                                                lebih lanjut.
                                            </div>
                                        @else
                                            {{-- Form Pilih Peneliti --}}
                                            <p class="info-text description">
                                                Silahkan pilih peneliti dari daftar di bawah ini untuk menugaskan
                                                rancangan yang sedang diproses.
                                            </p>
                                            <div class="form-group">
                                                <label for="peneliti">
                                                    <h4>Pilih Peneliti</h4>
                                                </label>
                                                {{-- Dropdown dengan Select2 --}}
                                                <select id="peneliti" class="form-control select2"
                                                    wire:model="selectedPeneliti">
                                                    <option hidden>Pilih Peneliti...</option>
                                                    @foreach ($listPeneliti as $peneliti)
                                                        <option value="{{ $peneliti->id }}">
                                                            {{ $peneliti->nama_user }}</option>
                                                    @endforeach
                                                </select>
                                                @error('selectedPeneliti')
                                                    <span class="text-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <button class="btn btn-neutral" data-dismiss="modal">
                                                <i class="bi bi-x-circle"></i> Batal
                                            </button>

                                            {{-- Tombol Simpan dengan wire:loading --}}
                                            <button class="btn btn-primary" wire:click="assignPeneliti"
                                                wire:loading.attr="disabled">
                                                <i class="bi bi-check-circle" wire:loading.remove></i>
                                                <span wire:loading.remove>Simpan</span>
                                                <i class="spinner-border spinner-border-sm text-light" wire:loading></i>
                                                <span wire:loading>Memproses...</span>
                                            </button>
                                        @endif

                                    </div>

                                    <div class="modal-footer">
                                        {{-- Tombol Batal --}}
                                        @if (!$selectedRancangan->revisi->first()?->peneliti)
                                        @else
                                            <button class="btn btn-outline-warning" data-dismiss="modal"
                                                wire:click="refreshData">
                                                <i class="bi bi-x-circle"></i> Tutup
                                        @endif
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="modal-content">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="modal-title" id="uploadRevisiModalLabel">Notification Upload Revisi</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <!-- Tambahkan wrapper untuk center align -->
                            <div class="d-flex justify-content-center align-items-center" style="min-height: 150px;">
                                <div class="col-md-10 alert alert-default text-center" role="alert">
                                    <i class="bi bi-exclamation-triangle-fill"></i>
                                    Rancangan ini belum disetujui! Tidak dapat melakukan tindakan ini. Silahkan hubungi
                                    Admin untuk informasi lebih lanjut.
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button class="btn btn-neutral" data-dismiss="modal">
                                <i class="bi bi-x-circle"></i> Tutup
                            </button>
                        </div>
                    </div>
                @endif
            @else
                <div class="card">
                    <div class="d-flex justify-content-center align-items-start"
                        style="min-height: 200px; padding-top: 50px;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-3 info-text">Sedang memuat data, harap tunggu...</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    {{-- Script --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            window.Livewire.on('closeNotificationPilihPeneliti', () => {

                $('#notificationPilihPeneliti').modal('hide');
            });


            window.addEventListener('swal:notif', function(event) {

                const data = event.detail[0];
                $('#modalPersetujuan').modal('hide');

                // Tampilkan SweetAlert
                Swal.fire({
                    icon: data.type, // Bisa 'success', 'error', 'warning', 'info', atau 'question'
                    text: data.message, // Pesan tambahan (opsional)
                    toast: true, // Mengaktifkan toast
                    position: 'top-end', // Posisi toast ('top', 'top-start', 'top-end', 'center', 'bottom', dll.)
                    showConfirmButton: false, // Tidak menampilkan tombol konfirmasi
                    timer: 3000, // Waktu toast tampil (dalam milidetik)
                    timerProgressBar: true, // Menampilkan progress bar pada timer
                });
            });
            // Sweeet Alert 2
            window.Livewire.on('swal:denied', (data) => {

                // Jika data adalah array, akses elemen pertama
                if (Array.isArray(data)) {
                    data = data[0];
                }

                Swal.fire({
                    icon: data.type,
                    title: data.title,
                    text: data.message,
                    showConfirmButton: true,
                });
            });

        });
    </script>
    </div>
