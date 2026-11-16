<?php

namespace App\Livewire\V2;

use App\Models\PerangkatDaerah;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Daftar Perangkat Daerah: menambah instansi baru, membetulkan namanya, dan menghapus yang belum dipakai.
 * Nama di sini muncul di pilihan pengguna, kartu naskah, tujuan nota dinas, dan dokumentasi.
 */
#[Layout('layouts.app')]
#[Title('Perangkat Daerah')]
class PengaturanPerangkatDaerah extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public int $perPage = 10;

    /** null = menambah; selain itu id yang sedang diubah namanya. */
    public ?int $idDiubah = null;

    public string $nama = '';

    public function updated(string $properti): void
    {
        if (in_array($properti, ['search', 'perPage'], true)) {
            $this->resetPage();
        }
    }

    public function tambah(): void
    {
        $this->reset('idDiubah', 'nama');
        $this->resetValidation();
        $this->dispatch('openModal', 'modalPerangkatDaerah');
    }

    public function ubah(int $id): void
    {
        $perangkat = PerangkatDaerah::findOrFail($id);

        $this->idDiubah = $perangkat->id;
        $this->nama = $perangkat->nama_perangkat_daerah;
        $this->resetValidation();
        $this->dispatch('openModal', 'modalPerangkatDaerah');
    }

    public function simpan(): void
    {
        // Spasi berlebih dirapikan dulu, supaya "Dinas  Kesehatan" tidak lolos sebagai nama baru.
        $this->nama = trim(preg_replace('/\s+/u', ' ', $this->nama));

        $this->validate([
            'nama' => ['required', 'string', 'min:3', 'max:255', Rule::unique('perangkat_daerah', 'nama_perangkat_daerah')->ignore($this->idDiubah)],
        ], [
            'nama.unique' => 'Perangkat Daerah dengan nama ini sudah ada.',
        ], [
            'nama' => 'nama Perangkat Daerah',
        ]);

        if ($this->idDiubah) {
            PerangkatDaerah::findOrFail($this->idDiubah)->update(['nama_perangkat_daerah' => $this->nama]);
            $pesan = 'Nama Perangkat Daerah diperbarui.';
        } else {
            PerangkatDaerah::create(['nama_perangkat_daerah' => $this->nama]);
            $pesan = "{$this->nama} ditambahkan.";
        }

        $this->reset('idDiubah', 'nama');
        $this->dispatch('closeModal', 'modalPerangkatDaerah');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => $pesan]);
    }

    /** Hanya yang belum dipakai yang bisa dihapus; yang sudah punya pengguna atau dokumen cukup diubah namanya. */
    public function hapus(int $id): void
    {
        $perangkat = PerangkatDaerah::findOrFail($id);

        if ($alasan = $this->pemakaian([$id])[$id] ?? null) {
            $this->dispatch('swal:toast', ['type' => 'error', 'message' => "{$perangkat->nama_perangkat_daerah} tidak bisa dihapus karena sudah dipakai ({$alasan})."]);

            return;
        }

        $perangkat->delete();
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => "{$perangkat->nama_perangkat_daerah} dihapus."]);
    }

    /** Keterangan pemakaian per id, mis. "3 pengguna, 2 dokumentasi". Id yang belum dipakai tidak ada di hasil. */
    private function pemakaian(array $ids): array
    {
        $hasil = [];

        foreach ([
            // Rancangan dan naskah terhubung lewat akun penggunanya, jadi cukup dua tabel ini yang diperiksa.
            'pengguna' => 'users',
            'dokumentasi' => 'dokumentasi_produk_hukum',
        ] as $label => $tabel) {
            $jumlah = DB::table($tabel)->whereIn('perangkat_daerah_id', $ids)
                ->selectRaw('perangkat_daerah_id, count(*) as jumlah')->groupBy('perangkat_daerah_id')
                ->pluck('jumlah', 'perangkat_daerah_id');

            foreach ($jumlah as $id => $n) {
                $hasil[$id][] = "{$n} {$label}";
            }
        }

        return array_map(fn (array $bagian) => implode(', ', $bagian), $hasil);
    }

    public function render()
    {
        $halaman = PerangkatDaerah::query()
            ->when($this->search !== '', fn ($q) => $q->where('nama_perangkat_daerah', 'like', "%{$this->search}%"))
            ->orderBy('nama_perangkat_daerah')
            ->paginate(max(5, min(50, $this->perPage)));

        return view('v2.pengaturan-perangkat-daerah', [
            'halaman' => $halaman,
            'pemakaian' => $this->pemakaian($halaman->pluck('id')->all()),
            'total' => PerangkatDaerah::count(),
        ]);
    }
}
