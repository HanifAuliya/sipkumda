<div wire:ignore.self class="modal fade" id="adminPersetujuanModal" tabindex="-1" role="dialog" data-backdrop="static"
    data-keyboard="false" data-keyboard="false">
    <div class="modal-dialog modal-xl no-style-modal" role="document">
        <div class="modal-content">
            @if ($rancangan)
                <div class="row mt-3">
                    {{-- Informasi Utama --}}
                    <div class="col-md-6 ">
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h4 class="mb-0">Informasi Utama</h4>
                            </div>
                            <div class="card-body">
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <th class="info-text w-25">Nomor</th>
                                            <td class="wrap-text w-75">{{ $rancangan->no_rancangan ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Jenis</th>
                                            <td class="wrap-text w-75">
                                                <mark
                                                    class="info-text badge-{{ $rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : '' }} badge-pill">
                                                    {{ $rancangan->jenis_rancangan ?? 'N/A' }}
                                                </mark>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Tentang</th>
                                            <td class="wrap-text w-75">{{ $rancangan->tentang ?? 'N/A' }} </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Tanggal Pengajuan</th>
                                            <td class="wrap-text w-75">
                                                {{ $rancangan->tanggal_pengajuan ? \Carbon\Carbon::parse($rancangan->tanggal_pengajuan)->translatedFormat('d F Y, H:i') : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">User Pengaju</th>
                                            <td class="wrap-text w-75">{{ $rancangan->user->nama_user ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Perangkat Daerah</th>
                                            <td class="wrap-text w-75">
                                                {{ $rancangan->user->perangkatDaerah->nama_perangkat_daerah ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Nomor Nota</th>
                                            <td class="wrap-text-td-70 ">
                                                {{ $rancangan->nomor_nota ?? 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>Tanggal Nota</th>
                                            <td>
                                                {{ $rancangan->tanggal_nota ? \Carbon\Carbon::parse($rancangan->tanggal_nota)->translatedFormat('d F Y') : 'N/A' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Status Rancangan</th>
                                            <td class="wrap-text w-75">
                                                <span
                                                    class="badge badge-{{ $rancangan->status_rancangan === 'Disetujui' ? 'success' : ($rancangan->status_rancangan === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                    {{ $rancangan->status_rancangan ?? 'N/A' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Nota Dinas</th>
                                            <td class="wrap-text w-75">
                                                @if ($rancangan->nota_dinas_pd)
                                                    <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($rancangan->nota_dinas_pd)) }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-text mr-2 text-warning"></i>
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
                                                @if ($rancangan->rancangan)
                                                    <a href="{{ url('/view-private/rancangan/rancangan/' . basename($rancangan->rancangan)) }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-text mr-2 text-primary"></i>
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
                                                @if ($rancangan->matrik)
                                                    <a href="{{ url('/view-private/rancangan/matrik/' . basename($rancangan->matrik)) }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-spreadsheet mr-2 text-success"></i>
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
                                                @if ($rancangan->bahan_pendukung)
                                                    <a href="{{ url('/view-private/rancangan/bahan_pendukung/' . basename($rancangan->bahan_pendukung)) }}"
                                                        target="_blank">
                                                        <i class="bi bi-file-earmark-pdf mr-2 text-danger"></i>
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
                                                <span
                                                    class="badge badge-{{ $rancangan->status_berkas === 'Disetujui' ? 'success' : ($rancangan->status_berkas === 'Ditolak' ? 'danger' : 'warning') }} badge-pill">
                                                    {{ $rancangan->status_berkas ?? 'N/A' }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Tanggal Berkas Disetujui</th>
                                            <td class="wrap-text w-75">
                                                {{ $rancangan->tanggal_berkas_disetujui
                                                    ? \Carbon\Carbon::parse($rancangan->tanggal_berkas_disetujui)->translatedFormat('d F Y, H:i')
                                                    : 'Belum disetujui' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Catatan Berkas</th>
                                            <td class="wrap-text w-75">
                                                {{ $rancangan->catatan_berkas ?? 'Tidak Ada Catatan' }}
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
                                        Periksa dan Lakukan Verifikasi
                                    </div>
                                    <div class="card shadow-sm mt-3">
                                        <div class="card-body">
                                            {{-- Informasi --}}
                                            <p class="description info-text mb-3 info-text">
                                                Pilih status persetujuan untuk rancangan ini. Tambahkan catatan
                                                untuk memberikan masukan atau alasan penolakan.
                                            </p>

                                            {{-- Pilihan Persetujuan Berkas --}}
                                            <div class="form-group">
                                                <label class="font-weight-bold form-control-label">Pilih Persetujuan
                                                    Berkas
                                                    di
                                                    bawah ini:</label>
                                                <div class="d-flex align-items-center mt-2">
                                                    {{-- Radio Button Setujui --}}
                                                    <div class="custom-control custom-radio mr-4">
                                                        <input type="radio" id="setujui" name="statusBerkas"
                                                            class="custom-control-input" wire:model="statusBerkas"
                                                            value="Disetujui">
                                                        <label class="custom-control-label" for="setujui">
                                                            <i class="bi bi-check-circle-fill text-success"></i> Setujui
                                                            Berkas
                                                        </label>
                                                    </div>

                                                    {{-- Radio Button Tolak --}}
                                                    <div class="custom-control custom-radio">
                                                        <input type="radio" id="tolak" name="statusBerkas"
                                                            class="custom-control-input" wire:model="statusBerkas"
                                                            value="Ditolak">
                                                        <label class="custom-control-label" for="tolak">
                                                            <i class="bi bi-x-circle-fill text-danger"></i> Tolak
                                                            Berkas
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
                                                <button type="button" class="btn btn-neutral mr-3"
                                                    data-dismiss="modal" wire:click="resetForm">
                                                    <i class="bi bi-x-circle-lg"></i> Batal
                                                </button>

                                                {{-- Tombol Verifikasi --}}
                                                <button class="btn btn-success" wire:click="updateStatus"
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
                                @endif
                                @if ($rancangan->status_berkas === 'Disetujui' || $rancangan->status_berkas === 'Ditolak')
                                    <div class="modal-footer d-flex justify-content-end">
                                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                                            wire:click="resetForm">Tutup </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card shadow-sm mt-3">
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
                </div>
            @endif

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
