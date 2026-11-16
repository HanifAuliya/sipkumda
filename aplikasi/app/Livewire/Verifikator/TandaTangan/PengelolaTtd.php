<?php

namespace App\Livewire\Verifikator\TandaTangan;

use App\Models\TandaTangan;
use App\Models\User;
use App\Support\TandaTanganPeneliti;
use Closure;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Livewire\WithoutUrlPagination;
use Throwable;

/**
 * Kelola TTD (khusus Admin). Pemilik tanda tangan mengaturnya sendiri di Profil: Verifikator
 * (Kepala Bagian Hukum) untuk nota dinas dan matriks, Peneliti untuk matriks. Di sini Admin
 * membantu: mengganti gambar, menghubungkan data Kabag ke akunnya, mencatat Kabag lama atau
 * Plt/Plh, dan menonaktifkan tanda tangan yang tidak dipakai lagi.
 */
class PengelolaTtd extends Component
{
    use WithPagination, WithoutUrlPagination, WithFileUploads;

    public const PERAN = ['Admin'];

    #[Url(except: 'kabag')]
    public string $tab = 'kabag';

    public $search = '';
    public $perPage = 5;

    // Form Kepala Bagian Hukum (editId null = tambah)
    #[Locked]
    public $editId;
    public $nama_ttd, $nip_ttd, $jabatan, $status, $file_ttd, $user_id;
    public $existingFile;

    // Form tanda tangan peneliti
    #[Locked]
    public ?int $penelitiId = null;
    public $gambarPeneliti;

    /** Mengganti gambar tanda tangan orang lain meminta kata sandi Admin. */
    public string $sandiAdmin = '';

    protected $validationAttributes = [
        'nama_ttd' => 'nama lengkap',
        'nip_ttd' => 'NIP',
        'jabatan' => 'pangkat/golongan',
        'file_ttd' => 'gambar tanda tangan',
        'user_id' => 'akun Verifikator',
        'gambarPeneliti' => 'gambar tanda tangan',
        'sandiAdmin' => 'kata sandi Anda',
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasAnyRole(self::PERAN), 403);
        $this->tab = in_array($this->tab, ['kabag', 'peneliti'], true) ? $this->tab : 'kabag';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatedTab(): void
    {
        $this->tab = in_array($this->tab, ['kabag', 'peneliti'], true) ? $this->tab : 'kabag';
        $this->reset('search');
        $this->resetPage();
    }

    private function aturanGambar(bool $wajib): array
    {
        return [
            'bail', $wajib ? 'required' : 'nullable', 'image', 'mimes:png,jpg,jpeg', 'max:2048',
            function (string $kolom, $file, Closure $gagal) {
                if ($pesan = TandaTanganPeneliti::masalah($file->getRealPath())) {
                    $gagal($pesan);
                }
            },
        ];
    }

    public function updatedFileTtd()
    {
        $this->validateOnly('file_ttd', ['file_ttd' => $this->aturanGambar(false)]);
    }

    public function updatedGambarPeneliti()
    {
        $this->validateOnly('gambarPeneliti', ['gambarPeneliti' => $this->aturanGambar(false)]);
    }

    // ---- Kepala Bagian Hukum ----

    public function openModalTambah()
    {
        $this->resetForm();
        $this->status = 'Aktif';
        $this->dispatch('openModal', 'modalTtd');
    }

    public function openModalEdit($id)
    {
        $this->resetForm();

        $ttd = TandaTangan::findOrFail($id);

        $this->editId = $ttd->id;
        $this->nama_ttd = $ttd->nama_ttd;
        $this->nip_ttd = $ttd->nip_ttd;
        $this->jabatan = $ttd->jabatan;
        $this->status = $ttd->status;
        $this->user_id = $ttd->user_id;
        $this->existingFile = $ttd->file_ttd;

        $this->dispatch('openModal', 'modalTtd');
    }

    /** Akun yang boleh dihubungkan ke data ini: Verifikator yang belum punya tanda tangan sendiri. */
    private function akunTersedia()
    {
        return User::role('Verifikator')
            ->where(fn ($q) => $q->whereDoesntHave('tandaTanganKabag')
                ->when($this->editId, fn ($q) => $q->orWhereHas('tandaTanganKabag', fn ($t) => $t->whereKey($this->editId))))
            ->orderBy('nama_user')
            ->get(['id', 'nama_user', 'NIP']);
    }

