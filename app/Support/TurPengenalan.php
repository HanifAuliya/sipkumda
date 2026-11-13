<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Tur pengenalan di Dashboard: menyorot menu dan bagian layar satu per satu, sesuai peran.
 *
 * Tiap langkah: target (nilai atribut data-tur di layar; null = kartu di tengah), judul, isi, dan
 * isi_kosong bila ada: kalimat pengganti saat bagian yang diperiksa (cek, bawaannya target) bertanda
 * data-kosong, misalnya aplikasi yang baru dipakai dan belum berisi naskah.
 * Menu sidebar bertarget "menu-<slug label>", mis. "menu-rancanganku". Langkah untuk menu yang
 * tidak dimiliki pengguna dibuang; langkah yang targetnya tidak terlihat (mis. sidebar di HP)
 * ditampilkan di tengah layar.
 */
class TurPengenalan
{
    /** Tur muncul sekali; bisa diulang lewat ?tur=1 (tombol di Panduan dan menu akun). */
    public static function tampil(?User $user): bool
    {
        if (! $user || $user->wajib_ganti_sandi || ! request()->routeIs('dashboard')) {
            return false;
        }

        return $user->tur_selesai_at === null || request()->boolean('tur');
    }

    public static function langkah(User $user): array
    {
        $peran = $user->getRoleNames()->first() ?? 'Pengguna';
        $pd = $user->hasRole('Perangkat Daerah');
        $menu = collect(Navigation::for($user))->flatMap(fn ($s) => $s['items'])
            ->map(fn ($item) => 'menu-' . Str::slug($item['label']))->all();

        $langkah = [
            [null, 'Selamat datang di SIFKUMDA', "Tur singkat ini mengenalkan menu yang Anda pakai sebagai {$peran}. Tekan Selanjutnya, atau Lewati bila sudah paham."],
            ['statistik', 'Ringkasan naskah', 'Jumlah naskah di tiap tahap. Kartu yang bisa ditekan membuka daftarnya.',
                'Jumlah naskah di tiap tahap. Angkanya bertambah setelah naskah pertama diajukan.'],
            ['giliran', 'Giliran Anda', 'Naskah yang sedang menunggu tindakan Anda ada di sini. Tekan Kerjakan untuk membukanya.',
                $pd
                    ? 'Belum ada naskah. Mulailah dari menu Rancanganku › Ajukan Rancangan Baru; naskah yang perlu Anda tindak lanjuti nanti muncul di sini.'
                    : 'Belum ada naskah yang menunggu Anda. Saat ada tugas baru, naskahnya muncul di sini dan Anda mendapat notifikasi.'],
            ...self::menuPeran($user),
            ['lonceng', 'Notifikasi', 'Tugas baru dan kabar naskah muncul di sini. Naskah yang lama tertahan juga diingatkan lewat notifikasi.'],
            ['menu-panduan', 'Panduan', 'Panduan lengkap sesuai peran Anda, lengkap dengan gambar. Tur ini juga bisa diulang dari sana.'],
            ['akun', 'Akun Anda', 'Profil, foto, tanda tangan, ganti kata sandi, dan keluar.'],
            [null, 'Selesai, siap dipakai', 'Mulailah dari Giliran Anda. Bila bingung, buka menu Panduan.',
                $pd
                    ? 'Mulailah dengan mengajukan rancangan pertama lewat Rancanganku › Ajukan Rancangan Baru. Bila bingung, buka menu Panduan.'
                    : 'Tugas pertama Anda akan muncul di Giliran Anda dan di notifikasi. Bila bingung, buka menu Panduan.',
                'giliran'],
        ];

        return collect($langkah)
            ->reject(fn ($l) => $l[0] !== null && str_starts_with($l[0], 'menu-') && ! in_array($l[0], $menu, true))
            ->map(fn ($l) => array_filter([
                'target' => $l[0],
                'judul' => $l[1],
                'isi' => $l[2],
                'isi_kosong' => $l[3] ?? null,
                'cek' => $l[4] ?? null,
            ], fn ($v) => $v !== null) + ['target' => null])
            ->values()
            ->all();
    }

