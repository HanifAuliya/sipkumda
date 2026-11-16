# Panduan Server Production SIFKUMDA

Checklist agar aplikasi aman dan tetap lancar saat dipakai banyak orang sekaligus,
beserta hasil load test yang mendasarinya.

## 1. Wajib setiap deploy

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **`config:cache` tetap wajib di server.** Tanpa itu, `.env` dibaca ulang di setiap request,
> sehingga aplikasi lebih lambat. Jalankan ulang setiap kali `.env` diubah.
>
> Di PHP thread-safe (Apache Windows), `putenv()` dulu membuat sebagian request gagal membaca
> `.env` saat ramai: error database acak, atau 404 pada `/livewire-*/update` karena alamat itu
> dihitung dari `APP_KEY`. `bootstrap/app.php` kini mematikan `putenv()` lewat
> `Env::disablePutenv()`. Pada uji 2.400 request bersamaan tanpa `config:cache`, tidak ada lagi
> 404 (sebelumnya 4 dari 800).
>
> Akibatnya, konfigurasi diambil dari file `.env`. Variabel lingkungan sistem operasi tidak terbaca
> lewat `getenv()`, kecuali yang masuk ke `$_ENV` atau `$_SERVER` (misalnya `SetEnv` di Apache).

### Bahan Penting bawaan (sekali saat pemasangan, dan setelah pembaruan template)

```bash
php artisan bahan-penting:bawaan
```

Mengisi tombol **Bahan Penting** milik Perangkat Daerah dengan Template Matriks Peraturan Bupati,
Template Matriks Keputusan Bupati, Contoh Nota Dinas Pengajuan, dan Daftar Periksa Pengajuan. Aman
dijalankan ulang: berkas bawaan diperbarui, berkas unggahan Admin tidak disentuh, dan bawaan yang
sengaja dihapus Admin tidak dibuat lagi (kecuali dengan `--pulihkan`).

### Ekstensi PHP untuk foto profil

Aktifkan `gd` (dengan JPEG, PNG, dan WebP) dan `exif` di `php.ini`. Tanpa `gd`, unggah foto ditolak
dengan pesan "Foto tidak bisa dibaca". Tanpa `exif`, foto HP bisa tersimpan miring.

### Font Bookman Old Style (sekali saat pemasangan server)

Produk hukum daerah wajib berhuruf Bookman Old Style 12 (Permendagri 80/2015 Pasal 167,
Permendagri 1/2023 Pasal 32). File font berlisensi dan tidak ikut di repo, jadi salin manual
`BOOKOS.TTF`, `BOOKOSB.TTF`, `BOOKOSI.TTF`, dan `BOOKOSBI.TTF` dari `C:\Windows\Fonts` komputer
ber-Office ke `resources/fonts/` dengan nama huruf besar seperti itu. Pastikan `storage/fonts`
bisa ditulis oleh web server. Tanpa file ini PDF naskah jatuh ke Times New Roman dan
`storage/logs/laravel.log` mencatat "tidak memakai Bookman Old Style".

## 2. `.env` production

| Variabel | Nilai | Catatan |
|---|---|---|
| `APP_ENV` | `production` | Mengaktifkan Content-Security-Policy |
| `APP_DEBUG` | `false` | Jangan pernah `true` di server publik |
| `APP_URL` | `https://domain-anda` | Dipakai juga oleh tautan aktivasi akun |
| `APP_TIMEZONE` | `Asia/Makassar` | Jam jadwal backup dan tanggal di aplikasi mengikuti nilai ini |
| `LOG_LEVEL` | `error` | |
| `SESSION_SECURE_COOKIE` | `true` | Bila memakai HTTPS |
| `CACHE_LIMITER` | `redis` (disarankan) | Kosong = database. Jangan `file` di Windows |
| `JDIH_RATE_LIMIT` | `120` | Pencarian per IP per menit; naikkan bila banyak pengunjung berbagi IP kantor |
| `JDIH_DOWNLOAD_LIMIT` | `30` | Unduhan PDF per IP per menit |

Debugbar hanya ada di `require-dev`, jadi tidak terpasang bila memakai `composer install --no-dev`.

