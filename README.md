# SIFKUMDA — Sistem Informasi Fasilitasi Produk Hukum Daerah

Aplikasi web Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah untuk menyusun, meneliti,
memfasilitasi, dan mengarsipkan **Peraturan Bupati** dan **Keputusan Bupati**, ditambah halaman publik
JDIH untuk mencari produk hukum yang sudah ditetapkan.

Satu naskah dikerjakan bersama di satu **matriks** (rumusan awal di kiri, penyempurnaan di kanan) dari
pengajuan sampai diarsipkan. Naskah Word/PDF, matriks PDF, dan nota dinas dicetak dari matriks itu,
jadi tidak ada unggah ulang berkas di setiap tahap.

**Dokumen lain**

| Dokumen | Untuk siapa |
|---|---|
| [docs/DEPLOY.md](docs/DEPLOY.md) | Pengelola server: pemasangan, `.env`, cadangan dan pemulihan |
| [docs/RUNBOOK-ADMIN.md](docs/RUNBOOK-ADMIN.md) | Admin Bagian Hukum: pekerjaan rutin dan masalah yang sering muncul |
| [docs/SERAH-TERIMA.md](docs/SERAH-TERIMA.md) | Pimpinan dan pengelola TI: akses yang harus dipegang instansi |
| [docs/panduan/](docs/panduan/) | Pengguna: panduan per peran |

## Alur

| Langkah | Tahap | Dikerjakan oleh |
|---|---|---|
| 1 | Penyusunan → Persetujuan Berkas | Perangkat Daerah menyusun dan mengajukan; Admin memeriksa berkas |
| 2 | Menunggu Peneliti → Penelitian ⇄ Perbaikan PD | Verifikator menugaskan Peneliti; Peneliti memeriksa per rumusan; PD menyesuaikan |
| 3 | Validasi Rancangan → Rancangan Disetujui | Verifikator |
| 4 | Persetujuan Fasilitasi → Validasi Fasilitasi ⇄ Perbaikan Fasilitasi / Pengecekan Peneliti | PD mengajukan, Peneliti memeriksa, Verifikator menyetujui; isi dikunci |
| 5 | Menunggu Nota Dinas → Penandatanganan → Menunggu Arsip | Bagian Hukum menerbitkan nota dinas; Admin atau Tamu mencatat paraf dan tanda tangan |
| 6 | Selesai | Admin atau Verifikator mengarsipkan ke JDIH |

Aturan perpindahan tahap ada di `app/Services/AlurNaskah.php` (konstanta `AKSI`) dan `app/Enums/TahapNaskah.php`.

## Peran

| Peran | Pekerjaan |
|---|---|
| Perangkat Daerah | Menyusun dan mengajukan rancangan, menyesuaikan rumusan, mengajukan fasilitasi |
| Admin | Memeriksa berkas, menerbitkan nota dinas, mencatat tanda tangan, mengarsipkan, mengelola pengguna dan pengaturan |
| Verifikator | Menugaskan peneliti, memvalidasi rancangan dan fasilitasi, menerbitkan nota dinas, mengelola tanda tangan |
| Peneliti | Meneliti rumusan, memeriksa naskah fasilitasi |
| Tamu | Mencatat paraf dan tanda tangan basah (petugas di lingkungan Sekda) |
| Super Admin | Seluruh akses Admin |

## Yang bisa diatur tanpa mengubah kode

| Menu | Isi |
|---|---|
| Pengaturan › Pengaturan Naskah | Nama Bupati dan Sekda; urutan paraf dan tanda tangan |
| Pengaturan › Format Dokumen | Kertas, margin, huruf, kop, paraf hierarki, pola nomor nota dinas dan nomor Keputusan Bupati, isi nota dinas |
| Pengaturan › Kelola TTD | Pejabat penanda tangan nota dinas |
| Pengaturan › Template Bahan Penting | Berkas yang bisa diunduh Perangkat Daerah |
| Pengaturan › Perangkat Daerah | Daftar instansi |
| Akun › Daftar Pengguna | Akun, peran, aktivasi, kata sandi sementara |
| `.env` `SIFKUMDA_HARI_TERTAHAN` | Batas hari naskah dianggap tertahan (Laporan dan pengingat otomatis), bawaan 7 |

## Teknologi

- PHP 8.3+ (dipakai 8.5), Laravel 13, Livewire 4, Alpine.js, Tailwind CSS 4, Vite
- MySQL 8
- Spatie Laravel Permission (peran), DomPDF dan PHPWord (cetak), Laravel Excel (ekspor), PDF Parser
- Pest (tes)

## Menjalankan di komputer sendiri

