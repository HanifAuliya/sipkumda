<div wire:ignore.self class="modal fade" id="adminModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            {{-- Header Modal --}}
            <div class="modal-header flex-column align-items-start" style="border-bottom: 2px solid #dee2e6;">
                <h4 class="modal-title w-100 mb-2">Persetujuan Rancangan</h4>
                <p class="description mb-0 w-100 info-text">
                    Silakan cek informasi rancangan di bawah ini, termasuk file yang diajukan, lalu pilih status
                    persetujuan.
                </p>
                <button type="button" class="close position-absolute" style="top: 10px; right: 10px;"
                    data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            {{-- Body Modal --}}
            <div class="modal-body">
                @if ($rancangan)
                    <div class="row">
                        {{-- Informasi Utama --}}
                        <div class="col-md-6 ">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">Informasi Utama</h4>
                                </div>
                                <div class="card-body">
                                    <p class="description info-text mb-3">Berikut adalah informasi dasar dari rancangan
                                        yang
                                        diajukan. Pastikan semua informasi sudah sesuai.</p>
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <th class="info-text">Nomor</th>
                                                <td>{{ $rancangan->no_rancangan ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Jenis</th>
                                                <td>
                                                    <mark
                                                        class="info-text badge-{{ $rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                        {{ $rancangan->jenis_rancangan ?? 'N/A' }}
                                                    </mark>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">Tentang</th>
                                                <td>{{ $rancangan->tentang ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">Tanggal Pengajuan</th>
                                                <td>{{ $rancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($rancangan->tanggal_pengajuan)->translatedFormat('d F Y') : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">User Pengaju</th>
                                                <td>{{ $rancangan->user->nama_user ?? 'N/A' }}</td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">Perangkat Daerah</th>
                                                <td>{{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">Status Rancangan</th>
                                                <td>
                                                    <span
                                                        class="badge badge-{{ $rancangan->status_rancangan === 'Disetujui' ? 'success' : ($rancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $rancangan->status_rancangan ?? 'N/A' }}
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        {{-- File Persetujuan --}}
                        <div class="col-md-6">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0">File Persetujuan</h4>
                                </div>
                                <div class="card-body">
                                    <p class="description info-text mb-3">Pastikan file yang diajukan sudah lengkap dan
                                        sesuai. Anda
                                        dapat mengunduh file untuk memverifikasinya.</p>
                                    <table class="table table-sm table-borderless">
                                        <tbody>
                                            <tr>
                                                <th class="info-text">Nota Dinas</th>
                                                <td>
                                                    <a href="{{ $rancangan->nota_dinas_pd ? asset('storage/' . $rancangan->nota_dinas_pd) : '#' }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-text mr-2 text-warning"></i>
                                                        {{ $rancangan->nota_dinas_pd ? 'Download Nota' : 'Tidak Ada Nota' }}
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">File Rancangan</th>
                                                <td>
                                                    <a href="{{ $rancangan->rancangan ? asset('storage/' . $rancangan->rancangan) : '#' }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-text mr-2 text-primary"></i>
                                                        {{ $rancangan->rancangan ? 'Download Rancangan' : 'Tidak Ada Rancangan' }}
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">Matrik</th>
                                                <td>
                                                    <a href="{{ $rancangan->matrik ? asset('storage/' . $rancangan->matrik) : '#' }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
                                                        {{ $rancangan->matrik ? 'Download Matrik' : 'Tidak Ada Matrik' }}
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th class="info-text">Bahan Pendukung</th>
                                                <td>
                                                    <a href="{{ $rancangan->bahan_pendukung ? asset('storage/' . $rancangan->bahan_pendukung) : '#' }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
                                                        {{ $rancangan->bahan_pendukung ? 'Download Bahan' : 'Tidak Ada Bahan' }}
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Status Berkas</th>
                                                <td class="info-text">
                                                    <span
                                                        class="badge badge-{{ $rancangan->status_berkas === 'Disetujui' ? 'success' : ($rancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                        {{ $rancangan->status_berkas ?? 'N/A' }}
                                                    </span>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Tanggal Berkas Disetujui</th>
                                                <td class="info-text">
                                                    {{ $rancangan->tanggal_berkas_disetujui
                                                        ? \Carbon\Carbon::parse($rancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y')
                                                        : 'N/A' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Catatan Berkas</th>
                                                <td class="info-text">
                                                    {{ $rancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pilihan Status dan Catatan Persetujuan --}}
                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm">
                                @if ($rancangan->status_berkas === 'Disetujui' || $rancangan->status_berkas === 'Ditolak')
                                    <div class="alert alert-{{ $rancangan->status_berkas === 'Disetujui' ? 'success' : 'danger' }} mb-0"
                                        role="alert" style="flex: 1; text-align: center;">
                                        <strong>{{ $rancangan->status_berkas }} !</strong> Rancangan
                                        Telah
                                        {{ $rancangan->status_berkas }} Silahkan Cek diHalaman Persetujuan sesuai
                                        dengan tab statusnya
                                    </div>
                                @else
                                    <div class="alert alert-warning mb-0" role="alert"
                                        style="flex: 1; text-align: center;">
                                        <strong> Berkas Rancangan {{ $rancangan->status_berkas }}!</strong>
                                        Periksa dan Lakuka Verifikasi
                                    </div>

                                    <div class="card-body">
                                        {{-- Informasi --}}
                                        <p class="description info-text mb-3 info-text">
                                            Pilih status persetujuan untuk rancangan ini. Tambahkan catatan
                                            untuk memberikan masukan atau alasan penolakan.
                                        </p>

                                        {{-- Pilihan Persetujuan Berkas --}}
                                        <div class="form-group">
                                            <label class="font-weight-bold form-control-label">Pilih Persetujuan Berkas
                                                di
                                                bawah ini:</label>
                                            <div class="d-flex align-items-center mt-2">
                                                {{-- Radio Button Setujui --}}
                                                <div class="custom-control custom-radio mr-4">
                                                    <input type="radio" id="setujui" name="statusBerkas"
                                                        class="custom-control-input" wire:model="statusBerkas"
                                                        value="Disetujui" @disabled(in_array($statusBerkas, ['Disetujui', 'Ditolak']))>
                                                    <label class="custom-control-label" for="setujui">
                                                        <i class="bi bi-check-circle-fill text-success"></i> Setujui
                                                        Berkas
                                                    </label>
                                                </div>

                                                {{-- Radio Button Tolak --}}
                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="tolak" name="statusBerkas"
                                                        class="custom-control-input" wire:model="statusBerkas"
                                                        value="Ditolak" @disabled(in_array($statusBerkas, ['Disetujui', 'Ditolak']))>
                                                    <label class="custom-control-label" for="tolak">
                                                        <i class="bi bi-x-circle-fill text-danger"></i> Tolak Berkas
                                                    </label>
                                                </div>
                                            </div>

                                            {{-- Error Message --}}
                                            @error('statusBerkas')
                                                <span class="text-danger mt-2 d-block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- Catatan --}}
                                        <div class="form-group">
                                            <label class="font-weight-bold form-control-label">Tambahkan Catatan <small
                                                    class="text-danger"> wajib</small></label>
                                            <textarea wire:model.defer="catatan" class="form-control mb-3" rows="3" placeholder="Tambahkan catatan..."
                                                @disabled(in_array($statusBerkas, ['Disetujui', 'Ditolak']) && !is_null($catatan))></textarea>
                                            @error('catatan')
                                                <span class="text-danger mt-2 d-block">{{ $message }}</span>
                                            @enderror
                                        </div>

                                        {{-- Tombol --}}
                                        <div class="d-flex justify-content-end">
                                            {{-- Tombol Tutup --}}
                                            <button type="button" class="btn btn-secondary mr-3"
                                                data-dismiss="modal" wire:click="resetForm">
                                                <i class="bi bi-x-circle-lg"></i> Batal
                                            </button>

                                            @if ($rancangan->status_berkas === 'Disetujui' || $rancangan->status_berkas === 'Ditolak')
                                                {{-- Tombol Reset --}}
                                                <button class="btn btn-danger" wire:click="resetStatus"
                                                    wire:loading.attr="disabled">
                                                    <span wire:loading.remove><i
                                                            class="bi bi-arrow-counterclockwise"></i>
                                                        Reset Status</span>
                                                    <span wire:loading><i class="bi bi-hourglass-split"></i>
                                                        Memproses...</span>
                                                </button>
                                            @else
                                                {{-- Tombol Verifikasi --}}
                                                <button class="btn btn-success" wire:click="updateStatus"
                                                    wire:loading.attr="disabled">
                                                    <span wire:loading.remove><i class="bi bi-check-circle"></i>
                                                        Verifikasi
                                                        Rancangan</span>
                                                    <span wire:loading><i class="bi bi-hourglass-split"></i>
                                                        Memproses...</span>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    {{-- Spinner Loading --}}
                    <div class="d-flex justify-content-center align-items-start"
                        style="min-height: 200px; padding-top: 50px;">
                        <div class="text-center">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                            <p class="mt-3 info-text">Sedang membuat data, harap tunggu...</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Script --}}
    <script>
        window.addEventListener('swal:modalPersetujuan', function(event) {
            // Ambil elemen pertama dari array
            const data = event.detail[0];

            // Tampilkan SweetAlert
            Swal.fire({
                icon: data.type, // Bisa 'success', 'error', 'warning', 'info', atau 'question'
                title: data.title, // Judul dari toast
                text: data.message, // Pesan tambahan (opsional)
                toast: true, // Mengaktifkan toast
                position: 'top-end', // Posisi toast ('top', 'top-start', 'top-end', 'center', 'bottom', dll.)
                showConfirmButton: false, // Tidak menampilkan tombol konfirmasi
                timer: 3000, // Waktu toast tampil (dalam milidetik)
                timerProgressBar: true, // Menampilkan progress bar pada timer
            });
        });
    </script>
</div>
