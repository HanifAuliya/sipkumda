<?php

namespace App\Services;

use App\Enums\TahapNaskah as T;
use App\Models\DokumentasiProdukHukum;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahNotaDinas;
use App\Models\NaskahRiwayat;
use App\Models\NotaDinas;
use App\Models\TandaTangan;
use App\Models\User;
use App\Notifications\NaskahNotification;
use App\Support\TemplateNaskah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Seluruh aturan alur v2: siapa boleh berbuat apa pada tahap apa.
 * Tampilan hanya bertanya ke sini, sehingga aturan tidak tersebar di komponen.
 */
class AlurNaskah
{
    /** Pengubahan baris matriks yang tersedia bagi pemegang giliran. */
    public const MODE_SUSUN = 'susun';        // PD menyusun kolom 3
    public const MODE_TELITI = 'teliti';      // Peneliti mengisi kolom 4-5
    public const MODE_SESUAIKAN = 'sesuaikan'; // PD memperbaiki kolom 4
    public const MODE_KOREKSI = 'koreksi';    // Verifikator membetulkan langsung

    /**
     * Daftar perpindahan tahap.
     * dari, ke, label tombol, varian tombol, catatan wajib?, kepada siapa diberitahukan.
     */
    private const AKSI = [
        'ajukan' => ['dari' => T::Draf, 'ke' => T::PersetujuanBerkas, 'label' => 'Ajukan', 'ikon' => 'send', 'varian' => 'primary'],
        'setujui_berkas' => ['dari' => T::PersetujuanBerkas, 'ke' => T::PilihPeneliti, 'label' => 'Berkas lengkap', 'ikon' => 'check2', 'varian' => 'success'],
        'tolak_berkas' => ['dari' => T::PersetujuanBerkas, 'ke' => T::Draf, 'label' => 'Kembalikan ke PD', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true],
        'tugaskan_peneliti' => ['dari' => T::PilihPeneliti, 'ke' => T::Penelitian, 'label' => 'Tugaskan Peneliti', 'ikon' => 'person-check', 'varian' => 'primary', 'peneliti' => true],
        'kirim_ke_pd' => ['dari' => T::Penelitian, 'ke' => T::PerbaikanPd, 'label' => 'Kirim ke PD', 'ikon' => 'send', 'varian' => 'primary'],
        'teruskan_ke_verifikator' => ['dari' => T::Penelitian, 'ke' => T::ValidasiRancangan, 'label' => 'Selesai, teruskan ke Verifikator', 'ikon' => 'check2-all', 'varian' => 'success'],
        'kembali_ke_peneliti' => ['dari' => T::PerbaikanPd, 'ke' => T::Penelitian, 'label' => 'Kirim tindak lanjut', 'ikon' => 'send', 'varian' => 'primary'],
        'setujui_rancangan' => ['dari' => T::ValidasiRancangan, 'ke' => T::Disetujui, 'label' => 'Setuju', 'ikon' => 'check2', 'varian' => 'success'],
        'tolak_rancangan' => ['dari' => T::ValidasiRancangan, 'ke' => T::Penelitian, 'label' => 'Tidak setuju, kembali ke Peneliti', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true],
        'ajukan_fasilitasi' => ['dari' => T::Disetujui, 'ke' => T::PersetujuanFasilitasi, 'label' => 'Ajukan fasilitasi', 'ikon' => 'send', 'varian' => 'primary'],
        'setujui_fasilitasi' => ['dari' => T::PersetujuanFasilitasi, 'ke' => T::ValidasiFasilitasi, 'label' => 'Berkas fasilitasi lengkap', 'ikon' => 'check2', 'varian' => 'success'],
        'tolak_fasilitasi' => ['dari' => T::PersetujuanFasilitasi, 'ke' => T::Disetujui, 'label' => 'Kembalikan ke PD', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true],
        'finalkan' => ['dari' => T::ValidasiFasilitasi, 'ke' => T::Final, 'label' => 'Setuju', 'ikon' => 'patch-check', 'varian' => 'success'],
        'tolak_validasi' => ['dari' => T::ValidasiFasilitasi, 'ke' => T::PerbaikanFasilitasi, 'label' => 'Tidak setuju, kembali ke PD', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true],
        'perbaikan_ke_peneliti' => ['dari' => T::PerbaikanFasilitasi, 'ke' => T::CekPeneliti, 'label' => 'Kirim ke Peneliti', 'ikon' => 'send', 'varian' => 'primary'],
        'cek_ke_pd' => ['dari' => T::CekPeneliti, 'ke' => T::PerbaikanFasilitasi, 'label' => 'Kirim ke PD', 'ikon' => 'send', 'varian' => 'primary'],
        'cek_ke_verifikator' => ['dari' => T::CekPeneliti, 'ke' => T::ValidasiFasilitasi, 'label' => 'Selesai, teruskan ke Verifikator', 'ikon' => 'check2-all', 'varian' => 'success'],
        'buka_kunci' => ['dari' => T::Final, 'ke' => T::ValidasiFasilitasi, 'label' => 'Buka kunci', 'ikon' => 'unlock', 'varian' => 'outline-danger', 'catatan' => true, 'peran' => 'Verifikator'],
        'buka_kunci_ttd' => ['dari' => T::Penandatanganan, 'ke' => T::ValidasiFasilitasi, 'label' => 'Buka kunci', 'ikon' => 'unlock', 'varian' => 'outline-danger', 'catatan' => true, 'peran' => 'Verifikator'],
        // Tiga aksi ini punya isian sendiri, jadi dijalankan lewat metodenya masing-masing.
        'terbitkan_nota' => ['dari' => T::Final, 'ke' => T::Penandatanganan, 'label' => 'Terbitkan nota dinas', 'ikon' => 'file-earmark-text', 'varian' => 'primary', 'form' => 'nota'],
        'tandai_tahapan' => ['dari' => T::Penandatanganan, 'ke' => T::Arsip, 'label' => 'Tandai tahapan selesai', 'ikon' => 'check2-square', 'varian' => 'primary', 'form' => 'tahapan'],
        'arsipkan' => ['dari' => T::Arsip, 'ke' => T::Selesai, 'label' => 'Arsipkan ke JDIH', 'ikon' => 'archive', 'varian' => 'success', 'form' => 'arsip'],
    ];

