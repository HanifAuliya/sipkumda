# Runbook Admin SIFKUMDA

Panduan pekerjaan rutin dan cara mengatasi masalah yang sering muncul, untuk Admin dan Verifikator
Bagian Hukum. Semua langkah di sini dilakukan dari aplikasi, tanpa programmer, kecuali bagian
**Perlu bantuan pengelola server** di akhir.

Istilah menu mengikuti menu di sisi kiri aplikasi.

---

## 1. Akun pengguna

Menu: **Akun › Daftar Pengguna** (Admin dan Super Admin bisa mengubah; Verifikator hanya melihat).

### Menambah pengguna
1. Tekan **Tambah Pengguna**.
2. Isi Nama, NIP (18 digit), Email, Role, dan Perangkat Daerah, lalu simpan.
3. Sistem mengirim **tautan aktivasi** ke email itu. Pengguna membuka tautan dan membuat kata sandinya sendiri.

Kalau muncul pesan *Email belum terkirim*, atau pengguna tidak menerima email:
- Coba sekali lagi dengan tombol **Kirim Ulang Aktivasi** (ikon pesawat kertas) di baris pengguna itu.
- Kalau tetap tidak sampai, pakai **kata sandi sementara** (lihat di bawah).

### Kata sandi sementara
Dipakai bila email tidak bisa dipakai, atau pengguna lupa kata sandi dan tidak bisa membuka emailnya.

1. Di baris pengguna, tekan tombol **kunci** (Buat kata sandi sementara), lalu **Ya, buat**.
2. Jendela menampilkan NIP dan kata sandi sementara, misalnya `kq7m-4tr2-hb9x`. **Kata sandi ini hanya tampil sekali.**
3. Sampaikan langsung kepada yang bersangkutan (bertemu langsung atau telepon), bukan lewat grup obrolan.
4. Saat masuk, pengguna langsung dibawa ke halaman Profil dan wajib membuat kata sandi baru sebelum bisa membuka menu lain.

Kata sandi lama pengguna itu langsung tidak berlaku. Tombol ini tidak ada untuk akun Anda sendiri;
kata sandi sendiri diganti di **Profil**.

### Pengguna lupa kata sandi
- Bila emailnya masih bisa dibuka: minta ia memakai **Lupa password?** di halaman masuk.
- Bila tidak: buat **kata sandi sementara**.

### Pegawai pindah tugas atau berhenti
- **Akun yang belum pernah dipakai** bisa dihapus dengan tombol **Hapus**.
- **Akun yang sudah tercatat pada naskah** tidak bisa dihapus, supaya riwayat naskah tetap utuh. Agar akun
  lama tidak bisa dipakai lagi, buat kata sandi sementara baru dan jangan sampaikan kepada siapa pun.
  Lalu buat akun baru untuk penggantinya.
- Mengganti peran atau Perangkat Daerah seseorang: tombol **Ubah**.

---

## 2. Data dan pengaturan

### Perangkat Daerah baru, ganti nama, atau digabung
Menu: **Pengaturan › Perangkat Daerah** (Admin).
- **Tambah Perangkat Daerah** untuk instansi baru.
- **Ubah** untuk mengganti nama. Nama baru langsung tampil di semua akun, naskah, dan dokumen.
- **Hapus** hanya untuk yang belum dipakai. Yang sudah dipakai cukup diganti namanya.

### Bupati atau Sekda berganti
Menu: **Pengaturan › Pengaturan Naskah** › Nama Pejabat › **Ubah**.
Nama ini dipakai untuk naskah yang dibuat sesudahnya. Naskah yang sudah ada diperbaiki lewat baris
Penutup di matriksnya.

### Pejabat penanda tangan nota dinas berganti
Menu: **Pengaturan › Kelola TTD** (Verifikator).
Tambah penanda tangan baru dan nonaktifkan yang lama. Penanda tangan yang sudah dipakai pada nota dinas
tidak bisa dihapus, cukup dinonaktifkan.

### Urutan paraf dan tanda tangan berubah
Menu: **Pengaturan › Pengaturan Naskah** › Urutan Tanda Tangan › **Ubah** (Admin).
- Ganti nama, naikkan atau turunkan urutan, **Tambah Tahapan**, atau hapus tahapan (paling banyak 8).
- **Pakai Urutan Bawaan** mengembalikan Paraf Koordinasi → Asisten → Sekda → Bupati.
- Urutan baru langsung berlaku, termasuk untuk naskah yang sedang menunggu tanda tangan. Tanggal yang
  sudah dicatat pada tahapan yang dihapus tidak dipakai lagi. Sebaiknya diubah saat tidak ada naskah di
  tahap Penandatanganan; jumlahnya ditampilkan saat mengubah.

### Kop, margin, huruf, nomor, atau isi nota dinas berubah
Menu: **Pengaturan › Format Dokumen**.
1. Pilih tab Keputusan Bupati, Peraturan Bupati, atau Nota Dinas.
2. Ubah isiannya. Pratinjau PDF di sebelah kanan ikut berubah sebelum disimpan, memakai naskah sungguhan.
3. Tekan **Simpan**. Kalau salah, **Kembalikan ke Bawaan**.