    public function save()
    {
        $this->user_id = filled($this->user_id) ? (int) $this->user_id : null;

        $this->validate([
            'nama_ttd' => 'required|string|max:100',
            'nip_ttd' => 'required|string|max:50|unique:tanda_tangan,nip_ttd' . ($this->editId ? ',' . $this->editId : ''),
            'jabatan' => 'required|string|max:100',
            'file_ttd' => $this->aturanGambar(! $this->editId || ! $this->existingFile),
            'status' => 'required|in:Aktif,Tidak Aktif',
            'user_id' => ['nullable', Rule::in($this->akunTersedia()->pluck('id')->all())],
            'sandiAdmin' => $this->file_ttd ? ['required', 'current_password'] : ['nullable'],
        ]);

        $ttd = $this->editId ? TandaTangan::findOrFail($this->editId) : new TandaTangan(['dibuat_oleh' => auth()->id()]);
        $ttd->fill([
            'nama_ttd' => trim($this->nama_ttd),
            'nip_ttd' => trim($this->nip_ttd),
            'jabatan' => trim($this->jabatan),
            'status' => $this->status,
            'user_id' => $this->user_id,
        ]);

        // gantiGambar ikut menyimpan baris, jadi data baru tersimpan bersama gambarnya.
        if ($this->file_ttd) {
            try {
                $ttd->gantiGambar($this->file_ttd->getRealPath());
            } catch (Throwable $e) {
                report($e);
                $this->reset('file_ttd');

                throw ValidationException::withMessages(['file_ttd' => 'Gambar tidak bisa dibaca. Coba pilih gambar lain.']);
            }
        } else {
            $ttd->save();
        }

        $this->dispatch('closeModal', 'modalTtd');
        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => $this->editId ? 'Tanda tangan diperbarui.' : 'Tanda tangan ditambahkan.',
        ]);
        $this->resetForm();
    }

    public function removeFile()
    {
        $this->reset('file_ttd');
        $this->resetValidation('file_ttd');
    }

    public function delete($id)
    {
        $ttd = TandaTangan::withCount('naskahNotaDinas')->findOrFail($id);
        $dipakai = $ttd->naskah_nota_dinas_count;

        if ($dipakai > 0) {
            $this->dispatch('swal:modal', [
                'type' => 'warning',
                'title' => 'Tidak dapat dihapus',
                'message' => "Tanda tangan ini dipakai di {$dipakai} nota dinas. Ubah statusnya menjadi Tidak Aktif sebagai gantinya.",
            ]);
            return;
        }

        $ttd->delete();
        $this->resetPage();

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Tanda tangan dihapus.',
        ]);
    }

    public function resetForm()
    {
        $this->reset(['editId', 'nama_ttd', 'nip_ttd', 'jabatan', 'status', 'file_ttd', 'existingFile', 'user_id', 'sandiAdmin']);
        $this->resetValidation();
    }

    // ---- Peneliti ----

    private function peneliti(int $id): User
    {
        return User::role('Peneliti')->findOrFail($id);
    }

    public function bukaPeneliti(int $id): void
    {
        $this->resetPeneliti();
        $this->penelitiId = $this->peneliti($id)->id;
        $this->dispatch('openModal', 'modalTtdPeneliti');
    }

    public function simpanPeneliti(): void
    {
        $peneliti = $this->peneliti((int) $this->penelitiId);
        $this->validate([
            'gambarPeneliti' => $this->aturanGambar(true),
            'sandiAdmin' => ['required', 'current_password'],
        ]);

        try {
            TandaTanganPeneliti::simpan($peneliti, $this->gambarPeneliti->getRealPath());
        } catch (Throwable $e) {
            report($e);
            $this->reset('gambarPeneliti');

            throw ValidationException::withMessages(['gambarPeneliti' => 'Gambar tidak bisa dibaca. Coba pilih gambar lain.']);
        }

        $this->dispatch('closeModal', 'modalTtdPeneliti');
        $this->dispatch('swal:toast', ['type' => 'success', 'message' => "Tanda tangan {$peneliti->nama_user} tersimpan."]);
        $this->resetPeneliti();
    }

    public function hapusPeneliti(int $id): void
    {
        $peneliti = $this->peneliti($id);
        TandaTanganPeneliti::hapus($peneliti);

        $this->dispatch('swal:toast', ['type' => 'success', 'message' => "Tanda tangan {$peneliti->nama_user} dihapus."]);
    }

    public function resetPeneliti(): void
    {
        $this->reset(['penelitiId', 'gambarPeneliti', 'sandiAdmin']);
        $this->resetValidation();
    }

    public function render()
    {
        $cari = fn ($q, array $kolom) => $q->where(function ($q) use ($kolom) {
            foreach ($kolom as $k) {
                $q->orWhere($k, 'like', "%{$this->search}%");
            }
        });

        return view('livewire.verifikator.tanda-tangan.pengelola-ttd', [
            'ttdList' => $this->tab === 'kabag'
                ? $cari(TandaTangan::withCount('naskahNotaDinas')->with('pemilik:id,nama_user'), ['nama_ttd', 'nip_ttd', 'jabatan'])
                    ->orderByRaw("status = 'Aktif' desc")->latest()->paginate($this->perPage)
                : null,
            'penelitiList' => $this->tab === 'peneliti'
                ? $cari(User::role('Peneliti'), ['nama_user', 'NIP'])->orderBy('nama_user')->paginate($this->perPage)
                : null,
            'jumlah' => [
                'kabag' => TandaTangan::count(),
                'peneliti' => User::role('Peneliti')->count(),
            ],
            'akunTersedia' => $this->akunTersedia(),
            'penelitiDipilih' => $this->penelitiId ? User::find($this->penelitiId) : null,
        ])->layout('layouts.app');
    }
}