    /** Urutan penandatanganan di luar sistem, dicatat satu per satu. */
    public const TAHAPAN_TTD = [
        'paraf_koordinasi' => 'Paraf Koordinasi',
        'asisten' => 'Asisten',
        'sekda' => 'Sekda',
        'bupati' => 'Bupati',
    ];

    /** Mode pengubahan baris bagi pengguna, atau null bila hanya boleh membaca. */
    public function modeBaris(Naskah $naskah, User $user): ?string
    {
        if (! $naskah->giliranMilik($user)) {
            return null;
        }

        return match ($naskah->tahap) {
            T::Draf => self::MODE_SUSUN,
            T::Penelitian, T::CekPeneliti => self::MODE_TELITI,
            T::PerbaikanPd, T::PerbaikanFasilitasi => self::MODE_SESUAIKAN,
            T::ValidasiFasilitasi => self::MODE_KOREKSI,
            default => null,
        };
    }

    /** Tombol perpindahan tahap yang tersedia dan alasan bila belum bisa dijalankan. */
    public function aksiTersedia(Naskah $naskah, User $user): array
    {
        $hasil = [];

        foreach (self::AKSI as $kunci => $aksi) {
            if ($aksi['dari'] !== $naskah->tahap || ! $this->pemegangAksi($naskah, $user, $aksi)) {
                continue;
            }
            if (in_array($kunci, $this->aksiTersembunyi($naskah), true)) {
                continue;
            }

            $hasil[$kunci] = $aksi + ['hambatan' => $this->hambatan($naskah, $kunci)];

            if ($kunci === 'tandai_tahapan') {
                $hasil[$kunci]['label'] = 'Tandai ' . self::TAHAPAN_TTD[$this->tahapanBerikutnya($naskah)] . ' selesai';
            }
        }

        return $hasil;
    }

    /** Kunci tahapan penandatanganan berikutnya yang belum selesai. */
    public function tahapanBerikutnya(Naskah $naskah): ?string
    {
        return array_key_first(array_diff_key(self::TAHAPAN_TTD, $naskah->tahapan_ttd ?? []));
    }

    /** Peneliti hanya melihat satu tombol lanjut, sesuai ada tidaknya baris untuk PD. */
    private function aksiTersembunyi(Naskah $naskah): array
    {
        $adaUntukPd = $naskah->baris()->where('status', B::PERLU_PD)->exists();

        return $adaUntukPd
            ? ['teruskan_ke_verifikator', 'cek_ke_verifikator']
            : ['kirim_ke_pd', 'cek_ke_pd'];
    }

