<?php

namespace App\Livewire\Perangkatdaerah\Rancangan;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\RancanganProdukHukum;
use Illuminate\Support\Facades\Notification;
use App\Notifications\RancanganBaruNotification;
use Spatie\Permission\Models\Role;
use App\Models\User;
use App\Models\Revisi;

class Rancanganku extends Component
{
    public $tab = 'sedang_diajukan'; // Default tab

    protected $queryString = ['tab']; // Masukkan 'tab' ke dalam query string

    use WithFileUploads;

    public $jenisRancangan;
    public $tentang;
    public $rancangan;
    public $matrik;
    public $nota_dinas_pd;
    public $bahanPendukung;

    protected $rules = [
        'jenisRancangan' => 'required|string',
        'tentang' => 'required|string|max:255',
        'rancangan' => 'required|mimes:pdf|max:2048', // Maks 2MB
        'matrik' => 'required|mimes:pdf|max:2048',
        'nota_dinas_pd' => 'required|mimes:pdf|max:2048',
        'bahanPendukung' => 'nullable|mimes:pdf|max:2048', // Maks 2MB
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
        $this->dispatch('rancanganDitambahkan');

        $this->reset();

        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Rancangan berhasil ditambahkan!',
        ]);
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
        $this->rancangan = null;
        $this->matrik = null;
        $this->nota_dinas_pd = null;
        $this->bahanPendukung = null;

        // Reset error validasi
        $this->resetErrorBag();
        $this->resetValidation();
    }


    public function render()
    {
        return view('livewire.perangkatdaerah.rancangan.rancanganku')->layout('layouts.app');
    }
}
