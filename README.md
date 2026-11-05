# SIPKUMDA — Sistem Informasi Produk Hukum Daerah

Aplikasi web untuk mengelola alur pengajuan, penelitian, fasilitasi, hingga dokumentasi produk hukum daerah (Peraturan Bupati dan Surat Keputusan) di lingkungan Pemerintah Kabupaten Hulu Sungai Tengah.

Sistem ini menggantikan proses manual berbasis berkas fisik menjadi alur digital yang terlacak: setiap rancangan dapat dipantau statusnya, direvisi, disetujui, lalu terdokumentasi secara terpusat.

## Fitur Utama

- **Pengajuan rancangan** produk hukum oleh Perangkat Daerah, lengkap dengan unggah berkas (rancangan, matriks, nota dinas, bahan pendukung).
- **Cek kelengkapan berkas otomatis** — dokumen Word/PDF yang diunggah diekstrak teksnya lalu diperiksa kelengkapannya sebelum diajukan.
- **Alur persetujuan bertahap**: persetujuan Admin → Verifikator memilih Peneliti → revisi oleh Peneliti → validasi.
- **Fasilitasi produk hukum**, termasuk pengelolaan tanda tangan dan pembuatan nota dinas.
- **Dokumentasi & master data** produk hukum dengan ekspor ke **Excel** dan **PDF**.
- **Notifikasi real-time** untuk setiap perubahan status.
- **Manajemen pengguna berbasis peran** (role & permission).
- Halaman publik berisi statistik jumlah rancangan dan produk hukum yang terdokumentasi.

## Peran Pengguna

| Peran | Akses utama |
|---|---|
| Super Admin | Seluruh sistem, manajemen pengguna |
| Admin | Persetujuan rancangan, manajemen fasilitasi, bahan penting |
| Verifikator | Pemilihan peneliti, validasi rancangan & fasilitasi, kelola TTD |
| Peneliti | Revisi rancangan, persetujuan fasilitasi |
| Perangkat Daerah | Mengajukan rancangan & fasilitasi, memantau status |
| Tamu | Akses terbatas (lihat data) |

## Teknologi

- **Backend:** Laravel 11, PHP 8.2
- **Frontend:** Livewire 3, Alpine.js, Tailwind CSS, Vite
- **Tabel data:** Rappasoft Laravel Livewire Tables
- **Otorisasi:** Spatie Laravel Permission
- **Dokumen:** DomPDF, Laravel Excel, PHPWord, PDF Parser
- **Real-time:** Pusher + Laravel Echo
- **Testing:** Pest

## Instalasi

Prasyarat: PHP 8.2+, Composer, Node.js, dan MySQL atau SQLite.

```bash
git clone https://github.com/HanifAuliya/sipkumda.git
cd sipkumda

composer install
npm install

cp .env.example .env
php artisan key:generate
```

Atur koneksi database (dan kredensial Pusher jika ingin notifikasi real-time) di `.env`, lalu:

```bash
php artisan migrate --seed
php artisan storage:link
```

Jalankan aplikasi:

```bash
npm run dev
php artisan serve
```

Buka `http://localhost:8000`. Seeder membuat akun contoh untuk setiap peran (lihat `database/seeders/UserSeeder.php`).
Untuk mengisi data contoh di semua tahap alur: `php artisan db:seed --class=DemoSeeder`.

Beranda publik SIFKUMDA ada di `/`; pencarian produk hukum untuk umum (JDIH) ada di `/produk-hukum`.

## Server Production

Sebelum dipakai banyak orang, ikuti [docs/DEPLOY.md](docs/DEPLOY.md): `config:cache` wajib
(terutama di Windows), pengaturan MySQL & web server, serta hasil load test.

## Aplikasi v2

Alur baru di `/v2`, berjalan berdampingan dengan alur lama memakai login dan data pengguna yang sama.
Setelah login, pengguna memilih aplikasi lama atau v2 di `/pilih-aplikasi`, dan bisa pindah lewat menu pengguna.
Matriks menjadi sumber utama; naskah Word/PDF dan matriks PDF dicetak dari baris matriks, dan hasil
akhirnya masuk ke tabel arsip yang sama dengan JDIH.

- Menu: `app/Support/Navigation.php` (`sectionsV2`), memakai kerangka tampilan yang sama dengan aplikasi lama
- Layar kerja naskah: `resources/views/layouts/kerja.blade.php`; pembagian per bagian di `app/Support/BlokNaskah.php`
- Aturan alur: `app/Services/AlurNaskah.php` dan `app/Enums/TahapNaskah.php`
- Pembacaan Word: `app/Services/PembacaNaskahWord.php`
- Cetak: `app/Services/CetakNaskah.php` dan `app/Support/TataLetakNaskah.php`
- Layar: `app/Livewire/V2/` dan `resources/views/v2/`
- Data contoh (lokal): `php artisan db:seed --class=V2RancanganPdSeeder` (rancangan milik akun `perangkatdaerah`)
- Font PDF naskah: salin Bookman Old Style ke `resources/fonts/` (lihat `docs/DEPLOY.md`)
- Lambang Garuda pada kop: simpan di `public/assets/img/garuda.png`

Di Laragon, permintaan bersamaan bisa gagal 404 selama konfigurasi belum di-cache (lihat docs/DEPLOY.md).
Untuk mencoba lokal gunakan `php artisan serve`. Jangan menjalankan tes saat `config:cache` aktif,
karena tes akan memakai database utama.

## Struktur Singkat

```
app/
├── Livewire/          # Komponen per peran (Admin, Verifikator, Peneliti, PerangkatDaerah, ...)
├── Models/            # RancanganProdukHukum, FasilitasiProdukHukum, DokumentasiProdukHukum, ...
├── Services/          # KelengkapanBerkasChecker (cek kelengkapan berkas)
└── Http/Controllers/  # Ekspor PDF/Excel, template, profil
```

## Lisensi

Dirilis di bawah lisensi MIT.