    private function pemegangAksi(Naskah $naskah, User $user, array $aksi): bool
    {
        return isset($aksi['peran']) ? $user->hasRole($aksi['peran']) : $naskah->giliranMilik($user);
    }

    /** Mengunci naskah dan memastikan aksi ini masih berlaku pada tahap terkini. */
    private function kunciUntukAksi(Naskah $naskah, User $user, string $kunci): Naskah
    {
        $naskah = Naskah::whereKey($naskah->id)->lockForUpdate()->firstOrFail();
        $aksi = self::AKSI[$kunci];

        if ($naskah->tahap !== $aksi['dari'] || ! $this->pemegangAksi($naskah, $user, $aksi)) {
            abort(403, 'Aksi ini tidak tersedia pada tahap sekarang.');
        }

        return $naskah;
    }

    /**
     * Menerbitkan nota dinas. Nomornya berbagi urutan dengan nota dinas alur lama,
     * jadi selama keduanya berjalan tidak ada nomor ganda.
     */
    public function terbitkanNota(Naskah $naskah, User $user, ?string $nomorInputan, ?string $kepada, ?int $tandaTanganId): Naskah
    {
        if (! ctype_digit((string) $nomorInputan) || strlen((string) $nomorInputan) > 3) {
            $this->gagal('nomorInputan', 'Nomor nota dinas berupa angka, paling banyak 3 digit.');
        }
        if (blank($kepada)) {
            $this->gagal('kepada', 'Tujuan nota dinas wajib diisi.');
        }
        if (! $tandaTanganId || ! TandaTangan::where('status', 'Aktif')->whereKey($tandaTanganId)->exists()) {
            $this->gagal('tandaTanganId', 'Pilih tanda tangan.');
        }

        return Cache::lock('nomor-nota-dinas', 15)->block(10, fn () => DB::transaction(function () use ($naskah, $user, $nomorInputan, $kepada, $tandaTanganId) {
            $naskah = $this->kunciUntukAksi($naskah, $user, 'terbitkan_nota');
            $tahun = now()->year;

            if ($this->nomorNotaDipakai((int) $nomorInputan, $naskah->jenis, $tahun)) {
                $this->gagal('nomorInputan', "Nomor {$nomorInputan} sudah digunakan untuk {$naskah->jenis} tahun {$tahun}.");
            }

            $kode = $naskah->jenis === 'Peraturan Bupati' ? 'NDPB' : 'NDSK';

            $naskah->notaDinas()->create([
                'nomor_inputan' => $nomorInputan,
                'nomor_nota' => "180/{$nomorInputan}/{$kode}/KUM/{$tahun}",
                'tanggal_nota' => now(),
                'kepada' => $kepada,
                'tanda_tangan_id' => $tandaTanganId,
            ]);

            return $this->pindah($naskah, $user, T::Penandatanganan);
        }));
    }

    private function nomorNotaDipakai(int $nomor, string $jenis, int $tahun): bool
    {
        // Alur lama masih menyimpan Keputusan Bupati dengan nama "Surat Keputusan".
        $jenisLama = $jenis === 'Keputusan Bupati' ? 'Surat Keputusan' : $jenis;

        return NaskahNotaDinas::whereRaw('CAST(nomor_inputan AS UNSIGNED) = ?', [$nomor])
            ->whereYear('tanggal_nota', $tahun)
            ->whereHas('naskah', fn ($q) => $q->where('jenis', $jenis))
            ->exists()
            || NotaDinas::whereRaw('CAST(nomor_inputan AS UNSIGNED) = ?', [$nomor])
                ->whereYear('tanggal_nota', $tahun)
                ->whereHas('fasilitasi.rancangan', fn ($q) => $q->where('jenis_rancangan', $jenisLama))
                ->exists();
    }

