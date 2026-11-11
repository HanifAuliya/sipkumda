{{-- Pilihan jenis produk hukum untuk daftar naskah; komponen pemakainya punya properti "jenis". --}}
<x-form.select wire:model.live="jenis" icon="tags" class="sm:w-52" aria-label="Jenis produk hukum">
    <option value="">Semua jenis</option>
    <option value="Peraturan Bupati">Peraturan Bupati (PB)</option>
    <option value="Keputusan Bupati">Keputusan Bupati (SK)</option>
</x-form.select>