    /** Menu utama tiap peran, sesuai urutan pekerjaannya. */
    private static function menuPeran(User $user): array
    {
        $daftar = [
            'Perangkat Daerah' => [
                ['menu-rancanganku', 'Rancanganku', 'Ajukan rancangan baru dengan tombol Ajukan Rancangan Baru, lalu pantau tahapnya di sini. Template dan contoh berkas ada di tombol Bahan Penting.'],
                ['menu-fasilitasiku', 'Fasilitasiku', 'Setelah rancangan disetujui, ajukan fasilitasi dan tindak lanjuti catatan peneliti di sini.'],
                ['menu-daftar-nota-dinas', 'Daftar Nota Dinas', 'Nota dinas dari Bagian Hukum untuk naskah Anda bisa diunduh di sini.'],
                ['menu-dokumentasi', 'Dokumentasi', 'Produk hukum yang sudah ditetapkan dan diarsipkan.'],
            ],
            'Peneliti' => [
                ['menu-penelitian-rancangan', 'Penelitian Rancangan', 'Rancangan yang ditugaskan kepada Anda. Periksa tiap baris: tetapkan, perbaiki, atau minta Perangkat Daerah menyesuaikan.'],
                ['menu-persetujuan-fasilitasi', 'Persetujuan Fasilitasi', 'Berkas fasilitasi yang perlu Anda periksa kelengkapannya.'],
                ['menu-pengecekan-fasilitasi', 'Pengecekan Fasilitasi', 'Perbaikan fasilitasi dari Perangkat Daerah yang perlu Anda cek.'],
                ['menu-daftar-rancangan', 'Daftar Rancangan', 'Semua rancangan, untuk dicari atau dipantau.'],
            ],
            'Verifikator' => [
                ['menu-pilih-peneliti', 'Pilih Peneliti', 'Rancangan yang berkasnya lengkap menunggu Anda menugaskan peneliti.'],
                ['menu-validasi-rancangan', 'Validasi Rancangan', 'Hasil penelitian yang menunggu persetujuan Anda.'],
                ['menu-validasi-fasilitasi', 'Validasi Fasilitasi', 'Naskah fasilitasi yang menunggu persetujuan akhir.'],
                ['menu-daftar-nota-dinas', 'Menerbitkan Nota Dinas', 'Naskah yang fasilitasinya selesai menunggu nota dinas di tab Menunggu Terbit. Tekan Terbitkan Nota Dinas, isi nomor urut, periksa Kepada, dan pilih tanda tangan. Admin juga bisa menerbitkannya.'],
                ['menu-dokumentasi', 'Dokumentasi', 'Arsipkan naskah yang tanda tangannya lengkap lewat Arsipkan dari Sistem, atau tambahkan produk hukum lama.'],
                ['menu-laporan-pemantauan', 'Laporan & Pemantauan', 'Rekap naskah, sebaran per Perangkat Daerah (siapa mengajukan apa dan sampai mana), aktivitas semua naskah, dan naskah yang tertahan. Bisa diunduh sebagai Excel.'],
                ['menu-kelola-ttd', 'Kelola TTD', 'Pejabat penanda tangan nota dinas.'],
            ],
            'Admin' => [
                ['menu-persetujuan-berkas', 'Persetujuan Berkas', 'Pengajuan baru dari Perangkat Daerah. Periksa berkasnya, lalu setujui atau kembalikan.'],
                ['menu-daftar-nota-dinas', 'Menerbitkan Nota Dinas', 'Naskah yang fasilitasinya selesai menunggu nota dinas di tab Menunggu Terbit. Tekan Terbitkan Nota Dinas, isi nomor urut, periksa Kepada, dan pilih tanda tangan. Verifikator juga bisa menerbitkannya.'],
                ['menu-penandatanganan', 'Penandatanganan', 'Catat paraf dan tanda tangan pejabat satu per satu.'],
                ['menu-dokumentasi', 'Dokumentasi', 'Arsipkan naskah yang tanda tangannya lengkap lewat Arsipkan dari Sistem, atau tambahkan produk hukum lama.'],
                ['menu-laporan-pemantauan', 'Laporan & Pemantauan', 'Rekap naskah, sebaran per Perangkat Daerah (siapa mengajukan apa dan sampai mana), aktivitas semua naskah, dan naskah yang tertahan. Bisa diunduh sebagai Excel.'],
                ['menu-daftar-pengguna', 'Daftar Pengguna', 'Tambah akun, atur peran, dan buat kata sandi sementara.'],
                ['menu-pengaturan-naskah', 'Pengaturan', 'Nama pejabat, urutan tanda tangan, format dokumen, dan daftar perangkat daerah.'],
            ],
            'Super Admin' => [
                ['menu-daftar-pengguna', 'Daftar Pengguna', 'Tambah akun, atur peran, dan buat kata sandi sementara.'],
                ['menu-laporan-pemantauan', 'Laporan & Pemantauan', 'Rekap naskah, sebaran per Perangkat Daerah, aktivitas semua naskah, dan naskah yang tertahan.'],
                ['menu-pengaturan-naskah', 'Pengaturan', 'Nama pejabat, urutan tanda tangan, format dokumen, dan daftar perangkat daerah.'],
            ],
            'Tamu' => [
                ['menu-penandatanganan', 'Penandatanganan', 'Naskah yang sedang diparaf dan ditandatangani. Catat setiap tahapan yang sudah selesai beserta tanggalnya.'],
            ],
        ];

        $peran = collect(array_keys($daftar))->first(fn ($p) => $user->hasRole($p));

        return $peran ? $daftar[$peran] : [];
    }
}