    /** Mencatat satu tahapan tanda tangan, berurutan. Tahapan terakhir membawa naskah ke arsip. */
    public function tandaiTahapan(Naskah $naskah, User $user, ?string $tanggal = null): Naskah
    {
        $tanggal = filled($tanggal) ? $tanggal : now()->toDateString();

        if (! strtotime($tanggal) || $tanggal > now()->toDateString()) {
            $this->gagal('tanggalTahapan', 'Tanggal tidak boleh melebihi hari ini.');
        }

        return DB::transaction(function () use ($naskah, $user, $tanggal) {
            $naskah = $this->kunciUntukAksi($naskah, $user, 'tandai_tahapan');
            $tahapan = $this->tahapanBerikutnya($naskah);

            $naskah->tahapan_ttd = ($naskah->tahapan_ttd ?? []) + [$tahapan => $tanggal];

            if ($this->tahapanBerikutnya($naskah) === null) {
                return $this->pindah($naskah, $user, T::Arsip);
            }

            $naskah->save();
            $this->catat($naskah, $user, 'tahapan', null, null, self::TAHAPAN_TTD[$tahapan]);
            $this->beriTahuPemrakarsa($naskah, $user, 'Tahap ' . self::TAHAPAN_TTD[$tahapan] . ' telah selesai.');

            return $naskah;
        });
    }

    /** Naskah bertanda tangan basah masuk ke tabel arsip yang sama dengan JDIH. */
    public function arsipkan(Naskah $naskah, User $user, ?string $nomor, ?string $tanggalPenetapan, ?string $nomorBerita, ?string $tahunBerita, UploadedFile $file): Naskah
    {
        if (! ctype_digit((string) $nomor) || strlen((string) $nomor) > 3) {
            $this->gagal('nomorArsip', 'Nomor produk hukum berupa angka, paling banyak 3 digit.');
        }
        if (blank($tanggalPenetapan) || ! strtotime($tanggalPenetapan)) {
            $this->gagal('tanggalPenetapan', 'Tanggal penetapan wajib diisi.');
        }

        return DokumentasiProdukHukum::denganNomorTerkunci(function () use ($naskah, $user, $nomor, $tanggalPenetapan, $nomorBerita, $tahunBerita, $file) {
            $naskah = $this->kunciUntukAksi($naskah, $user, 'arsipkan');
            $tahun = (int) date('Y', strtotime($tanggalPenetapan));
            $perangkatDaerah = $naskah->pemrakarsa->perangkat_daerah_id;

            if (! $perangkatDaerah) {
                $this->gagal('aksi', 'Akun pemrakarsa belum terhubung ke perangkat daerah.');
            }
            if (DokumentasiProdukHukum::nomorDipakai($nomor, $tahun)) {
                $this->gagal('nomorArsip', "Nomor {$nomor} untuk tahun {$tahun} sudah ada.");
            }

            $namaFile = str_replace(' ', '_', $naskah->jenis) . "_Nomor_{$nomor}_Tahun_{$tahun}_Kabupaten_Hulu_Sungai_Tengah.pdf";

            $dokumentasi = DokumentasiProdukHukum::create([
                'nomor' => $nomor,
                'tahun' => $tahun,
                'tanggal_pengarsipan' => now(),
                'tanggal_penetapan' => $tanggalPenetapan,
                'nomor_tahun_berita' => ($nomorBerita ?: '-') . '/' . ($tahunBerita ?: '-'),
                'perangkat_daerah_id' => $perangkatDaerah,
                'jenis_dokumentasi' => $naskah->jenis,
                'tentang_dokumentasi' => $naskah->tentang,
                'file_produk_hukum' => $file->storeAs('dokumentasi/file_produk_hukum', $namaFile, 'local'),
            ]);

            $naskah->dokumentasi_id = $dokumentasi->id;

            return $this->pindah($naskah, $user, T::Selesai);
        });
    }

    /** Membuang draf yang tidak jadi diajukan beserta berkasnya. */
    public function buangDraf(Naskah $naskah, User $user): void
    {
        $slug = DB::transaction(function () use ($naskah, $user) {
            $naskah = Naskah::whereKey($naskah->id)->lockForUpdate()->firstOrFail();

            if ($naskah->tahap !== T::Draf || $naskah->pemrakarsa_id !== $user->id) {
                abort(403, 'Hanya draf milik Anda yang bisa dibuang.');
            }

            $slug = $naskah->slug;
            $naskah->delete();

            return $slug;
        });

        Storage::disk('local')->deleteDirectory("naskah/{$slug}");
    }

    /** Mencatat perpindahan tahap beserta salinan isi naskah saat itu. */
    private function pindah(Naskah $naskah, User $user, T $ke, ?string $catatan = null): Naskah
    {
        $dari = $naskah->tahap;

        $this->simpanVersi($naskah, $user);

        $naskah->tahap = $ke;
        $naskah->giliran++;
        $naskah->catatan_tahap = $catatan;
        $naskah->save();

        $this->catat($naskah, $user, 'tahap', $catatan, $dari->label(), $ke->label());
        $this->beriTahu($naskah, $user, $catatan);

        return $naskah;
    }

