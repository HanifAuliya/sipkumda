<div>
    <button class="btn btn-outline-primary " data-toggle="modal" data-target="#modalAjukanFasilitasi">
        <i class="ni ni-send"></i> Ajukan Fasilitasi
    </button>
    <!-- Modal Ajukan Fasilitasi -->
    <div class="modal fade" id="modalAjukanFasilitasi" tabindex="-1" role="dialog"
        aria-labelledby="modalAjukanFasilitasiLabel" wire:ignore.self data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                {{-- Header Modal --}}
                <div class="modal-header">
                    <h5 class="modal-title" id="modalAjukanFasilitasiLabel">Ajukan Fasilitasi</h5>

                </div>

                {{-- Body Modal --}}
                <div class="modal-body">
                    {{-- Dropdown Pilih Nomor Rancangan --}}
                    <div class="form-group">
                        <label for="rancanganId">Pilih Nomor Rancangan</label>
                        <select id="rancanganId" class="form-control" wire:model.lazy="rancanganId">
                            <option value="" hidden>-- Pilih Nomor Rancangan --</option>
                            @forelse ($rancanganOptions as $id => $rancangan)
                                <option value="{{ $id }}">{{ $rancangan }}</option>
                            @empty
                                <option value="" disabled>Tidak ada rancangan tersedia</option>
                            @endforelse
                        </select>
                        @error('rancanganId')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror

                        {{-- Pemberitahuan jika dropdown kosong --}}
                        @if (empty($rancanganOptions))
                            <small class="text-danger mt-2 d-block">Tidak ada rancangan yang dapat diajukan!</small>
                        @endif
                    </div>

                    {{-- Upload File Rancangan --}}
                    <div class="form-group" x-data="{ showFileInput: @entangle('rancanganId') }">
                        <template x-if="showFileInput">
                            <div>
                                <label for="fileRancangan">Upload File Rancangan</label>
                                <input type="file" id="fileRancangan" class="form-control" wire:model="fileRancangan"
                                    accept="application/pdf" wire:change="resetError('fileRancangan')">

                                {{-- Penjelasan tambahan --}}
                                <small class="form-text text-muted">
                                    Upload file rancangan hasil revisi dari peneliti yang telah diperbaiki dan
                                    diterapkan
                                    untuk pengajuan rancangan.
                                </small>

                                {{-- Loading saat file diunggah --}}
                                <div wire:loading wire:target="fileRancangan" class="text-primary mt-2">
                                    <i class="spinner-border spinner-border-sm"></i> Mengunggah file...
                                </div>

                                @error('fileRancangan')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Footer Modal --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"
                        wire:click="resetFormFasilitasi">
                        <i class="bi bi-x-circle"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="submit" wire:loading.attr="disabled">
                        <i class="bi bi-send"></i> {{-- Icon Bootstrap 5 --}}
                        <span wire:loading.remove wire:target="submit">Ajukan</span>
                        <span wire:loading wire:target="submit">
                            <i class="spinner-border spinner-border-sm"></i> Mengajukan...
                        </span>
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>
