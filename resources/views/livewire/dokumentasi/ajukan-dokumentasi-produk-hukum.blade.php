<div>
    @if (Auth::user()->can('create', App\Models\DokumentasiProdukHukum::class))
        <button class="btn btn-outline-default mb-3" data-target="#modalTambahDokumentasi" data-toggle="modal">
            <i class="bi bi-folder-plus mr-2"></i></i>Tambah Dokumentasi Produk Hukum
        </button>
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
                            <option hidden>-- Pilih Nomor Rancangan --</option>
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
                                        <span class="input-group-text">Nomor</span>
                                        <input type="text" id="nomor_produk_hukum"
                                            class="form-control text-center" wire:model.defer="nomor"
                                            placeholder="###" maxlength="3" pattern="\d{3}"
                                            oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0,3)">
                                        <span class="input-group-text">/Tahun {{ now()->year }}</span>
                                    </div>

                                    @error('nomor')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>



                                <div class="form-group">
                                    <label>Tanggal Publikasi</label>
                                    <input type="date" class="form-control" wire:model.defer="tanggal">
                                    @error('tanggal')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                {{-- Tambahan Nomor & Tanggal Berita Daerah --}}
                                <div class="form-group">
                                    <label>Nomor Berita Daerah</label>
                                    <input type="text" class="form-control" wire:model.defer="nomorBeritaDaerah"
                                        placeholder="Masukkan Nomor Berita Daerah">
                                    @error('nomorBeritaDaerah')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Tanggal Berita Daerah</label>
                                    <input type="date" class="form-control"
                                        wire:model.defer="tanggalBeritaDaerah">
                                    @error('tanggalBeritaDaerah')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
                                </div>


                                <div class="form-group">
                                    <label>Upload Berita Daerah (PDF)</label>
                                    <input type="file" class="form-control" wire:model="fileProdukHukum"
                                        wire:change="resetError" wire:loading.attr="disabled">
                                    <!-- Indikator Loading -->
                                    <div wire:loading wire:target="fileProdukHukum">
                                        <small class="text-warning">Mengupload file...</small>
                                    </div>
                                    @error('fileProdukHukum')
                                        <small class="text-danger">{{ $message }}</small>
                                    @enderror
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
</div>
