# Serah Terima SIFKUMDA

Daftar akses dan tanggung jawab yang harus dipegang instansi, supaya SIFKUMDA tetap bisa dipakai,
dipelihara, dan dipulihkan tanpa bergantung pada satu orang. Untuk Kepala Bagian Hukum dan pengelola
TI (Diskominfo atau petugas yang ditunjuk).

Isi kolom **Pemegang** dan centang kolom **Selesai** saat serah terima. Kata sandi **jangan** ditulis di
dokumen ini; simpan di tempat resmi (brankas kata sandi instansi atau amplop tertutup di Bagian Hukum).

## 1. Akses yang harus dipindahkan ke instansi

| No | Akses | Kondisi sekarang | Yang perlu dilakukan | Pemegang | Selesai |
|---|---|---|---|---|---|
| 1 | Repositori kode (GitHub) | Akun pribadi `HanifAuliya/sipkumda` | Pindahkan (*Transfer ownership*) ke akun atau organisasi GitHub milik instansi, atau undang minimal satu pengelola TI sebagai *Admin* repositori | | ☐ |
| 2 | Server aplikasi | | Akun administrator server atas nama instansi; catat alamat, sistem operasi, dan cara masuk | | ☐ |
| 3 | Database MySQL | | Akun `root` dan akun aplikasi; kata sandi disimpan di tempat resmi | | ☐ |
| 4 | Nama domain dan DNS | | Pastikan terdaftar atas nama instansi dan tanggal perpanjangannya tercatat | | ☐ |
| 5 | Sertifikat HTTPS | | Catat cara perpanjangan (otomatis atau manual) | | ☐ |
| 6 | Akun email pengirim (SMTP) | | Dipakai untuk aktivasi akun dan Lupa Password; atas nama instansi, bukan email pribadi | | ☐ |
| 7 | File `.env` di server | Berisi kunci aplikasi dan kata sandi database | Salinannya disimpan di tempat resmi; **jangan** diunggah ke GitHub | | ☐ |
| 8 | Lokasi cadangan | `BACKUP_DIR` di `.env` | Arahkan ke disk lain atau folder jaringan; catat lokasinya | | ☐ |
| 9 | Font Bookman Old Style | Disalin manual ke server | Simpan salinan file font bersama arsip serah terima | | ☐ |

## 2. Akun di dalam aplikasi

| No | Yang dipastikan | Pemegang | Selesai |
|---|---|---|---|
| 1 | Minimal **dua** akun Admin yang dipegang orang berbeda | | ☐ |
| 2 | Minimal **satu** akun Super Admin cadangan, dipegang Kepala Bagian Hukum | | ☐ |
| 3 | Minimal satu Verifikator aktif dan data Kelola TTD sudah benar | | ☐ |
| 4 | Akun contoh (`superadmin`, `admin`, `verifikator`, `perangkatdaerah`, `peneliti`, `tamu` dengan kata sandi `password`) **tidak ada** di server produksi | | ☐ |
| 5 | Pengelola server tahu perintah darurat `php artisan akun:super-admin` | | ☐ |

## 3. Sebelum aplikasi dipakai umum

| No | Langkah | Selesai |
|---|---|---|
| 1 | Pemasangan mengikuti [DEPLOY.md](DEPLOY.md), termasuk `config:cache`, HTTPS, dan `APP_DEBUG=false` | ☐ |
| 2 | Penjadwal (`php artisan schedule:run` setiap menit) terpasang, lalu `php artisan backup:jalankan` dicoba sekali dan file cadangan muncul | ☐ |
| 3 | Pemulihan dari cadangan dicoba sekali ke database terpisah | ☐ |
| 4 | Email dicoba: tambah satu pengguna dan pastikan tautan aktivasi sampai | ☐ |
| 5 | Satu Peraturan Bupati dan satu Keputusan Bupati dicoba dari pengajuan sampai muncul di JDIH | ☐ |
| 6 | Format Dokumen, nama pejabat, urutan tanda tangan, dan Kelola TTD sudah sesuai keadaan sekarang | ☐ |
| 7 | Hanya `PerangkatDaerahSeeder` dan `RoleSeeder` yang dijalankan; **bukan** `UserSeeder`, `DemoSeeder`, atau `V2RancanganPdSeeder` | ☐ |

## 4. Pembagian tugas sesudah serah terima

| Tugas | Dikerjakan oleh | Panduan |
|---|---|---|
| Pengguna, pengaturan, membetulkan kesalahan naskah, arsip | Admin Bagian Hukum | [RUNBOOK-ADMIN.md](RUNBOOK-ADMIN.md) |
| Server, cadangan, pemulihan, email, pembaruan keamanan | Pengelola TI | [DEPLOY.md](DEPLOY.md) |
| Perubahan alur atau fitur baru | Pengembang (internal atau pihak ketiga) | [README.md](../README.md) |
| Pelatihan pengguna baru | Admin Bagian Hukum | [panduan/](panduan/) dan menu **Panduan** di aplikasi |

## 5. Yang masih butuh pengembang

Hal-hal ini sengaja belum bisa diatur dari aplikasi, karena jarang berubah atau berisiko bila diubah sembarangan:

- Urutan dan nama **tahap alur** (Penyusunan sampai Selesai) dan siapa yang memegang tiap tahap.
- Awalan nomor rancangan (PB untuk Peraturan Bupati, SK untuk Keputusan Bupati).
- Jenis produk hukum selain Peraturan Bupati dan Keputusan Bupati.
- Susunan bagian naskah (Judul, Menimbang, Mengingat, dan seterusnya) dan cara membaca file Word.

Kode aplikasi sebelum alur ini disimpan di cabang `v1/cadangan` repositori.