Di sini juga diatur **pola nomor nota dinas** (mis. `100.3/{nomor}/ND/KUM/{tahun}`) dan **pola nomor
keputusan** (mis. `140/{nomor}/412.1.6/TAHUN {tahun}`).

Nota dinas yang sudah terbit **tidak ikut berubah**. Bila perlu diperbarui, buka naskahnya dan pakai
**Perbaiki & Terbitkan Ulang** (lihat bagian 3).

### Template untuk Perangkat Daerah
Menu: **Pengaturan › Template Bahan Penting** (Admin). Berkas di sini muncul di tombol **Bahan Penting**
pada menu Rancanganku milik Perangkat Daerah.

---

## 3. Membetulkan kesalahan pada naskah

| Masalah | Yang dilakukan |
|---|---|
| Naskah salah dikirim ke tahap berikutnya | Di layar kerja, menu **Lainnya** berisi tombol "Kembalikan ke …" yang menyebut tahap tujuannya. Alasan wajib diisi. |
| Draf atau rancangan salah dibuat / ganda | Perangkat Daerah bisa **Buang Draf** selama belum diajukan. Rancangan yang sudah bernomor hanya bisa dihapus Admin lewat **Lainnya › Hapus Rancangan**; nomornya tidak dipakai lagi. |
| Peneliti yang ditugaskan perlu diganti | Verifikator: **Lainnya › Ganti Peneliti**. |
| Isi naskah perlu diubah padahal sudah dikunci (sesudah Validasi Fasilitasi) | Verifikator: halaman **Nota Dinas & Tanda Tangan › Isi naskah perlu diubah? › Kembalikan ke Validasi Fasilitasi**. Catatan paraf dan tanda tangan yang sudah dicatat ikut terhapus. |
| Nota dinas salah penanda tangan atau tujuan | Admin/Verifikator: halaman **Nota Dinas & Tanda Tangan › Perbaiki & Terbitkan Ulang**. Nomor dan tanggal tetap; versi lama disimpan sebagai "Diganti". |
| Tanggal paraf atau tanda tangan salah | Halaman **Nota Dinas & Tanda Tangan**: **Ubah Tanggal** atau **Batalkan** di tahapan itu. |
| Nomor atau berkas dokumentasi salah | Menu **Dokumentasi**, tombol **Ubah** di baris dokumen itu. |

Setiap perubahan tercatat di tab **Riwayat** pada layar kerja naskah.

---

## 4. Arsip dan dokumentasi

Menu: **Dokumentasi**.
- Tab **Menunggu Arsip** (Admin dan Verifikator) berisi naskah yang tanda tangannya sudah lengkap.
  Tekan **Arsipkan**, isi nomor, tanggal penetapan, Berita Daerah, dan unggah PDF bertanda tangan basah.
  Keputusan Bupati memakai nomor berformat `[140] / [nomor] / [412.1.6] / TAHUN [tahun]`; hanya nomor
  urut yang wajib diisi, bagian lain bisa diganti bila memakai kode lain.
- Tab **Terarsip** berisi semua dokumen. **Tambah Arsip › Dokumen lama** untuk produk hukum yang
  ditetapkan sebelum ada sistem.
- Dokumen yang sudah diarsipkan langsung tampil untuk umum di halaman JDIH (`/produk-hukum`).

---

## 5. Pemeriksaan rutin

| Kapan | Yang diperiksa |
|---|---|
| Setiap hari kerja | Dashboard › **Giliran Anda**. Naskah yang lama tertahan bisa ditanyakan ke pemegang gilirannya. |
| Bila muncul notifikasi *cadangan gagal* | Laporkan ke pengelola server hari itu juga. |
| Setiap bulan | Daftar Pengguna: akun pegawai yang sudah pindah tugas. |
| Setiap pergantian pejabat | Nama Bupati/Sekda, Kelola TTD, urutan tanda tangan, dan paraf hierarki di Format Dokumen. |

---

## 6. Perlu bantuan pengelola server

Hal di bawah ini tidak bisa dilakukan dari aplikasi. Panduannya ada di [DEPLOY.md](DEPLOY.md).

| Keadaan | Yang dilakukan pengelola server |
|---|---|
| **Semua Admin terkunci** (lupa kata sandi dan email tidak bisa dipakai) | `php artisan akun:super-admin <NIP>` mencetak kata sandi sementara untuk akun itu. Untuk membuat Super Admin baru: `php artisan akun:super-admin <NIP> --nama="..." --email=...` |
| Email aktivasi dan Lupa Password tidak pernah sampai | Periksa pengaturan `MAIL_*` di `.env`. Sementara itu Admin tetap bisa memakai kata sandi sementara. |
| Notifikasi cadangan gagal | Periksa `storage/logs/laravel.log`, lalu jalankan `php artisan backup:jalankan`. |
| Data terhapus atau rusak | Pulihkan dari cadangan; langkahnya di DEPLOY.md bagian Backup. |
| Aplikasi tidak bisa dibuka | Periksa layanan web server dan MySQL, lalu `storage/logs/laravel.log`. |
| Pembaruan keamanan (sekitar setahun sekali) | `composer update`, `npm update`, jalankan tes, lalu deploy seperti biasa. |