## 3. Web server

- **HTTPS** wajib. Header HSTS otomatis dikirim saat request lewat HTTPS.
- **Apache Windows (mpm_winnt):** naikkan `ThreadsPerChild` (default 64), mis. `150`.
  Saat thread habis, request mengantre (tidak error), tetapi waktu tunggu bertambah.
- **Di belakang reverse proxy / Cloudflare:** daftarkan proxy di `bootstrap/app.php`
  (`$middleware->trustProxies(at: [...])`). Tanpa itu semua pengunjung terlihat ber-IP sama,
  sehingga batas login dan pencarian berlaku untuk semua orang sekaligus.
- Halaman publik (`/`, `/produk-hukum/...`) tidak memakai sesi dan mengirim
  `Cache-Control: public`, jadi aman di-cache CDN/reverse proxy.
- Batasi banjir request kasar (DoS) di level web server/firewall/CDN; rate limit aplikasi
  hanya menjaga operasi yang mahal (pencarian kata kunci, unduhan, login).

## 4. MySQL

Load test menunjukkan membuka koneksi MySQL memakan ±12 ms per request (query-nya sendiri ±1 ms).
Atur di `my.ini` / `my.cnf`:

```ini
skip-name-resolve              ; tanpa reverse-DNS per koneksi (penyebab utama koneksi lambat)
max_connections = 300          ; ≥ jumlah thread web server + queue worker
thread_cache_size = 64
innodb_buffer_pool_size = 1G   ; ±50–70% RAM bila server khusus database
```

## 5. Proses latar belakang

```bash
php artisan queue:work --tries=3     # dijalankan terus (Supervisor / NSSM di Windows)
php artisan schedule:run             # setiap menit (cron / Task Scheduler)
```

Tanpa `schedule:run`, tugas terjadwal tidak pernah berjalan:

| Tugas | Waktu | Isi |
|---|---|---|
| `backup:jalankan` | 01.00 | Cadangan database dan berkas (bagian 6) |
| `naskah:ingatkan` | 08.00 | Notifikasi pengingat untuk pemegang giliran naskah yang tertahan |
| `notifications:clear` | harian | Membersihkan notifikasi lama |
| `wa:kirim` | tiap menit | Mengirim pesan WhatsApp yang masih menunggu (bagian 7) |

Di hosting bersama yang tidak bisa menjalankan `queue:work` terus-menerus, isi `QUEUE_LEWAT_JADWAL=true`:
penjadwal menjalankan worker tiap menit sampai antrean kosong. Lihat bagian 9.

Batas hari naskah dianggap tertahan diatur `SIFKUMDA_HARI_TERTAHAN` di `.env` (bawaan `7`). Pengingat
dikirim saat lamanya tepat kelipatan batas itu (hari ke-7, 14, 21, ...). Tahap Penandatanganan tidak
diingatkan. Angka yang sama dipakai di menu Laporan & Pemantauan.

## 6. Backup

`php artisan backup:jalankan` berjalan otomatis setiap pukul 01.00. Database disimpan sebagai
`database/<nama>-<tanggal>.sql.gz` dan disimpan 14 hari. Berkas unggahan disalin ke `berkas/`; hanya
file baru atau berubah yang disalin, dan salinan tidak ikut terhapus saat file aslinya dihapus.
Bila gagal, kesalahan dicatat di `storage/logs/laravel.log` dan Admin menerima notifikasi.

| Variabel | Bawaan | Catatan |
|---|---|---|
| `BACKUP_DIR` | `storage/app/backup` | **Arahkan ke disk lain atau folder jaringan.** Di disk yang sama, cadangan ikut hilang bila disknya rusak |
| `BACKUP_MYSQLDUMP` | `mysqldump` | Di Windows biasanya perlu alamat lengkap, misalnya `C:\mysql\bin\mysqldump.exe` |
| `BACKUP_SIMPAN_HARI` | `14` | Tiga cadangan terbaru selalu disimpan walau sudah lebih lama |

Sesudah pemasangan, jalankan `php artisan backup:jalankan` sekali dan pastikan file `.sql.gz` muncul.
Cadangan berisi data pribadi, jadi batasi akses foldernya.

