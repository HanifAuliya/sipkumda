<div>
    {{-- Modal Ajukan Rancangan --}}
    <div wire:ignore.self class="modal fade" id="ajukanRancanganModal" tabindex="-1" role="dialog"
        aria-labelledby="ajukanRancanganModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form wire:submit.prevent="submit">
                    <div class="modal-header">
                        <h5 class="modal-title" id="ajukanRancanganModalLabel">Ajukan Rancangan Baru</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
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
                            <label for="tentang" class="form-label font-weight-bold">Tentang</label>
                            <input type="text" class="form-control" wire:model="tentang"
                                placeholder="Masukkan Judul/Tentang Rancangan" required />
                            @error('tentang')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        {{-- File Inputs --}}
                        <div class="mb-3">
                            <label for="rancangan" class="form-label font-weight-bold">File Rancangan</label>
                            <input type="file" class="form-control" wire:model="rancangan" />
                            @error('rancangan')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="matrik" class="form-label font-weight-bold">Matrik Rancangan</label>
                            <input type="file" class="form-control" wire:model="matrik" />
                            @error('matrik')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nota_dinas_pd" class="form-label font-weight-bold">Nota Dinas</label>
                            <input type="file" class="form-control" wire:model="nota_dinas_pd" />
                            @error('nota_dinas_pd')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="bahanPendukung" class="form-label font-weight-bold">Bahan Pendukung
                                (Opsional)</label>
                            <input type="file" class="form-control" wire:model="bahanPendukung" />
                            @error('bahanPendukung')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="reset" class="btn btn-outline-info" wire:click="resetInput">
                            Reset Inputan
                        </button>
                        <button type="button" class="btn btn-outline-warning" data-dismiss="modal"
                            wire:click="resetForm">
                            Batal
                        </button>

                        <button class="btn btn-outline-default" wire:click="submit" wire:loading.attr="disabled">
                            <span wire:loading.remove>Ajukan</span>
                            <span wire:loading>Tunggu Sebentar Lagi Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        // Berhasil
        window.addEventListener('swal:modal', function(event) {
            // Ambil elemen pertama dari array
            const data = event.detail[0];

            $('#ajukanRancanganModal').modal('hide'); // Tutup modal
            // $('.modal-backdrop').remove(); // Hapus backdrop modal

            // Tampilkan SweetAlert
            Swal.fire({
                icon: data.type,
                title: data.title,
                text: data.message,
                showConfirmButton: true,
            });
        });

        // Gagal
        window.addEventListener('swal:error', function(event) {
            // Ambil elemen pertama dari array
            const data = event.detail[0];

            // Tampilkan SweetAlert
            Swal.fire({
                icon: data.type,
                title: data.title,
                text: data.message,
                showConfirmButton: true,
            });
        });
    </script>
</div>
