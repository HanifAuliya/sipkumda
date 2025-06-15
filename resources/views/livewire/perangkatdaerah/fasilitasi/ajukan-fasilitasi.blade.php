<div>
    <button class="btn btn-outline-primary " data-toggle="modal" data-target="#modalAjukanFasilitasi">
        <i class="ni ni-send"></i> Ajukan Fasilitasi
    </button>
    <!-- Modal Ajukan Fasilitasi -->
    <div class="modal fade" id="modalAjukanFasilitasi" tabindex="-1" role="dialog"
        aria-labelledby="modalAjukanFasilitasiLabel" wire:ignore.self data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
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

                        {{-- Loading saat rancanganOptions masih kosong --}}
                        <div wire:loading wire:target="rancanganOptions" class="text-primary">
                            <i class="spinner-border spinner-border-sm"></i> Memuat data rancangan...
                        </div>

                        <select id="rancanganId" class="form-control" wire:model.lazy="rancanganId"
                            wire:loading.attr="disabled">
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

                                {{-- Loading jika rancanganId belum dipilih --}}
                                <div wire:loading wire:target="rancanganId" class="text-primary">
                                    <i class="spinner-border spinner-border-sm"></i> Menunggu pemilihan rancangan...
                                </div>

                                {{-- Input File --}}
                                <input type="file" id="fileRancangan" class="form-control" wire:model="fileRancangan"
                                    accept="application/pdf" wire:change="resetError('fileRancangan')"
                                    {{ $fileRancangan ? 'disabled' : '' }}
                                    style="{{ $fileRancangan ? 'background-color: #e9ecef; cursor: not-allowed; opacity: 0.6;' : '' }}">
                                {{-- Styling disabled --}}

                                {{-- Penjelasan tambahan --}}
                                <small class="form-text text-muted">
                                    Upload file rancangan hasil revisi dari peneliti yang telah diperbaiki dan
                                    diterapkan untuk pengajuan rancangan dengan format <small class="text-danger">PDF
                                        max:20mb</small>.
                                </small>

                                {{-- Loading saat file diunggah --}}
                                <div wire:loading wire:target="fileRancangan" class="text-primary mt-2">
                                    <i class="spinner-border spinner-border-sm"></i> Mengunggah file...
                                </div>

                                {{-- Preview file yang telah diunggah --}}
                                @if ($fileRancangan)
                                    <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                        <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                        <span class="flex-grow-1">{{ $fileRancangan->getClientOriginalName() }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                            wire:click="removeFile">
                                            <i class="bi bi-trash"></i> Hapus File
                                        </button>
                                    </div>
                                @endif

                                @error('fileRancangan')
                                    <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>


                        </template>
                    </div>

                </div>

                {{-- Footer Modal --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                        wire:click="resetFormFasilitasi">
                        <i class="bi bi-backspace"></i> Batal
                    </button>
                    <button type="button" class="btn btn-outline-default" wire:click="submit"
                        wire:loading.attr="disabled"
                        {{ empty($rancanganId) || empty($fileRancangan) ? 'disabled' : '' }}>
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
