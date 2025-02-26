<div>
    @if (Auth::user()->can('create', App\Models\DokumentasiProdukHukum::class))
        <div class="dropdown mb-3">
            <button class="btn btn-outline-default dropdown-toggle" type="button" id="dropdownTambahDokumentasi"
                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="bi bi-folder-plus mr-2"></i> Tambah Dokumentasi
            </button>
            <div class="dropdown-menu" aria-labelledby="dropdownTambahDokumentasi">
                <!-- Tambah Dokumentasi dari Rancangan -->
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalTambahDokumentasi">
                    <i class="bi bi-folder-plus mr-2"></i> Tambah Arsip Dokumentasi
                </a>
                <!-- Tambah Dokumentasi Manual -->
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#modalTambahManual">
                    <i class="bi bi-folder-symlink"></i></i> Tambah Arsip Sebelum ada Sistem
                </a>
            </div>
        </div>

    @endcan
    <div wire:ignore.self class="modal fade" id="modalTambahDokumentasi" tabindex="-1" role="dialog"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Dokumentasi Produk Hukum</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    {{-- Pilih Nomor Rancangan --}}
                    <div class="form-group">
                        <label>Pilih Nomor Rancangan</label>
                        <select class="form-control" wire:model="rancanganId">
                            <option hidden>Pilih Nomor Rancangan</option>
                            @forelse ($rancanganList as $rancangan)
                                <option value="{{ $rancangan->id }}">
                                    {{ $rancangan->rancangan->no_rancangan }} - {{ $rancangan->rancangan->tentang }}
                                </option>
                            @empty
                                <option disabled>Data tidak tersedia</option>
                            @endforelse
                        </select>
                        @error('rancanganId')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Gunakan Alpine.js untuk menampilkan input tambahan saat rancangan dipilih --}}
                    <div x-data="{ showFileInput: @entangle('rancanganId') }">
                        <template x-if="showFileInput">
                            <div>
                                <div class="form-group">
                                    <label for="nomor_produk_hukum">Nomor Produk Hukum</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control text-center" value="Nomor"
                                            disabled>
                                        <input type="text" id="nomor_produk_hukum"
                                            class="form-control text-center" wire:model.defer="nomor"
                                            placeholder="###" maxlength="3"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,3)">
                                        <input type="text" class="form-control text-center"
                                            value="Tahun {{ now()->year }}" disabled>
                                    </div>
                                    @error('nomor')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- Nomor Berita Daerah --}}
                                <div class="form-group">
                                    <label>Nomor Berita Daerah</label>
                                    <input type="text" class="form-control" wire:model.defer="nomor_berita"
                                        placeholder="Contoh: 12" maxlength="2"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,2)">
                                    @error('nomor_berita')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- Tahun Berita Daerah --}}
                                <div class="form-group">
                                    <label>Tahun Berita Daerah</label>
                                    <input type="text" class="form-control" wire:model.defer="tahun_berita"
                                        placeholder="Contoh: 2025" maxlength="4"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,4)">
                                    @error('tahun_berita')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- Tanggal Pengarsipan --}}
                                <div class="form-group">
                                    <label>Tanggal Pengarsipan</label>
                                    <input type="date" class="form-control"
                                        wire:model.defer="tanggal_pengarsipan">
                                    @error('tanggal_pengarsipan')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- Tanggal Penetapan --}}
                                <div class="form-group">
                                    <label>Tanggal Penetapan</label>
                                    <input type="date" class="form-control" wire:model.defer="tanggal_penetapan">
                                    @error('tanggal_penetapan')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- Upload File Baru --}}
                                <div class="form-group">
                                    <label class="font-weight-bold">Upload Berita Daerah (PDF)</label>
                                    <input type="file" class="form-control" wire:model="file_produk_hukum"
                                        wire:change="resetError" wire:loading.attr="disabled"
                                        accept="application/pdf" {{ $file_produk_hukum ? 'disabled' : '' }}
                                        style="{{ $file_produk_hukum ? 'background-color: #e9ecef; cursor: not-allowed; opacity: 0.6;' : '' }}">
                                    <div wire:loading wire:target="file_produk_hukum" class="text-warning mt-2">
                                        <i class="spinner-border spinner-border-sm"></i> Mengupload file...
                                    </div>
                                    @error('file_produk_hukum')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                    @if ($file_produk_hukum)
                                        <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                            <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                            <span
                                                class="flex-grow-1">{{ $file_produk_hukum->getClientOriginalName() }}</span>
                                            <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                                wire:click="removeFile">
                                                <i class="bi bi-trash"></i> Hapus File
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-warning" data-dismiss="modal">
                        <i class="bi bi-backspace-fill mr-2"></i>Batal
                    </button>
                    <button class="btn btn-primary" wire:click="store" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="store">
                            <i class="bi bi-save mr-2"></i> Simpan
                        </span>
                        <span wire:loading wire:target="store">
                            <i class="spinner-border spinner-border-sm"></i> Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="modalTambahManual" tabindex="-1" role="dialog"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Dokumentasi Produk Hukum Manual</h5>
                </div>
                <div class="modal-body">

                    {{-- Pilih Perangkat Daerah --}}
                    <div class="form-group">
                        <label>Pilih Perangkat Daerah</label>
                        <select class="form-control" wire:model.defer="perangkat_daerah_id">
                            <option hidden>Pilih Perangkat Daerah</option>
                            @foreach ($perangkatList as $perangkat)
                                <option value="{{ $perangkat->id }}">{{ $perangkat->nama_perangkat_daerah }}
                                </option>
                            @endforeach
                        </select>
                        @error('perangkat_daerah_id')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    {{-- Nomor Produk Hukum --}}
                    <div class="form-group">
                        <label for="nomor_produk_hukum">Nomor Produk Hukum</label>
                        <div class="input-group">
                            <input type="text" class="form-control text-center" value="Nomor" disabled>
                            <input type="text" id="nomor_produk_hukum" class="form-control text-center"
                                wire:model.defer="nomor" placeholder="###" maxlength="3"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,3)">
                            <input type="text" class="form-control text-center" wire:model.defer="tahun"
                                placeholder="Tahun" maxlength="4"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,4)">
                        </div>
                        @error('nomor')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        @error('tahun')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Nomor Berita Daerah --}}
                    <div class="form-group">
                        <label>Nomor Tahun Berita Daerah</label>
                        <div class="input-group">
                            <small>contoh : 12/2025</small>
                            <input type="text" class="form-control" wire:model.defer="nomor_berita"
                                placeholder="Contoh: 12" maxlength="2"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,2)">
                            <input type="text" class="form-control text-center" value="/" disabled>
                            <input type="text" class="form-control" wire:model.defer="tahun_berita"
                                placeholder="Contoh: 2025" maxlength="4"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,4)">
                        </div>
                        @error('nomor_berita')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        @error('tahun_berita')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>
                    {{-- Jenis Dokumentasi --}}
                    <div class="form-group">
                        <label>Jenis Dokumentasi</label>
                        <select class="form-control" wire:model.defer="jenis_dokumentasi">
                            <option value="">-- Pilih Jenis Dokumentasi --</option>
                            <option value="Keputusan Bupati">Keputusan Bupati</option>
                            <option value="Peraturan Bupati">Peraturan Bupati</option>
                        </select>
                        @error('jenis_dokumentasi')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>


                    {{-- Tentang Dokumentasi --}}
                    <div class="form-group">
                        <label>Tentang Dokumentasi</label>
                        <textarea class="form-control" wire:model.defer="tentang_dokumentasi"
                            placeholder="Contoh: Tentang Pengelolaan Sampah"></textarea>
                        @error('tentang_dokumentasi')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Tanggal Penetapan --}}
                    <div class="form-group">
                        <label>Tanggal Penetapan</label>
                        <input type="date" class="form-control" wire:model.defer="tanggal_penetapan">
                        @error('tanggal_penetapan')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    {{-- Upload File Baru --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Upload Berita Daerah (PDF)</label>
                        <input type="file" class="form-control" wire:model="file_produk_hukum"
                            wire:change="resetError" wire:loading.attr="disabled" accept="application/pdf"
                            {{ $file_produk_hukum ? 'disabled' : '' }}
                            style="{{ $file_produk_hukum ? 'background-color: #e9ecef; cursor: not-allowed; opacity: 0.6;' : '' }}">
                        <div wire:loading wire:target="file_produk_hukum" class="text-warning mt-2">
                            <i class="spinner-border spinner-border-sm"></i> Mengupload file...
                        </div>
                        @error('file_produk_hukum')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                        @if ($file_produk_hukum)
                            <div class="mt-2 p-2 border rounded bg-light d-flex align-items-center">
                                <i class="bi bi-file-earmark-pdf text-danger mr-2"></i>
                                <span class="flex-grow-1">{{ $file_produk_hukum->getClientOriginalName() }}</span>
                                <button type="button" class="btn btn-sm btn-outline-danger ml-2"
                                    wire:click="removeFile">
                                    <i class="bi bi-trash"></i> Hapus File
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-warning" data-dismiss="modal">
                        <i class="bi bi-backspace-fill mr-2"></i>Batal
                    </button>
                    <button class="btn btn-primary" wire:click="storeManual" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="storeManual">
                            <i class="bi bi-save mr-2"></i> Simpan
                        </span>
                        <span wire:loading wire:target="storeManual">
                            <i class="spinner-border spinner-border-sm"></i> Memproses...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
