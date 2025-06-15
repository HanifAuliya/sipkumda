{{-- Modal Unggah Revisi --}}
<div wire:ignore.self class="modal fade" id="notificationRevisi" tabindex="-1" role="dialog"
    aria-labelledby="notificationRevisi" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg" role="document">
        @if ($selectedRevisi)
            @if ($selectedRevisi->status_validasi === 'Menunggu Validasi')
                <div class="modal-content">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title" id="uploadRevisiModalLabel">Notification Upload Revisi</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            wire:click="resetForm">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Alert Informasi --}}
                        <div class="alert alert-default d-flex align-items-center mb-4" role="alert">
                            <i class="bi bi-info-circle-fill mr-2"></i>
                            <span>
                                Rancangan telah <strong>Direvisi </strong>& Status revisi saat ini adalah
                                <strong>"Menunggu Validasi"</strong>.
                                Silakan masuk ke menu <strong>Upload Revisi -> Riwayat Revisi</strong> untuk melihat
                                detail
                                lebih lanjut.
                            </span>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                                wire:click="resetForm">
                                Tutup
                            </button>
                        </div>
                    </div>
                </div>
            @else
                <div class="modal-content">
                    <form wire:submit.prevent="notifUploadRevisi">
                        <div class="modal-header border-bottom">
                            <h5 class="modal-title" id="uploadRevisiModalLabel">Notification Upload Revisi</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                                wire:click="resetForm">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            {{-- Informasi Rancangan --}}
                            @if ($selectedRevisi && $selectedRevisi->rancangan)
                                <table class="table table-sm table-borderless">
                                    <tbody>
                                        <tr>
                                            <th class="info-text w-25"> No Rancangan</th>
                                            <td class="wrap-text w-75">
                                                {{ $selectedRevisi->rancangan->no_rancangan ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Jenis Rancangan</th>
                                            <td class="wrap-text w-75"><mark
                                                    class="badge badge-{{ $selectedRevisi->rancangan->jenis_rancangan === 'Peraturan Bupati' ? 'primary' : 'success' }} badge-pill">
                                                    {{ $selectedRevisi->rancangan->jenis_rancangan ?? 'N/A' }}
                                                </mark></td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25"> Tentang</th>
                                            <td class="wrap-text w-75"> {{ $selectedRevisi->rancangan->tentang ?? '-' }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">Nota Dinas dari PD</th>
                                            <td class="wrap-text w-75">
                                                <a href="{{ url('/view-private/rancangan/nota_dinas/' . basename($selectedRevisi->rancangan->nota_dinas_pd ?? '')) }}"
                                                    target="_blank">
                                                    <i class="bi bi-file-earmark-pdf text-default"></i>
                                                    Nota Dinas
                                                </a>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th class="info-text w-25">File Rancangan </th>
                                            <td class="wrap-text w-75">
                                                <a href="{{ url('/view-private/rancangan/rancangan/' . basename($selectedRevisi->rancangan->rancangan ?? '')) }}"
                                                    target="_blank">
                                                    <i class="bi bi-file-earmark-pdf text-default"></i>
                                                    File Rancangan
                                                </a>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            @endif


                            {{-- Input File Revisi Matrik --}}
                            <div class="mb-4">
                                <label for="revisiMatrik" class="font-weight-bold form-control-label">
                                    <i class="bi bi-file-earmark-pdf text-primary"></i> File Revisi Matrik
                                    <small class="text-muted d-block">Unggah dokumen matrik dalam format PDF (max:
                                        10 MB).</small>
                                </label>
                                <input type="file" class="form-control" wire:model="revisiMatrik" accept=".pdf" />
                                <div wire:loading wire:target="revisiMatrik" class="text-info mt-2">
                                    <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                                </div>
                                @error('revisiMatrik')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Catatan Revisi --}}
                            <div class="mb-4">
                                <label for="catatanRevisi" class="font-weight-bold">Catatan Revisi</label>
                                <textarea id="catatanRevisi" class="form-control" wire:model="catatanRevisi" rows="4"
                                    placeholder="Tambahkan catatan revisi jika diperlukan"></textarea>
                                @error('catatanRevisi')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                                wire:click="resetForm">
                                <i class="bi bi-backspace mr-2"></i> Batal
                            </button>
                            <button class="btn btn-outline-default" type="submit" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="notifUploadRevisi"> <i
                                        class="bi bi-file-earmark-arrow-up mr-2"></i>Upload</span>
                                <span wire:loading wire:target="notifUploadRevisi">
                                    <i class="spinner-border spinner-border-sm"></i> Memproses...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            @endif
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
    <script>
        document.addEventListener('DOMContentLoaded', () => {

            window.addEventListener('swal:notif', function(event) {
                const data = event.detail[0];

                Swal.fire({
                    icon: data.type || 'info',
                    title: data.title || 'Informasi',
                    text: data.message || 'Tidak ada pesan yang diterima.',
                    showConfirmButton: true,
                });
            });
        });
    </script>
</div>
