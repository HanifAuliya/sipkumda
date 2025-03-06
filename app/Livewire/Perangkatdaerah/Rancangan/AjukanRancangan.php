<?php

namespace App\Livewire\PerangkatDaerah\Rancangan;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RancanganBaruNotification;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Revisi;


class AjukanRancangan extends Component
{
    use WithFileUploads;

    public $jenisRancangan;
    public $tentang;
    public $rancangan;
    public $matrik;
    public $nota_dinas_pd;
    public $bahanPendukung;
    public $tanggalNota;
    public $nomorNota;


    protected $rules = [
        'jenisRancangan' => 'required',
        'tentang' => 'required|string|max:255',
        'rancangan' => 'required|mimes:doc,docx|max:5120', // Hanya format Word
        'matrik' => 'required|mimes:doc,docx|max:5120', // Hanya format Word
        'nota_dinas_pd' => 'required|mimes:pdf|max:5120', // PDF
        'bahanPendukung' => 'nullable|mimes:pdf|max:5120', // PDF opsional
        'tanggalNota' => 'required|date',
        'nomorNota' => 'required|string|unique:rancangan_produk_hukum,nomor_nota|max:50',
    ];

    public function submit()
    {
        $this->validate();

        // Folder utama
        $mainFolder = 'rancangan';

        // Format Nama File
        $filePrefix = str_replace(' ', '_', strtolower($this->jenisRancangan)) . '_' . str_replace('/', '-', $this->nomorNota);

        // Simpan file dengan nama yang mengandung identitas
        $rancanganPath = $this->rancangan->storeAs(
            "$mainFolder/rancangan",
            "{$filePrefix}_rancangan." . $this->rancangan->extension(),
            'local'
        );

        $matrikPath = $this->matrik->storeAs(
            "$mainFolder/matrik",
            "{$filePrefix}_matrik." . $this->matrik->extension(),
            'local'
        );

        $notaDinasPath = $this->nota_dinas_pd->storeAs(
            "$mainFolder/nota_dinas",
            "{$filePrefix}_nota_dinas.pdf",
            'local'
        );

        $bahanPendukungPath = $this->bahanPendukung
            ? $this->bahanPendukung->storeAs(
                "$mainFolder/bahan_pendukung",
                "{$filePrefix}_bahan_pendukung.pdf",
                'local'
            )
            : null;

        // Simpan data ke database
        $rancangan = RancanganProdukHukum::create([
            'id_user' => auth()->id(),
            'jenis_rancangan' => $this->jenisRancangan,
            'tentang' => $this->tentang,
            'rancangan' => $rancanganPath,
            'matrik' => $matrikPath,
            'nota_dinas_pd' => $notaDinasPath,
            'bahan_pendukung' => $bahanPendukungPath,
            'status_berkas' => 'Menunggu Persetujuan',
            'status_rancangan' => 'Dalam Proses',
            'tanggal_pengajuan' => now(),
            'tanggal_nota' => $this->tanggalNota,
            'nomor_nota' => $this->nomorNota,
        ]);

        // Tambahkan status revisi ke tabel revisi
        Revisi::create([
            'id_rancangan' => $rancangan->id_rancangan,
            'status_revisi' => 'Belum Tahap Revisi',
            'status_validasi' => 'Belum Tahap Validasi',
            'catatan_revisi' => null,
        ]);

        // Kirim notifikasi ke Admin dan Verifikator
        Notification::send(User::role(['Admin'])->get(), new RancanganBaruNotification([
            'title' => 'Rancangan Menunggu Persetujuan Berkas',
            'message' => auth()->user()->nama_user . ' telah menambahkan rancangan baru dengan nomor ' . $rancangan->no_rancangan . '. Silahkan Periksa dan Lakukan Verifikasi Berkas, anda bisa ke halaman Persetujuan Berkas di bagian Rancangan Produk Hukum',
            'slug' => $rancangan->slug,
            'type' => 'admin_persetujuan',
        ]));

        Notification::send(User::role(['Verifikator'])->get(), new RancanganBaruNotification([
            'title' => 'Rancangan Baru Telah Ditambahkan',
            'message' => auth()->user()->nama_user . ' telah menambahkan rancangan baru dengan nomor ' . $rancangan->nomor_nota . '.',
            'slug' => $rancangan->slug,
            'type' => 'verifikator_detail',
        ]));

        // Emit event sukses
        $this->dispatch('refreshNotifications');
        $this->dispatch('rancanganDiperbarui');

        $this->reset();

        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Rancangan berhasil ditambahkan!',
        ]);

        // 🔥 Tutup modal setelah reset
        $this->dispatch('closeModal', 'ajukanRancanganModal');
    }
    public function removeFile($fileField)
    {
        $this->reset($fileField); // Menghapus file yang sudah diunggah
    }

    public function resetForm()
    {
        // Hapus file sementara Livewire
        if ($this->rancangan) {
            $this->rancangan->delete();
        }
        if ($this->matrik) {
            $this->matrik->delete();
        }
        if ($this->nota_dinas_pd) {
            $this->nota_dinas_pd->delete();
        }
        if ($this->bahanPendukung) {
            $this->bahanPendukung->delete();
        }

        // Atur ulang semua properti ke nilai default
        $this->jenisRancangan = null;
        $this->tentang = null;
        $this->tanggalNota = null;
        $this->nomorNota = null;
        $this->rancangan = null;
        $this->matrik = null;
        $this->nota_dinas_pd = null;
        $this->bahanPendukung = null;

        // Reset error validasi
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function resetError($field)
    {
        // Reset error validasi untuk field tertentu
        $this->resetErrorBag($field);
    }

    public function render()
    {
        return view('livewire.perangkatdaerah.rancangan.ajukan-rancangan');
    }
}