Prasyarat: PHP 8.3+, Composer, Node.js, MySQL.

```bash
git clone <alamat repositori>
cd sipkumda
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Atur `DB_*` di `.env`, lalu:

```bash
php artisan migrate
php artisan db:seed --class=PerangkatDaerahSeeder
php artisan db:seed --class=RoleSeeder
php artisan akun:super-admin <NIP> --nama="Nama Lengkap" --email=nama@contoh.go.id
php artisan storage:link
npm run dev
php artisan serve
```

Perintah `akun:super-admin` mencetak kata sandi sementara yang wajib diganti saat pertama masuk.

**Data contoh (hanya untuk komputer pengembang):** `UserSeeder` membuat satu akun per peran dengan
kata sandi `password`. `V2RancanganPdSeeder` mengisi rancangan contoh di tahap awal, dan `DemoSeeder`
menambah akun Perangkat Daerah, tanda tangan, arsip lama, lalu `DemoAlurSeeder`: naskah yang menunggu
nota dinas, sedang ditandatangani, menunggu arsip, selesai, dan tertahan lama (waktunya dimundurkan
supaya menu Laporan & Pemantauan berisi). **Jangan dijalankan di server produksi.**

**Panduan dan tur.** Panduan per peran ada di `docs/panduan/*.md` dan tampil di menu Panduan beserta
screenshot dari `public/img/panduan/`. Gambar dirujuk relatif (`../../public/img/panduan/…`) supaya
terbaca juga di GitHub. Tur pengenalan di Dashboard diatur di `app/Support/TurPengenalan.php`; target
sorotannya adalah atribut `data-tur` di tampilan (menu samping memakai `menu-<nama menu>`).

Font Bookman Old Style untuk PDF naskah tidak ikut di repositori; lihat [docs/DEPLOY.md](docs/DEPLOY.md).

## Tes

```bash
php artisan test
```

Tes memakai database `sipkumda_testing` yang dikosongkan setiap kali dijalankan. Pengaman ada di
`phpunit.xml` dan `tests/TestCase.php`. **Jangan menjalankan tes bila `bootstrap/cache/config.php`
ada** (sesudah `php artisan config:cache`), karena tes bisa memakai database utama. Hapus dulu dengan
`php artisan config:clear`.

## Peta kode

| Bagian | Letak |
|---|---|
| Alur dan aturan tahap | `app/Services/AlurNaskah.php`, `app/Enums/TahapNaskah.php` |
| Layar kerja matriks | `app/Livewire/V2/LayarKerja.php`, `BarisKerja.php`, `resources/views/v2/layar-kerja.blade.php` |
| Nota dinas, tanda tangan, arsip | `app/Livewire/V2/PantauNaskah.php`, `resources/views/v2/pantau-naskah.blade.php` |
| Daftar dan Dashboard | `app/Livewire/V2/DaftarNaskah.php`, `Beranda.php` |
| Membaca file Word | `app/Services/PembacaNaskahWord.php` |
| Cetak Word/PDF | `app/Services/CetakNaskah.php`, `app/Support/TataLetakNaskah.php`, `resources/views/pdf/v2/` |
| Format Dokumen | `app/Support/FormatDokumen.php`, `app/Livewire/V2/PengaturanFormat.php` |
| Menu | `app/Support/Navigation.php` |
| Dokumentasi dan JDIH | `app/Livewire/Dokumentasi/`, `app/Http/Controllers/JdihController.php`, `resources/views/jdih/` |
| Laporan & Pemantauan | `app/Support/LaporanNaskah.php`, `app/Support/PeriodeLaporan.php`, `app/Support/KalimatRiwayat.php` (kalimat Aktivitas), `app/Livewire/V2/Laporan.php`, `app/Exports/LaporanLengkapExport.php`, `resources/views/pdf/laporan.blade.php` |
| Pengingat naskah tertahan | `app/Console/Commands/IngatkanNaskahTertahan.php` (setiap pukul 08.00) |
| Cadangan | `app/Console/Commands/BackupAplikasi.php` (setiap pukul 01.00) |
| Akun darurat | `app/Console/Commands/AkunSuperAdmin.php` (`php artisan akun:super-admin`) |

Nama folder dan namespace `V2` adalah sisa masa peralihan dan tidak terlihat oleh pengguna.

**Aplikasi sebelumnya.** Alur lama (rancangan, revisi, fasilitasi, nota dinas) sudah dihapus beserta
tabelnya (migrasi `2026_11_12_000001_hapus_tabel_alur_lama`). Kodenya disimpan di cabang `v1/cadangan`.

## Lisensi

MIT.