    /** Syarat yang belum terpenuhi, sebagai kalimat untuk pengguna. */
    private function hambatan(Naskah $naskah, string $aksi): ?string
    {
        return match ($aksi) {
            'ajukan' => $this->hambatanAjukan($naskah),
            'kirim_ke_pd', 'teruskan_ke_verifikator', 'cek_ke_pd', 'cek_ke_verifikator' => $this->hambatanTeliti($naskah),
            'kembali_ke_peneliti', 'perbaikan_ke_peneliti' => $this->hambatanSesuaikan($naskah),
            default => null,
        };
    }

    private function hambatanAjukan(Naskah $naskah): ?string
    {
        if (! $naskah->berkas()->where('jenis', 'nota_dinas_pd')->where('aktif', true)->exists()) {
            return 'Nota Dinas PD belum diunggah.';
        }

        $kosong = $naskah->baris()->whereNot('bagian', 'lampiran')
            ->where(fn ($q) => $q->whereNull('rumusan_awal')->orWhere('rumusan_awal', ''))
            ->count();

        return $kosong ? "{$kosong} baris masih kosong." : null;
    }

    private function hambatanTeliti(Naskah $naskah): ?string
    {
        $belum = $naskah->baris()->where('status', B::BELUM)->count();

        return $belum ? "{$belum} baris belum diperiksa." : null;
    }

    private function hambatanSesuaikan(Naskah $naskah): ?string
    {
        $belum = $naskah->baris()->where('status', B::PERLU_PD)->count();

        return $belum ? "{$belum} baris belum disesuaikan." : null;
    }

    /** Memindahkan naskah ke tahap berikutnya dan menyimpan versi. */
    public function jalankan(Naskah $naskah, User $user, string $kunci, ?string $catatan = null, ?int $penelitiId = null): Naskah
    {
        $aksi = self::AKSI[$kunci] ?? abort(404);
        abort_if(isset($aksi['form']), 400, 'Aksi ini memiliki isian sendiri.');

        DB::transaction(function () use (&$naskah, $user, $kunci, $aksi, $catatan, $penelitiId) {
            // Tahap dibaca ulang di dalam kunci: klik ganda atau tab lama tidak bisa memindahkan dua kali.
            $naskah = $this->kunciUntukAksi($naskah, $user, $kunci);

            if (in_array($kunci, $this->aksiTersembunyi($naskah), true)) {
                $this->gagal('aksi', 'Status baris sudah berubah. Muat ulang halaman.');
            }
            if ($hambatan = $this->hambatan($naskah, $kunci)) {
                $this->gagal('aksi', $hambatan);
            }
            if (! empty($aksi['catatan']) && blank($catatan)) {
                $this->gagal('catatan', 'Alasan wajib diisi.');
            }
            if (! empty($aksi['peneliti'])) {
                if (! $penelitiId || ! User::role('Peneliti')->whereKey($penelitiId)->exists()) {
                    $this->gagal('penelitiId', 'Pilih peneliti terlebih dahulu.');
                }
                $naskah->peneliti_id = $penelitiId;
            }

            $naskah->diajukan_at ??= $kunci === 'ajukan' ? now() : null;

            // Saat dibuka kembali, tanda tangan harus diulang karena isinya berubah.
            // Nota dinas yang sudah terbit tetap dipakai, jadi tidak perlu diterbitkan lagi.
            $ke = $aksi['ke'];

            if ($kunci === 'finalkan') {
                $naskah->terkunci_at = now();
                $ke = $naskah->notaDinas()->exists() ? T::Penandatanganan : T::Final;
            } elseif (str_starts_with($kunci, 'buka_kunci')) {
                $naskah->terkunci_at = null;
                $naskah->tahapan_ttd = null;
            }

            $naskah = $this->pindah($naskah, $user, $ke, $catatan);
        });

        return $naskah;
    }

