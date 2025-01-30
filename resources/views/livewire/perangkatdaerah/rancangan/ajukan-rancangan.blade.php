<div>
    <button class="btn btn-outline-default" data-toggle="modal" data-target="#ajukanRancanganModal"> <i
            class="bi bi-send-plus mr-2"></i>Ajukan
        Rancangan Baru</button>
    {{-- Modal Ajukan Rancangan --}}
    <div wire:ignore.self class="modal fade" id="ajukanRancanganModal" tabindex="-1" role="dialog"
        aria-labelledby="ajukanRancanganModalLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="submit">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title" id="ajukanRancanganModalLabel">Ajukan Rancangan Baru</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"
                            wire:click="resetForm">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        {{-- Pilih Jenis Rancangan --}}
                        <div class="mb-3">
                            <label for="jenisRancangan" class="form-label font-weight-bold">
                                Jenis Rancangan
                            </label>
                            <select class="form-control" wire:model="jenisRancangan" required>
                                <option hidden>Pilih Jenis Rancangan</option>
                                <option value="Peraturan Bupati">Peraturan Bupati</option>
                                <option value="Surat Keputusan">Surat Keputusan</option>
                            </select>
                            @error('jenisRancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        {{-- Tentang --}}
                        <div class="mb-3">
                            <label for="tentang" class="form-label font-weight-bold">Tentang
                            </label>
                            <input type="text" class="form-control" wire:model="tentang"
                                placeholder="Tentang Rancangan misal (Pemberian Insentif Pemungutan Pajak Daerah)"
                                required />
                            @error('tentang')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                        <div class="row">
                            {{-- Input Tanggal Nota --}}
                            <div class="mb-3 col-md-6">
                                <label for="tanggalNota" class="form-label font-weight-bold">Tanggal Nota</label>
                                <input type="date" class="form-control" wire:model="tanggalNota" required />
                                @error('tanggalNota')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Input Nomor Nota --}}
                            <div class="mb-3 col-md-6">
                                <label for="nomorNota" class="form-label font-weight-bold">Nomor Nota</label>
                                <input type="text" class="form-control" wire:model="nomorNota"
                                    placeholder="Masukkan Nomor Nota (misal: NOTA-2023-001)" required />
                                @error('nomorNota')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>


                        {{-- Input File Rancangan --}}
                        <div class="mb-4">
                            <label for="rancangan" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> File Rancangan
                                <small class="text-muted d-block">Unggah dokumen rancangan dalam format PDF (max:
                                    5mb).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="rancangan" accept="application/pdf"
                                wire:change="resetError('rancangan')" />
                            {{-- Indikator Loading --}}
                            <div wire:loading wire:target="rancangan" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah File Rancangan...
                            </div>
                            @error('rancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Input File Matrik --}}
                        <div class="mb-4">
                            <label for="matrik" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> Matrik Rancangan
                                <small class="text-muted d-block">Unggah matrik dokumen dalam format PDF (max:
                                    5mb).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="matrik"
                                wire:change="resetError('matrik')" />
                            {{-- Indikator Loading --}}
                            <div wire:loading wire:target="matrik" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Matrik Rancangan...
                            </div>
                            @error('matrik')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Input Nota Dinas --}}
                        <div class="mb-4">
                            <label for="nota_dinas_pd" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> Nota Dinas
                                <small class="text-muted d-block">Unggah dokumen nota dinas dalam format PDF (max:
                                    5mb).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="nota_dinas_pd"
                                accept="application/pdf" wire:change="resetError('nota_dinas_pd')" />
                            {{-- Indikator Loading --}}
                            <div wire:loading wire:target="nota_dinas_pd" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Nota Dinas...
                            </div>
                            @error('nota_dinas_pd')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Input Bahan Pendukung --}}
                        <div class="mb-4">
                            <label for="bahanPendukung" class="font-weight-bold form-control-label">
                                <i class="bi bi-file-earmark-pdf text-primary"></i> Bahan Pendukung (Opsional)
                                <small class="text-muted d-block">Unggah dokumen bahan pendukung dalam format PDF (max:
                                    5mb).</small>
                            </label>
                            <input type="file" class="form-control" wire:model="bahanPendukung"
                                accept="application/pdf" wire:change="resetError('bahanPendukung')" />
                            {{-- Indikator Loading --}}
                            <div wire:loading wire:target="bahanPendukung" class="text-info mt-2">
                                <i class="spinner-border spinner-border-sm"></i> Mengunggah Bahan Pendukung...
                            </div>
                            @error('bahanPendukung')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            <i class="bi bi-backspace mr-2"></i>
                            Batal
                        </button>
                        <button class="btn btn-outline-default" type="submit" wire:loading.attr="disabled"
                            wire:target="submit">
                            <span wire:loading.remove wire:target="submit">
                                <i class="bi bi-file-earmark-arrow-up mr-2"></i>
                                Ajukan
                            </span>
                            <span wire:loading wire:target="submit">
                                <i class="spinner-border spinner-border-sm"></i> Memproses...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
