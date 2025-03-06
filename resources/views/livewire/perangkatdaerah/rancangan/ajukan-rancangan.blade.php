<div>
    <button class="btn btn-outline-default" data-toggle="modal" data-target="#ajukanRancanganModal">
        <i class="bi bi-send-plus mr-2"></i>Ajukan
        Rancangan Baru
    </button>

    {{-- Modal Ajukan Rancangan --}}
    <div wire:ignore.self class="modal fade" id="ajukanRancanganModal" tabindex="-1" role="dialog"
        aria-labelledby="ajukanRancanganModalLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="submit">
                    <div class="modal-header border-bottom">
                        <h5 class="modal-title" id="ajukanRancanganModalLabel">Ajukan Rancangan Baru</h5>
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
                            <label for="tentang" class="form-label font-weight-bold">Tentang</label>
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
                                <label for="tanggalNota" class="form-control-label font-weight-bold">Tanggal Nota
                                    <small class="text-muted d-block">Pastikan Isi dengan benar karena akan di cetak
                                        dalam nota dinas hasil.</small>
                                </label>
                                <input type="date" class="form-control" wire:model="tanggalNota" required />
                                @error('tanggalNota')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Input Nomor Nota --}}
                            <div class="mb-3 col-md-6">
                                <label for="nomorNota" class="form-control-label font-weight-bold">Nomor Nota
                                    <small class="text-muted d-block">Pastikan Isi dengan benar karena akan di cetak
                                        dalam nota dinas hasil.</small>
                                </label>
                                <input type="text" class="form-control" wire:model="nomorNota"
                                    placeholder="Masukkan Nomor Nota (misal: NOTA-2023-001)" required />
                                @error('nomorNota')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="nomorNota" class="form-control-label font-weight-bold text-warning">Perhatikan
                                Ini , Untuk matrik download file yang ada di Template (Bahan Penting)
                                <small class="text-muted d-block">File Rancangan dan Matrik berupa word selain itu
                                    PDF</small>
                            </label>
                        </div>

                        {{-- Input File (Rancangan, Matrik, Nota Dinas, Bahan Pendukung) --}}
                        @foreach (['rancangan', 'matrik', 'nota_dinas_pd', 'bahanPendukung'] as $fileField)
                            <div class="mb-4">
                                <label class="font-weight-bold form-control-label">
                                    <i class="bi bi-file-earmark text-primary"></i>
                                    {{ ucfirst(str_replace('_', ' ', $fileField)) }}
                                    <small class="text-muted d-block">Unggah dokumen (max:
                                        5MB).</small>
                                </label>

                                <input type="file" class="form-control" wire:model="{{ $fileField }}"
                                    accept=".pdf, .doc, .docx" wire:change="resetError('{{ $fileField }}')"
                                    {{ $$fileField ? 'disabled' : '' }}
                                    style="{{ $$fileField ? 'background-color: #e9ecef; cursor: not-allowed; opacity: 0.6;' : '' }}">

                                {{-- Indikator Loading --}}
                                <div wire:loading wire:target="{{ $fileField }}" class="text-info mt-2">
                                    <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                                </div>

                                {{-- Preview file & tombol hapus --}}
                                @if ($$fileField)
                                    <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                        <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                        <span class="flex-grow-1">{{ $$fileField->getClientOriginalName() }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                            wire:click="removeFile('{{ $fileField }}')">
                                            <i class="bi bi-trash"></i> Hapus File
                                        </button>
                                    </div>
                                @endif

                                @error($fileField)
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            <i class="bi bi-backspace mr-2"></i>
                            Batal
                        </button>
                        <button class="btn btn-outline-default" type="submit" wire:loading.attr="disabled"
                            wire:target="submit"
                            {{ empty($jenisRancangan) || empty($tentang) || empty($tanggalNota) || empty($nomorNota) || empty($rancangan) ? 'disabled' : '' }}>
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