    /** Menjalankan pengubahan baris di bawah kunci naskah, setelah giliran dan modenya dipastikan. */
    private function ubahBaris(B $baris, User $user, array $modeDiizinkan, callable $ubah): B
    {
        return DB::transaction(function () use ($baris, $user, $modeDiizinkan, $ubah) {
            $naskah = Naskah::whereKey($baris->naskah_id)->lockForUpdate()->firstOrFail();

            if (! in_array($this->modeBaris($naskah, $user), $modeDiizinkan, true)) {
                abort(403, 'Bukan giliran Anda untuk mengubah baris ini.');
            }

            $baris->refresh();
            $sebelum = $this->ringkasBaris($baris);

            $ubah($baris, $naskah);

            $baris->giliran_diubah = $naskah->giliran;
            $baris->diubah_oleh = $user->id;
            $baris->save();

            $this->catatBaris($naskah, $baris, $user, $sebelum);

            return $baris;
        });
    }

    /** Kolom 3, hanya saat penyusunan. Setelah diajukan kolom ini beku. */
    public function susun(B $baris, User $user, ?string $label, ?string $teks): B
    {
        return $this->ubahBaris($baris, $user, [self::MODE_SUSUN], function (B $b) use ($label, $teks) {
            $b->label = filled($label) ? trim($label) : null;
            $b->rumusan_awal = $teks;
        });
    }

    public function tetapkan(B $baris, User $user, ?string $keterangan = null): B
    {
        return $this->ubahBaris($baris, $user, [self::MODE_TELITI, self::MODE_KOREKSI], function (B $b) use ($keterangan) {
            $b->rumusan_penyempurnaan = null;
            $b->keterangan = $keterangan;
            $b->status = B::TETAP;
        });
    }

    /** Peneliti/Verifikator menulis rumusan. Teks yang sama persis dengan kolom 3 berarti Tetap. */
    public function perbaiki(B $baris, User $user, ?string $teks, ?string $keterangan = null, ?string $label = null): B
    {
        return $this->ubahBaris($baris, $user, [self::MODE_TELITI, self::MODE_KOREKSI], function (B $b) use ($teks, $keterangan, $label) {
            if ($b->sisipan()) {
                // Baris sisipan belum punya penanda; BAB atau Pasal-nya ditentukan di sini.
                if ($b->bagian === 'batang_tubuh' && filled($label)) {
                    $b->label = trim($label);
                }
                if (blank($teks) && blank($b->label)) {
                    $this->gagal('rumusan', 'Isi rumusannya, atau hapus baris ini bila tidak jadi dipakai.');
                }
            }

            $sama = $this->normal($teks) === $this->normal($b->rumusan_awal);

            $b->rumusan_penyempurnaan = $sama ? null : $teks;
            $b->keterangan = $keterangan;
            $b->status = $sama ? B::TETAP : B::DIUBAH;
        });
    }

    public function hapus(B $baris, User $user, ?string $keterangan = null): B
    {
        return $this->ubahBaris($baris, $user, [self::MODE_TELITI, self::MODE_KOREKSI], function (B $b) use ($keterangan) {
            // Judul, Penutup, dan Lampiran adalah bagian wajib naskah; isinya hanya bisa diubah.
            if (! in_array($b->bagian, B::BAGIAN_BERULANG, true)) {
                abort(403, 'Bagian ini wajib ada dan tidak bisa dihapus.');
            }

            $b->rumusan_penyempurnaan = null;
            $b->keterangan = $keterangan;
            $b->status = B::DIHAPUS;
        });
    }

    /** Peneliti cukup menulis arahan; kolom 4 boleh kosong dan PD yang merumuskan. */
    public function mintaPerbaikan(B $baris, User $user, ?string $arahan, ?string $teks = null): B
    {
        if (blank($arahan)) {
            $this->gagal('keterangan', 'Tulis arahan untuk PD di Keterangan.');
        }

        return $this->ubahBaris($baris, $user, [self::MODE_TELITI], function (B $b) use ($arahan, $teks) {
            $b->rumusan_penyempurnaan = filled($teks) ? $teks : $b->rumusan_penyempurnaan;
            $b->keterangan = $arahan;
            $b->status = B::PERLU_PD;
        });
    }

    /** PD memperbaiki baris mana pun pada gilirannya, dan perubahannya selalu ditinjau Peneliti lagi. */
    public function sesuaikan(B $baris, User $user, ?string $teks): B
    {
        return $this->ubahBaris($baris, $user, [self::MODE_SESUAIKAN], function (B $b) use ($teks) {
            $b->rumusan_penyempurnaan = $teks;
            $b->status = B::DISESUAIKAN;
        });
    }

