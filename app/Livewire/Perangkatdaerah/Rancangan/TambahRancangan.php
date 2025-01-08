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

class TambahRancangan extends Component
{
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

        // Simpan file ke storage
        $rancanganPath = $this->rancangan->store('rancangan');
        $matrikPath = $this->matrik->store('matrik');
        $notaDinasPath = $this->nota_dinas_pd->store('nota_dinas_pd');
        $bahanPendukungPath = $this->bahanPendukung
            ? $this->bahanPendukung->store('bahan_pendukung')
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
            'tanggal_pengajuan' => now(), // Tambahkan tanggal pengajuan
        ]);

        // Tambahkan status revisi ke tabel revisi
        Revisi::create([
            'id_rancangan' => $rancangan->id_rancangan, // Hubungkan ke rancangan
            'status_revisi' => 'Belum Tahap Direvisi', // Status awal
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

        $this->reset(); // Reset error validasi

        $this->dispatch('swal:modal', [
            'type' => 'success',
            'title' => 'Berhasil!',
            'message' => 'Rancangan berhasil ditambahkan!',
        ]);
    }
    public function resetForm()
    {
        $this->resetErrorBag(); // Reset error validasi
        $this->resetValidation(); // Reset tampilan error validasi
    }

    public function render()
    {
        return view('livewire.perangkatdaerah.rancangan.tambah-rancangan');
    }
}