**Memulihkan.** Uji dulu ke database baru supaya data yang masih ada tidak tertimpa:

```bash
mysql -u root -p -e "CREATE DATABASE sipkumda_pulih CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
gunzip -c database/sipkumda-2026-01-31-010000.sql.gz | mysql -u root -p sipkumda_pulih
```

Sesudah isinya benar, arahkan `DB_DATABASE` ke database itu lalu jalankan `php artisan config:cache`.
Salin isi `berkas/private` dan `berkas/public` ke `storage/app/private` dan `storage/app/public`.
File dump tidak memuat nama database, jadi tidak akan menimpa database lain selama nama tujuannya benar.

## 7. Notifikasi WhatsApp

Notifikasi naskah juga dikirim ke WhatsApp lewat satu nomor kantor yang disambungkan seperti WhatsApp Web.
Pengirimnya program open source **GOWA** ([go-whatsapp-web-multidevice](https://github.com/aldinokemal/go-whatsapp-web-multidevice))
yang berjalan di server yang sama, jadi isi pesan tidak lewat layanan pihak ketiga (di hosting bersama, GOWA berjalan
di komputer kantor dan dihubungi lewat Cloudflare Tunnel; lihat bagian 9). Jalur ini **bukan jalur resmi Meta**:
nomornya bisa diblokir bila dianggap spam. Pakai nomor yang sudah lama aktif dengan WhatsApp Business, dan minta
pegawai menyimpan nomor itu sebagai kontak.

**Memasang GOWA (sekali)**

1. Unduh `whatsapp_<versi>_windows_amd64.zip` (atau `linux_amd64`) dari halaman rilis, cocokkan dengan `checksums.txt`,
   lalu ekstrak ke folder tersendiri di luar folder aplikasi. Versi yang diuji: **v9.7.0**.
2. Jalankan dengan pengaturan berikut (contoh Windows: `jalankan-gowa.bat` di folder yang sama):

   ```bat
   set APP_HOST=127.0.0.1
   set APP_PORT=3000
   set APP_BASIC_AUTH=sifkumda:<password-acak-panjang>
   set APP_OS=SIFKUMDA
   set APP_UI_ENABLED=false
   set APP_UI_AUTO_UPDATE=false
   set MCP_ENABLED=false
   set WHATSAPP_AUTO_REPLY=Nomor ini hanya mengirim notifikasi otomatis SIFKUMDA. Mohon tidak membalas.
   windows-amd64.exe rest
   ```

   `APP_HOST=127.0.0.1` wajib: GOWA hanya boleh diakses dari server itu sendiri. Dashboard bawaan GOWA dan
   endpoint MCP dimatikan karena tidak dipakai.
3. Pasang sebagai layanan yang otomatis berjalan saat server menyala (NSSM di Windows, systemd di Linux), seperti queue worker.
4. Isi `.env` lalu `php artisan config:cache`:

   | Variabel | Nilai |
   |---|---|
   | `WA_DRIVER` | `gowa` (`log` = hanya dicatat di log) |
   | `WA_GOWA_URL` | `http://127.0.0.1:3000` |
   | `WA_GOWA_USER` / `WA_GOWA_PASSWORD` | sama dengan `APP_BASIC_AUTH` GOWA |

5. Masuk sebagai Admin, buka menu **WhatsApp**, lalu scan QR dari HP kantor (*Perangkat tertaut → Tautkan perangkat*).
   Kirim pesan uji dari halaman yang sama.

**Cara kerja.** Setiap notifikasi lonceng ikut membuat pesan di tabel `pesan_wa` bagi pengguna yang mengisi nomor WhatsApp
dan tidak mematikannya. Bawaannya hanya notifikasi yang perlu tindakan (giliran dan pengembalian); Admin bisa mengubahnya.
Pesan dikirim oleh queue worker dan oleh `wa:kirim` di penjadwal setiap menit, dengan jeda 2–5 detik antarpesan.

- Sambungan putus: pesan tetap menunggu dan terkirim begitu tersambung lagi. Pesan yang sudah dibuka di lonceng,
  atau tertunda lebih dari 48 jam (`WA_KEDALUWARSA_JAM`), tidak dikirim.
- Admin melihat peringatan di Beranda bila ada pesan menunggu lebih dari 10 menit.
- Sambungan biasanya putus karena GOWA perlu diperbarui setelah WhatsApp berubah, server atau GOWA mati,
  HP kantor tidak membuka WhatsApp sekitar 14 hari, atau perangkat tertaut dikeluarkan dari HP.
- Folder `storages` GOWA menyimpan sesi WhatsApp. Jangan dihapus, dan jangan dibagikan: siapa pun yang memegangnya
  bisa mengirim pesan atas nama nomor kantor.

## 8. Hasil load test

Lingkungan: Apache 2.4.62 + PHP 8.5 (mod_php, 64 thread), MySQL 8.0.40, 16 CPU logis,
mode production dengan `config:cache`. Data: 10.009 produk hukum dan 3.024 rancangan.
Alat: Apache Bench, semua request dari satu mesin (kondisi terburuk untuk rate limit).

**Halaman publik (JDIH)**

| Halaman | Pengguna bersamaan | Request/detik | p50 | p99 | Gagal |
|---|---|---|---|---|---|
| Beranda | 50 | 222 | 181 ms | 898 ms | 0 |
| Beranda | 200 | 204 | 920 ms | 1,4 s | 0 |
| Daftar produk hukum | 100 | 180 | 534 ms | 905 ms | 0 |
| Detail | 50 | 223 | 214 ms | 342 ms | 0 |
| Cari kata kunci | 100 | 110 | 844 ms | 1,6 s | 0 |
| Cari nomor | 50 | 131 | 377 ms | 484 ms | 0 |
| Unduh PDF | 50 | 128 | 383 ms | 472 ms | 0 |

**Aplikasi dalam (60 pengguna login bersamaan, 4 akun berbeda)**

| Halaman | Total request/detik | p50 | p99 | Gagal |
|---|---|---|---|---|
| Dashboard | ±168 | 340 ms | 545 ms | 0 |
| Halaman kerja utama tiap role | ±106 | 500–530 ms | ≤1,1 s | 0 |
| Daftar Rancangan | ±101 | 570 ms | 900 ms | 0 |

Sebagai gambaran, 60 pegawai yang masing-masing berpindah halaman tiap 10 detik hanya
membutuhkan ±6 request/detik.

**Temuan yang sudah diperbaiki selama load test**

- Konfigurasi `.env` tidak aman untuk banyak thread → wajib `config:cache` (lihat bagian 1).
- Penghitung rate limit menulis ke MySQL di setiap kunjungan → kini hanya untuk pencarian
  dan unduhan; halaman lain tidak menulis apa pun.
- Store `file` untuk rate limit gagal di Windows saat ramai (kunci file eksklusif) → tidak dipakai.
- Pencarian nomor memindai seluruh indeks tanggal → kini memakai indeks tahun/nomor.

## 9. Hostinger Web Hosting (hPanel, tanpa SSH) dengan GOWA di komputer kantor

Penempatan sementara: aplikasi di hosting bersama Hostinger dan diperbarui lewat **Git** (hPanel menarik dari
GitHub), sedangkan GOWA berjalan di satu komputer kantor dan dihubungi lewat Cloudflare Tunnel. Hosting bersama
tidak punya SSH, Node.js, maupun proses latar belakang, jadi:

- **GitHub Actions** (`.github/workflows/produksi.yml`) membangun branch **`produksi`** setiap ada push ke `main`:
  `vendor` tanpa paket pengembangan dan aset hasil build sudah di dalamnya.
- **hPanel › Git** menarik branch `produksi` ke folder subdomain, otomatis lewat webhook.
- **Cron** tiap menit menjalankan `sifkumda:selaraskan` (migrasi, data dasar, dan cache bila versi atau `.env`
  berubah; jejaknya di `storage/logs/rilis.log`) lalu `schedule:run` (tugas terjadwal, antrean, WhatsApp).

```
push main ─▶ GitHub Actions ─▶ branch produksi ─▶ hPanel Git (pull) ─▶ cron: sifkumda:selaraskan
Hostinger ──HTTPS──▶ Cloudflare ──tunnel──▶ PC kantor: cloudflared ─▶ GOWA 127.0.0.1:3000 ─▶ WhatsApp
```

Isi branch `produksi` (folder subdomain):

```
.htaccess      semua permintaan dibelokkan ke aplikasi/public
aplikasi/      Laravel lengkap; .env, font, dan storage milik server tidak ikut repo
```

`.env`, kode, dan `storage` tidak bisa dibuka dari browser karena setiap permintaan dibelokkan ke
`aplikasi/public`. `composer.json` sengaja tidak di root, supaya hPanel tidak menjalankan `composer update` sendiri.

### 9.1 Siapkan di hPanel (sekali)

| Menu hPanel | Yang dibuat atau dicek |
|---|---|
| **Advanced › PHP Configuration** | PHP **8.4 atau lebih baru** (paket Symfony 8 butuh 8.4.1+). Ekstensi `gd`, `exif`, `zip`, `fileinfo`, `pdo_mysql`, `mbstring`, `intl` aktif |
| **Domains › Subdomains** | Buat subdomain, mis. `sifkumda`. Catat foldernya, mis. `public_html/sifkumda` |
| **Security › SSL** | Pasang SSL gratis untuk subdomain itu |
| **Databases › MySQL Databases** | Buat database dan penggunanya; catat nama (berawalan `u123456789_`) dan kata sandinya |
| **Emails** | Akun pengirim, mis. `noreply@domain-anda`, untuk aktivasi akun dan atur ulang sandi |

### 9.2 Sambungkan Git

1. **File Manager**: kosongkan folder subdomain (hapus `default.php` atau `index.php` bawaan Hostinger).
   hPanel hanya mau memasang repo ke folder kosong.
2. **Advanced › Git › Create a New Repository**:
   - Repository: `https://github.com/HanifAuliya/sipkumda.git` (repo publik, tanpa kunci)
   - Branch: `produksi`
   - Directory: folder subdomain, mis. `sifkumda`
3. Tekan **Deploy** sekali, lalu nyalakan **Auto Deployment**. Bila hPanel menampilkan URL webhook, tambahkan di
   GitHub › Settings › Webhooks (event *push*).

### 9.3 `.env` dan font (sekali, lewat File Manager)

Tampilkan file tersembunyi di File Manager (Settings › Show hidden files), lalu buat `aplikasi/.env`:

```dotenv
APP_NAME=SIFKUMDA
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://sifkumda.domain-anda
APP_TIMEZONE=Asia/Makassar
APP_LOCALE=id
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u123456789_sifkumda
DB_USERNAME=u123456789_sifkumda
DB_PASSWORD=...

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=database
QUEUE_LEWAT_JADWAL=true
FILESYSTEM_DISK=local

MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_ENCRYPTION=ssl
MAIL_USERNAME=noreply@domain-anda
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=noreply@domain-anda
MAIL_FROM_NAME=SIFKUMDA

WA_DRIVER=gowa
WA_GOWA_URL=https://wa.domain-anda
WA_GOWA_USER=sifkumda
WA_GOWA_PASSWORD=<sama dengan APP_BASIC_AUTH GOWA>

# Super Admin pertama, dibuat otomatis sekali saja.
SIFKUMDA_SUPERADMIN_NIP=<NIP>
SIFKUMDA_SUPERADMIN_NAMA="Nama Lengkap"
SIFKUMDA_SUPERADMIN_EMAIL=nama@domain-anda
```

`APP_KEY` dibiarkan kosong: `sifkumda:selaraskan` membuatnya sendiri. `CACHE_STORE=file` aman di server Linux
dan tidak bergantung tabel database saat pemasangan pertama.

Unggah keempat font Bookman (bagian 1) ke `aplikasi/resources/fonts/` (buat foldernya).

### 9.4 Cron (sekali)

**Advanced › Cron Jobs › Custom**, setiap menit (`* * * * *`), sesuaikan jalurnya:

```bash
cd /home/u123456789/domains/domain-anda/public_html/sifkumda/aplikasi && php artisan sifkumda:selaraskan >> /dev/null 2>&1; php artisan schedule:run >> /dev/null 2>&1
```

Dalam satu sampai dua menit, `storage/logs/rilis.log` berisi jejak pemasangan: `APP_KEY` dibuat, migrasi,
peran, daftar Perangkat Daerah, Bahan Penting, Super Admin, dan cache. **Kata sandi sementara Super Admin
tercatat di log itu**: masuk, ganti kata sandinya, lalu hapus `rilis.log` (dibuat lagi pada rilis berikutnya
tanpa kata sandi). Bila log berisi `GAGAL`, perbaiki penyebabnya (biasanya isian database di `.env`); langkahnya
diulang otomatis tiap menit.

Bila log menyebut versi PHP terlalu rendah, ganti `php` di cron dengan alamat lengkap, mis.
`/opt/alt/php84/usr/bin/php`.

### 9.5 Memperbarui aplikasi

Cukup push ke `main`. GitHub Actions membangun `produksi` (±2 menit), hPanel menariknya, dan cron menjalankan
migrasi serta memperbarui cache dalam semenit. Status pembangunan terlihat di tab **Actions** GitHub; versi yang
terpasang ada di `aplikasi/REVISION` dan `storage/app/selaraskan.json`. Menjalankan ulang tanpa perubahan kode:
ubah `.env` (mis. tambah baris komentar).

Jangan mengubah file yang ada di repo langsung dari File Manager: `git pull` berikutnya bisa gagal. Yang boleh
diubah hanya `.env`, font, dan isi `storage`.

### 9.6 GOWA dan Cloudflare Tunnel di PC kantor

Syarat: DNS domain dikelola Cloudflare. Bila domain masih memakai DNS Hostinger, pindahkan nameserver ke
Cloudflare (salin dulu semua record DNS, termasuk subdomain dan email ke Hostinger), atau pakai domain lain
yang sudah di Cloudflare khusus untuk `wa`.

1. Pasang GOWA seperti bagian 7 (tetap `APP_HOST=127.0.0.1` dan `APP_BASIC_AUTH` dengan kata sandi acak panjang),
   dan jadikan layanan Windows dengan NSSM supaya menyala sendiri. Matikan mode tidur PC di Power Options.
2. Cloudflare › **Zero Trust › Networks › Tunnels › Create a tunnel** (Cloudflared), beri nama `sifkumda-wa`,
   pilih Windows, lalu jalankan perintah `cloudflared.exe service install <token>` yang ditampilkan di
   Command Prompt **Run as administrator**. Tunnel menjadi layanan Windows yang otomatis menyala.
3. Tab **Public Hostname**: subdomain `wa`, domain Anda, service `HTTP` → `127.0.0.1:3000`.
4. Masuk SIFKUMDA sebagai Admin › menu **WhatsApp**: statusnya harus tersambung ke GOWA. Scan QR dari HP kantor,
   lalu kirim pesan uji.
5. Batasi akses: **Security › WAF › Custom rules**, blokir bila *Hostname* sama dengan `wa.domain-anda` **dan**
   *IP Source Address* bukan IP server Hostinger (terlihat di **Security › Events** Cloudflare setelah langkah 4,
   atau di ringkasan hosting hPanel). Tanpa aturan ini GOWA hanya dijaga kata sandi Basic Auth.

Bila PC kantor mati, pesan WhatsApp menunggu (paling lama 48 jam) dan terkirim begitu menyala lagi; notifikasi
lonceng di aplikasi tetap berjalan. Isi pesan melewati Cloudflare (terenkripsi HTTPS).

### 9.7 Cadangan

Cadangan harian (bagian 6) tersimpan di disk hosting yang sama; unduh berkala dari File Manager
(`aplikasi/storage/app/backup`), dan andalkan juga backup bawaan Hostinger. Bila `mysqldump` tidak tersedia,
log rilis atau `laravel.log` mencatat kegagalan backup.

Paket ZIP manual (`scripts\paket-hostinger.ps1`) tetap bisa dipakai bila Git di hPanel tidak tersedia.