    /** Menyisipkan baris baru setelah baris acuan. Di luar penyusunan, isinya masuk kolom 4. */
    public function tambahSetelah(B $acuan, User $user, ?string $label = null): B
    {
        return DB::transaction(function () use ($acuan, $user, $label) {
            $naskah = Naskah::whereKey($acuan->naskah_id)->lockForUpdate()->firstOrFail();
            $mode = $this->modeBaris($naskah, $user);

            if (! in_array($mode, [self::MODE_SUSUN, self::MODE_TELITI, self::MODE_SESUAIKAN], true)) {
                abort(403, 'Bukan giliran Anda untuk menambah baris.');
            }

            $urutan = B::whereKey($acuan->id)->value('urutan');
            B::where('naskah_id', $naskah->id)->where('urutan', '>', $urutan)->increment('urutan');

            $baru = B::create([
                'naskah_id' => $naskah->id,
                'urutan' => $urutan + 1,
                'bagian' => $acuan->bagian,
                'label' => filled($label) ? trim($label) : null,
                'status' => $mode === self::MODE_SUSUN ? B::BELUM : B::DIUBAH,
                'giliran_diubah' => $naskah->giliran,
                'diubah_oleh' => $user->id,
            ]);

            $this->catat($naskah, $user, 'tambah_baris', null, null, $baru->label, $baru->id);

            return $baru;
        });
    }

    /** Hapus sungguhan hanya saat penyusunan; sesudahnya baris ditandai Dihapus lewat hapus(). */
    public function buangSaatMenyusun(B $baris, User $user): void
    {
        DB::transaction(function () use ($baris, $user) {
            $naskah = Naskah::whereKey($baris->naskah_id)->lockForUpdate()->firstOrFail();

            if ($this->modeBaris($naskah, $user) !== self::MODE_SUSUN) {
                abort(403, 'Baris hanya bisa dibuang saat penyusunan.');
            }

            $baris->delete();
        });
    }

    /** Peneliti membuang baris sisipan sungguhan. Baris usulan PD tetap lewat hapus() supaya jejaknya ada di matriks. */
    public function buangSisipan(B $baris, User $user): void
    {
        DB::transaction(function () use ($baris, $user) {
            $naskah = Naskah::whereKey($baris->naskah_id)->lockForUpdate()->firstOrFail();

            if ($this->modeBaris($naskah, $user) !== self::MODE_TELITI) {
                abort(403, 'Bukan giliran Anda untuk membuang baris.');
            }

            $baris->refresh();

            if (! $baris->sisipan()) {
                abort(403, 'Baris usulan Perangkat Daerah tidak bisa dibuang.');
            }

            if (B::where('naskah_id', $naskah->id)->where('bagian', $baris->bagian)->count() <= 1) {
                $this->gagal('rumusan', 'Ini baris terakhir pada bagian ini, jadi tidak bisa dihapus.');
            }

            $this->catat($naskah, $user, 'buang_baris', null, $this->ringkasBaris($baris), $baris->label ?: TemplateNaskah::BAGIAN[$baris->bagian]);
            $baris->delete();
        });
    }

    /** Menggeser baris saat penyusunan: arah -1 naik, +1 turun, hanya di dalam bagian yang sama. */
    public function geser(B $baris, User $user, int $arah): void
    {
        DB::transaction(function () use ($baris, $user, $arah) {
            $naskah = Naskah::whereKey($baris->naskah_id)->lockForUpdate()->firstOrFail();

            if ($this->modeBaris($naskah, $user) !== self::MODE_SUSUN) {
                abort(403, 'Baris hanya bisa digeser saat penyusunan.');
            }

            $baris->refresh();
            $tetangga = B::where('naskah_id', $naskah->id)
                ->where('bagian', $baris->bagian)
                ->where('urutan', $arah < 0 ? '<' : '>', $baris->urutan)
                ->orderBy('urutan', $arah < 0 ? 'desc' : 'asc')
                ->first();

            if ($tetangga) {
                [$baris->urutan, $tetangga->urutan] = [$tetangga->urutan, $baris->urutan];
                $baris->save();
                $tetangga->save();
            }
        });
    }

