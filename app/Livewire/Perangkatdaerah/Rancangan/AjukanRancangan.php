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
        'rancangan' => 'required|mimes:pdf|max:5120',
        'matrik' => 'required|mimes:pdf,doc,docx,xls,xlsx|max:5120',
        'nota_dinas_pd' => 'required|mimes:pdf|max:5120',
        'bahanPendukung' => 'nullable|mimes:pdf|max:5120',
        'tanggalNota' => 'required|date',
        'nomorNota' => 'required|string|unique:rancangan_produk_hukum,nomor_nota|max:50',
    ];


    public function submit()
    {
        $this->validate();

        // Folder utama
        $mainFolder = 'rancangan';

        // Simpan file ke dalam subfolder masing-masing di storage private
        $rancanganPath = $this->rancangan->store("$mainFolder/rancangan", 'local');
        $matrikPath = $this->matrik->store("$mainFolder/matrik", 'local');
        $notaDinasPath = $this->nota_dinas_pd->store("$mainFolder/nota_dinas", 'local');
        $bahanPendukungPath = $this->bahanPendukung
            ? $this->bahanPendukung->store("$mainFolder/bahan_pendukung", 'local')
            : null;

        // Simpan data ke database
        $rancangan = RancanganProdukHukum::create([
            'id_user' => auth()->id(),
            'jenis_rancangan' => $this->jenisRancangan,
            'tentang' => $this->tentang,
            'rancangan' => $this->rancangan->store('rancangan/rancangan', 'local'),
            'matrik' => $this->matrik->store('rancangan/matrik', 'local'),
            'nota_dinas_pd' => $this->nota_dinas_pd->store('rancangan/nota_dinas', 'local'),
            'bahan_pendukung' => $this->bahanPendukung
                ? $this->bahanPendukung->store('rancangan/bahan_pendukung', 'local')
                : null,
            'status_berkas' => 'Menunggu Persetujuan',
            'status_rancangan' => 'Dalam Proses',
            'tanggal_pengajuan' => now(),
            'tanggal_nota' => $this->tanggalNota, // Menyimpan tanggal nota
            'nomor_nota' => $this->nomorNota, // Menyimpan nomor nota
        ]);

        // Tambahkan status revisi ke tabel revisi
        Revisi::create([
            'id_rancangan' => $rancangan->id_rancangan, // Hubungkan ke rancangan
            'status_revisi' => 'Belum Tahap Revisi', // Status awal
            'status_validasi' => 'Belum Tahap Validasi', // Status awal
            'catatan_revisi' => null, // Catatan awal
        ]);


        // Kirim notifikasi ke Admin
        Notification::send(
            User::role(['Admin'])->get(),
            new RancanganBaruNotification([
                'title' => 'Rancangan Menunggu Persetujuan Berkas',
                'message' => auth()->user()->nama_user . ' telah menambahkan rancangan baru dengan nomor ' . $rancangan->no_rancangan . '. Silahkan Periksa dan Lakukan Verifikasi Berkas',
                'slug' => $rancangan->slug, // Slug untuk memuat modal
                'type' => 'admin_persetujuan', // Tipe notifikasi untuk membedakan modal
            ])
        );

        // Kirim notifikasi ke Verifikator
        Notification::send(
            User::role(['Verifikator'])->get(),
            new RancanganBaruNotification([
                'title' => 'Rancangan Baru Telah Ditambahkan',
                'message' => auth()->user()->nama_user . ' telah menambahkan rancangan baru dengan nomor ' . $rancangan->no_rancangan . '.',
                'slug' => $rancangan->slug, // Data slug untuk memuat modal
                'type' => 'verifikator_detail', // Tipe notifikasi
            ])
        );

        // Emit notifikasi sukses ke pengguna
        $this->dispatch('refreshNotifications');

        // Dispatch success event
        $this->dispatch('rancanganDiperbarui');

        $this->reset();

        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Rancangan berhasil ditambahkan!',
        ]);
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
