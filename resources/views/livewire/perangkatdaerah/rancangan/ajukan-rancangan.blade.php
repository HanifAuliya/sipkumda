<div>
    <button class="btn btn-outline-default" data-toggle="modal" data-target="#ajukanRancanganModal">
        <i class="bi bi-send-plus mr-2"></i>Ajukan Rancangan Baru
    </button>

    <div wire:ignore.self class="modal fade" id="ajukanRancanganModal" tabindex="-1" role="dialog"
        aria-labelledby="ajukanRancanganModalLabel" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="submit">

                    <div class="modal-header border-bottom">
                        <h5 class="modal-title" id="ajukanRancanganModalLabel">
                            <i class="bi bi-file-earmark-plus mr-2"></i>Ajukan Rancangan Baru
                        </h5>
                    </div>

                    <div class="modal-body">

                        {{-- Jenis Rancangan --}}
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Jenis Rancangan</label>
                            <select class="form-control" wire:model="jenisRancangan" required>
                                <option hidden>Pilih Jenis Rancangan</option>
                                <option value="Peraturan Bupati">Peraturan Bupati</option>
                                <option value="Surat Keputusan">Surat Keputusan</option>
                            </select>
                            @error('jenisRancangan')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- Tentang --}}
                        <div class="mb-3">
                            <label class="form-label font-weight-bold">Tentang</label>
                            <input type="text" class="form-control" wire:model="tentang"
                                placeholder="Contoh: Pemberian Insentif Pemungutan Pajak Daerah" required />
                            @error('tentang')
                                <span class="text-danger small">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="row">
                            {{-- Tanggal Nota --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-control-label font-weight-bold">Tanggal Nota Dinas Perangkat Daerah
                                    <small class="text-muted d-block font-weight-normal">
                                        Akan dicetak di nota dinas hasil.
                                    </small>
                                </label>
                                <input type="date" class="form-control" wire:model="tanggalNota" required />
                                @error('tanggalNota')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>

                            {{-- Nomor Nota --}}
                            <div class="mb-3 col-md-6">
                                <label class="form-control-label font-weight-bold">Nomor Nota Dinas Perangkat Daerah
                                    <small class="text-muted d-block font-weight-normal">
                                        Akan dicetak di nota dinas hasil.
                                    </small>
                                </label>
                                <input type="text" class="form-control" wire:model="nomorNota"
                                    placeholder="Contoh: NOTA-2023-001" required />
                                @error('nomorNota')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        {{-- Keterangan format --}}
                        <div class="alert alert-warning py-2 mb-3">
                            <i class="bi bi-exclamation-triangle mr-1"></i>
                            <strong>Perhatikan:</strong> Untuk matrik gunakan template yang tersedia (Bahan Penting).
                            File <strong>Rancangan</strong> dan <strong>Matrik</strong> berformat Word (.docx),
                            selain itu PDF.
                        </div>

                        {{-- ===================================== --}}
                        {{-- UPLOAD FILE                           --}}
                        {{-- ===================================== --}}
                        @foreach (['rancangan', 'matrik', 'nota_dinas_pd', 'bahanPendukung'] as $fileField)
                            <div class="mb-4">
                                <label class="font-weight-bold form-control-label">
                                    <i class="bi bi-file-earmark text-primary mr-1"></i>
                                    {{ ucfirst(str_replace('_', ' ', $fileField)) }}
                                    @if ($fileField !== 'bahanPendukung')
                                        <span class="text-danger">*</span>
                                    @else
                                        <span class="text-muted font-weight-normal">(opsional)</span>
                                    @endif
                                    <small class="text-muted d-block font-weight-normal">Maks: 20MB.</small>
                                </label>

                                <input type="file" class="form-control" wire:model="{{ $fileField }}"
                                    accept=".pdf,.doc,.docx" wire:change="resetError('{{ $fileField }}')"
                                    {{ $$fileField ? 'disabled' : '' }}
                                    style="{{ $$fileField ? 'background-color:#e9ecef;cursor:not-allowed;opacity:0.6;' : '' }}">

                                {{-- Loading upload --}}
                                <div wire:loading wire:target="{{ $fileField }}" class="text-info mt-1 small">
                                    <i class="spinner-border spinner-border-sm"></i> Mengunggah...
                                </div>

                                {{-- Preview file --}}
                                @if ($$fileField)
                                    <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                        <i class="bi bi-file-earmark-check text-success mr-2"></i>
                                        <span
                                            class="flex-grow-1 small">{{ $$fileField->getClientOriginalName() }}</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                            wire:click="removeFile('{{ $fileField }}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                @endif

                                @error($fileField)
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        @endforeach

                        {{-- ===================================== --}}
                        {{-- HASIL PEMERIKSAAN AI (otomatis muncul --}}
                        {{-- setelah 3 file utama terupload)       --}}
                        {{-- ===================================== --}}

                        {{-- Loading: sedang memeriksa --}}
                        <div wire:loading
                            wire:target="updatedRancangan,updatedMatrik,updatedNotaDinasPd,jalankanPrediksi">
                            <div class="alert alert-light border d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm text-primary mr-3" role="status"></div>
                                <div>
                                    <strong>Memeriksa kelengkapan berkas...</strong>
                                    <div class="small text-muted">AI sedang menganalisis isi dokumen Anda.</div>
                                </div>
                            </div>
                        </div>

                        {{-- Gagal hubungi API --}}
                        @if ($gagalCek)
                            <div class="alert alert-secondary d-flex align-items-center mb-3">
                                <i class="bi bi-wifi-off mr-3 text-secondary" style="font-size:1.3rem;"></i>
                                <div>
                                    <strong>Pemeriksaan AI tidak tersedia</strong>
                                    <div class="small text-muted">
                                        Server AI tidak dapat dihubungi. Anda tetap bisa mengajukan,
                                        namun Admin akan melakukan verifikasi manual.
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Hasil: LENGKAP --}}
                        @if ($sudahDicek && $hasilPrediksi === 'Lengkap')
                            <div class="alert alert-success mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill mr-3 text-success" style="font-size:1.5rem;"></i>
                                    <div>
                                        <strong>Berkas Terdeteksi Lengkap ✓</strong>
                                        <div class="small mt-1">
                                            Semua indikator dokumen terdeteksi oleh AI.
                                            Anda dapat langsung mengajukan rancangan ini.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Hasil: TIDAK LENGKAP --}}
                        @if ($sudahDicek && $hasilPrediksi === 'Tidak Lengkap')
                            <div class="alert alert-warning mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-exclamation-triangle-fill mr-3 text-warning"
                                        style="font-size:1.5rem;"></i>
                                    <div>
                                        <strong>Berkas Terdeteksi Belum Lengkap</strong>
                                        <div class="small mt-1">
                                            AI mendeteksi beberapa indikator yang belum terpenuhi.
                                            Anda <strong>tetap dapat mengajukan</strong>, namun
                                            Admin akan mencatat kekurangan ini dan mungkin akan
                                            meminta perbaikan.
                                        </div>
                                    </div>
                                </div>

                                @if (count($catatanKurang) > 0)
                                    <hr class="my-2">
                                    <p class="mb-1 small font-weight-bold">
                                        <i class="bi bi-list-ul mr-1"></i>Indikator yang belum terpenuhi:
                                    </p>
                                    <ul class="mb-0 pl-4 small">
                                        @foreach ($catatanKurang as $catatan)
                                            <li>{{ $catatan }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        @endif

                    </div>{{-- end modal-body --}}

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            <i class="bi bi-backspace mr-2"></i>Batal
                        </button>

                        {{-- Tombol Ajukan: aktif selalu, tapi disable saat loading --}}
                        <button class="btn btn-outline-default" type="submit" wire:loading.attr="disabled"
                            wire:target="submit,jalankanPrediksi"
                            {{ empty($jenisRancangan) || empty($tentang) || empty($tanggalNota) || empty($nomorNota) || empty($rancangan) || empty($matrik) || empty($nota_dinas_pd) ? 'disabled' : '' }}>
                            <span wire:loading.remove wire:target="submit">
                                <i class="bi bi-file-earmark-arrow-up mr-2"></i>
                                @if ($sudahDicek && $hasilPrediksi === 'Tidak Lengkap')
                                    Tetap Ajukan
                                @else
                                    Ajukan
                                @endif
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