    /** @param array<int, array{bagian: string, label: ?string, teks: ?string}> $baris */
    public function isiBaris(Naskah $naskah, array $baris): void
    {
        foreach (array_values($baris) as $i => $item) {
            B::create([
                'naskah_id' => $naskah->id,
                'urutan' => $i + 1,
                'bagian' => $item['bagian'],
                'label' => $item['label'] ?? null,
                'rumusan_awal' => $item['teks'] ?? null,
            ]);
        }
    }

    private function simpanVersi(Naskah $naskah, User $user): void
    {
        $naskah->versi()->create([
            'versi' => (int) $naskah->versi()->max('versi') + 1,
            'tahap' => $naskah->tahap->value,
            'user_id' => $user->id,
            'isi' => $naskah->baris()->get(['id', 'bagian', 'label', 'rumusan_awal', 'rumusan_penyempurnaan', 'keterangan', 'status'])->toArray(),
        ]);
    }

    private function ringkasBaris(B $baris): string
    {
        return json_encode([
            'label' => $baris->label,
            'awal' => $baris->rumusan_awal,
            'penyempurnaan' => $baris->rumusan_penyempurnaan,
            'keterangan' => $baris->keterangan,
            'status' => $baris->status,
        ], JSON_UNESCAPED_UNICODE);
    }

    /** Simpan otomatis memicu banyak perubahan: yang berurutan dari orang yang sama digabung jadi satu catatan. */
    private function catatBaris(Naskah $naskah, B $baris, User $user, string $sebelum): void
    {
        $sesudah = $this->ringkasBaris($baris);
        if ($sesudah === $sebelum) {
            return;
        }

        $terakhir = NaskahRiwayat::where('naskah_id', $naskah->id)->latest('id')->first();

        if ($terakhir && $terakhir->aksi === 'ubah_baris' && $terakhir->baris_id === $baris->id
            && $terakhir->user_id === $user->id && $terakhir->created_at->gt(now()->subMinutes(30))) {
            $terakhir->update(['sesudah' => $sesudah]);

            return;
        }

        $this->catat($naskah, $user, 'ubah_baris', null, $sebelum, $sesudah, $baris->id);
    }

    private function catat(Naskah $naskah, User $user, string $aksi, ?string $catatan, ?string $sebelum, ?string $sesudah, ?int $barisId = null): void
    {
        NaskahRiwayat::create([
            'naskah_id' => $naskah->id,
            'baris_id' => $barisId,
            'user_id' => $user->id,
            'aksi' => $aksi,
            'catatan' => $catatan,
            'sebelum' => $sebelum,
            'sesudah' => $sesudah,
        ]);
    }

    private function beriTahuPemrakarsa(Naskah $naskah, User $pelaku, string $pesan): void
    {
        if ($naskah->pemrakarsa_id !== $pelaku->id) {
            User::find($naskah->pemrakarsa_id)?->notify(new NaskahNotification([
                'title' => "{$naskah->nomor}: {$naskah->tahap->label()}",
                'message' => "Naskah \"{$naskah->tentang}\": {$pesan}",
                'slug' => $naskah->slug,
            ]));
        }
    }

    private function beriTahu(Naskah $naskah, User $pelaku, ?string $catatan): void
    {
        $tahap = $naskah->tahap;

        $penerima = match ($tahap->peran()) {
            'Perangkat Daerah' => User::whereKey($naskah->pemrakarsa_id)->get(),
            'Peneliti' => User::whereKey($naskah->peneliti_id)->get(),
            // Penandatanganan juga dicatat oleh Tamu, jadi Tamu ikut diberi tahu.
            default => User::role($tahap === T::Penandatanganan ? $tahap->pemegang() : $tahap->peran())->get(),
        };

        // Pemrakarsa selalu tahu posisi naskahnya, termasuk koreksi oleh Verifikator.
        if ($tahap->peran() !== 'Perangkat Daerah') {
            $penerima = $penerima->push(User::find($naskah->pemrakarsa_id));
        }

        $penerima = $penerima->filter()->unique('id')->reject(fn ($u) => $u->id === $pelaku->id);

        Notification::send($penerima, new NaskahNotification([
            'title' => "{$naskah->nomor}: {$tahap->label()}",
            'message' => trim("Naskah \"{$naskah->tentang}\" kini pada tahap {$tahap->label()}. {$catatan}"),
            'slug' => $naskah->slug,
        ]));
    }

    private function normal(?string $teks): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $teks));
    }

    private function gagal(string $kunci, string $pesan): never
    {
        throw ValidationException::withMessages([$kunci => $pesan]);
    }
}
