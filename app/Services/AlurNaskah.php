<?php

namespace App\Services;

use App\Enums\TahapNaskah as T;
use App\Models\DokumentasiProdukHukum;
use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use App\Models\NaskahBerkas;
use App\Models\NaskahNotaDinas;
use App\Models\NaskahRiwayat;
use App\Models\NotaDinas;
use App\Models\TandaTangan;
use App\Models\User;
use App\Notifications\NaskahNotification;
use App\Support\FormatDokumen;
use App\Support\PesanNotifikasi;
use App\Support\TemplateNaskah;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
     * mundur: naskah kembali ke tahap sebelumnya; labelnya selalu menyebut tahap tujuan.
     */
    private const AKSI = [
        'ajukan' => ['dari' => T::Draf, 'ke' => T::PersetujuanBerkas, 'label' => 'Ajukan', 'ikon' => 'send', 'varian' => 'primary'],
        'setujui_berkas' => ['dari' => T::PersetujuanBerkas, 'ke' => T::PilihPeneliti, 'label' => 'Berkas lengkap', 'ikon' => 'check2', 'varian' => 'success'],
        'tolak_berkas' => ['dari' => T::PersetujuanBerkas, 'ke' => T::Draf, 'label' => 'Kembalikan ke Penyusunan (PD)', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true, 'mundur' => true],
        'tugaskan_peneliti' => ['dari' => T::PilihPeneliti, 'ke' => T::Penelitian, 'label' => 'Tugaskan Peneliti', 'ikon' => 'person-check', 'varian' => 'primary', 'peneliti' => true],
        'kirim_ke_pd' => ['dari' => T::Penelitian, 'ke' => T::PerbaikanPd, 'label' => 'Kirim ke PD', 'ikon' => 'send', 'varian' => 'primary'],
        'teruskan_ke_verifikator' => ['dari' => T::Penelitian, 'ke' => T::ValidasiRancangan, 'label' => 'Selesai, teruskan ke Verifikator', 'ikon' => 'check2-all', 'varian' => 'success'],
        'kembali_ke_peneliti' => ['dari' => T::PerbaikanPd, 'ke' => T::Penelitian, 'label' => 'Kirim tindak lanjut', 'ikon' => 'send', 'varian' => 'primary'],
        'setujui_rancangan' => ['dari' => T::ValidasiRancangan, 'ke' => T::Disetujui, 'label' => 'Setuju', 'ikon' => 'check2', 'varian' => 'success'],
        'tolak_rancangan' => ['dari' => T::ValidasiRancangan, 'ke' => T::Penelitian, 'label' => 'Kembalikan ke Penelitian (Peneliti)', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true, 'mundur' => true],
        'ajukan_fasilitasi' => ['dari' => T::Disetujui, 'ke' => T::PersetujuanFasilitasi, 'label' => 'Ajukan fasilitasi', 'ikon' => 'send', 'varian' => 'primary'],
        'setujui_fasilitasi' => ['dari' => T::PersetujuanFasilitasi, 'ke' => T::ValidasiFasilitasi, 'label' => 'Berkas fasilitasi lengkap', 'ikon' => 'check2', 'varian' => 'success'],
        'tolak_fasilitasi' => ['dari' => T::PersetujuanFasilitasi, 'ke' => T::Disetujui, 'label' => 'Kembalikan berkas fasilitasi ke PD', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true, 'mundur' => true],
        'finalkan' => ['dari' => T::ValidasiFasilitasi, 'ke' => T::Final, 'label' => 'Setuju', 'ikon' => 'patch-check', 'varian' => 'success'],
        'tolak_validasi' => ['dari' => T::ValidasiFasilitasi, 'ke' => T::PerbaikanFasilitasi, 'label' => 'Kembalikan ke Perbaikan Fasilitasi (PD)', 'ikon' => 'arrow-return-left', 'varian' => 'outline-danger', 'catatan' => true, 'mundur' => true],
        'perbaikan_ke_peneliti' => ['dari' => T::PerbaikanFasilitasi, 'ke' => T::CekPeneliti, 'label' => 'Kirim ke Peneliti', 'ikon' => 'send', 'varian' => 'primary'],
        'cek_ke_pd' => ['dari' => T::CekPeneliti, 'ke' => T::PerbaikanFasilitasi, 'label' => 'Kirim ke PD', 'ikon' => 'send', 'varian' => 'primary'],
        'cek_ke_verifikator' => ['dari' => T::CekPeneliti, 'ke' => T::ValidasiFasilitasi, 'label' => 'Selesai, teruskan ke Verifikator', 'ikon' => 'check2-all', 'varian' => 'success'],
        // Buka kunci: isi naskah bisa diubah lagi. Tombolnya ada di halaman Nota Dinas & Tanda Tangan.
        'buka_kunci' => ['dari' => T::Final, 'ke' => T::ValidasiFasilitasi, 'label' => 'Kembalikan ke Validasi Fasilitasi', 'ikon' => 'unlock', 'varian' => 'outline-danger', 'catatan' => true, 'peran' => 'Verifikator', 'mundur' => true],
        'buka_kunci_ttd' => ['dari' => T::Penandatanganan, 'ke' => T::ValidasiFasilitasi, 'label' => 'Kembalikan ke Validasi Fasilitasi', 'ikon' => 'unlock', 'varian' => 'outline-danger', 'catatan' => true, 'peran' => 'Verifikator', 'mundur' => true],
        // Tiga aksi ini punya isian sendiri, jadi dijalankan lewat metodenya masing-masing.
        'terbitkan_nota' => ['dari' => T::Final, 'ke' => T::Penandatanganan, 'label' => 'Terbitkan nota dinas', 'ikon' => 'file-earmark-text', 'varian' => 'primary', 'form' => 'nota'],
        'tandai_tahapan' => ['dari' => T::Penandatanganan, 'ke' => T::Arsip, 'label' => 'Tandai tahapan selesai', 'ikon' => 'check2-square', 'varian' => 'primary', 'form' => 'tahapan'],
        'arsipkan' => ['dari' => T::Arsip, 'ke' => T::Selesai, 'label' => 'Arsipkan ke JDIH', 'ikon' => 'archive', 'varian' => 'success', 'form' => 'arsip'],
    ];

    /** Yang menerbitkan nota dinas juga boleh membetulkan dan menerbitkannya ulang. */
    private const PENERBIT_ULANG_NOTA = ['Admin', 'Verifikator', 'Super Admin'];

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

        // Bukan perpindahan tahap: naskah tetap di tahapnya, hanya penelitinya yang berganti.
        if ($this->bolehGantiPeneliti($naskah, $user)) {
            $hasil['ganti_peneliti'] = [
                'label' => 'Ganti Peneliti', 'ikon' => 'person-gear', 'varian' => 'outline',
                'peneliti' => true, 'hambatan' => null,
            ];
        }

        return $hasil;
    }

    /** Verifikator bisa mengganti peneliti selama penelitian dan fasilitasi, sebelum isi naskah dikunci. */
    public function bolehGantiPeneliti(Naskah $naskah, User $user): bool
    {
        return $user->hasRole('Verifikator')
            && $naskah->peneliti_id !== null
            && ! $naskah->tahap->terkunci()
            && ! in_array($naskah->tahap, [T::Draf, T::PersetujuanBerkas, T::PilihPeneliti], true);
    }

    public function gantiPeneliti(Naskah $naskah, User $user, ?int $penelitiId, ?string $catatan = null): Naskah
    {
        return DB::transaction(function () use ($naskah, $user, $penelitiId, $catatan) {
            $naskah = Naskah::whereKey($naskah->id)->lockForUpdate()->firstOrFail();
            abort_unless($this->bolehGantiPeneliti($naskah, $user), 403, 'Peneliti tidak bisa diganti pada tahap ini.');

            $baru = $penelitiId ? User::role('Peneliti')->find($penelitiId) : null;

            if (! $baru) {
                $this->gagal('penelitiId', 'Pilih peneliti terlebih dahulu.');
            }
            if ($baru->id === $naskah->peneliti_id) {
                $this->gagal('penelitiId', 'Peneliti ini yang sedang ditugaskan. Pilih peneliti lain.');
            }

            $lama = User::find($naskah->peneliti_id);
            $naskah->peneliti_id = $baru->id;
            $naskah->save();

            $this->catat($naskah, $user, 'ganti_peneliti', filled($catatan) ? $catatan : null, $lama?->nama_user, $baru->nama_user);

            // Tugas yang belum dibuka peneliti lama tidak berlaku lagi, karena ia tidak bisa membuka naskahnya.
            if ($lama) {
                $naskah->notifikasi()->where('notifiable_id', $lama->id)->whereNull('read_at')->update(['read_at' => now()]);
                $lama->notify(new NaskahNotification(PesanNotifikasi::penelitiDiganti($naskah, $user, $catatan, baru: false)));
            }
            $baru->notify(new NaskahNotification(PesanNotifikasi::penelitiDiganti($naskah, $user, $catatan, baru: true)));

            return $naskah;
        });
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

        $naskah = Cache::lock('nomor-nota-dinas', 15)->block(10, fn () => DB::transaction(function () use ($naskah, $user, $nomorInputan, $kepada, $tandaTanganId) {
            $naskah = $this->kunciUntukAksi($naskah, $user, 'terbitkan_nota');
            $tahun = now()->year;
            $pola = FormatDokumen::nota()['nomor'];
            // Pola dengan {kode} (NDPB/NDSK) memisahkan urutan per jenis; tanpa itu satu urutan untuk semua.
            $perJenis = str_contains($pola, '{kode}');

            if ($this->nomorNotaDipakai((int) $nomorInputan, $naskah->jenis, $tahun, $perJenis)) {
                $this->gagal('nomorInputan', $perJenis
                    ? "Nomor {$nomorInputan} sudah digunakan untuk {$naskah->jenis} tahun {$tahun}."
                    : "Nomor {$nomorInputan} sudah digunakan pada nota dinas tahun {$tahun}.");
            }

            $naskah->notaDinas()->create([
                'nomor_inputan' => $nomorInputan,
                'nomor_nota' => FormatDokumen::nomorNota($pola, $nomorInputan, $naskah->jenis, $tahun),
                'tanggal_nota' => now(),
                'kepada' => $kepada,
                'tanda_tangan_id' => $tandaTanganId,
            ]);

            return $this->pindah($naskah, $user, T::Penandatanganan);
        }));

        // Dibekukan di luar kunci nomor supaya penerbit lain tidak ikut menunggu pembuatan PDF.
        // Bila gagal, nota dinas tetap terbit dan unduhannya dibuat langsung dari pengaturan.
        try {
            $this->simpanPdfNota($naskah, $user, null);
        } catch (\Throwable $e) {
            report($e);
        }

        return $naskah;
    }

    /**
     * Admin menerbitkan ulang nota dinas dari pengaturan Format Dokumen terbaru, misalnya karena kop
     * atau pejabat berganti. Nomor dan tanggalnya tetap; versi sebelumnya disimpan sebagai "diganti".
     * Penandatangan dan tujuan yang salah dipilih saat terbit bisa sekaligus dibetulkan di sini;
     * perubahannya ikut tercatat di keterangan versi dan riwayat.
     */
    public function terbitUlangNota(Naskah $naskah, User $user, ?string $alasan, ?int $tandaTanganId = null, ?string $kepada = null): NaskahBerkas
    {
        if (! $user->hasAnyRole(self::PENERBIT_ULANG_NOTA)) {
            abort(403, 'Hanya Admin atau Verifikator yang bisa menerbitkan ulang nota dinas.');
        }
        if (blank($alasan)) {
            $this->gagal('alasanTerbitUlang', 'Tulis alasan penerbitan ulang.');
        }

        $ttdBaru = $tandaTanganId ? TandaTangan::where('status', 'Aktif')->find($tandaTanganId) : null;

        if ($tandaTanganId && ! $ttdBaru) {
            $this->gagal('ttdUlangId', 'Pilih tanda tangan yang aktif.');
        }

        return DB::transaction(function () use ($naskah, $user, $alasan, $ttdBaru, $kepada) {
            $naskah = Naskah::whereKey($naskah->id)->lockForUpdate()->firstOrFail();

            if (! $this->bolehTerbitUlangNota($naskah, $user)) {
                abort(403, 'Nota dinas tidak bisa diterbitkan ulang pada tahap ini.');
            }

            $nota = $naskah->notaDinas()->with('tandaTangan')->firstOrFail();
            $perubahan = [];

            if ($ttdBaru && $ttdBaru->id !== $nota->tanda_tangan_id) {
                $perubahan[] = 'penandatangan: ' . ($nota->tandaTangan->nama_ttd ?? '-') . ' → ' . $ttdBaru->nama_ttd;
                $nota->tanda_tangan_id = $ttdBaru->id;
            }
            if (filled($kepada) && trim($kepada) !== $nota->kepada) {
                $perubahan[] = "kepada: {$nota->kepada} → " . trim($kepada);
                $nota->kepada = trim($kepada);
            }
            $nota->save();

            $keterangan = Str::limit(trim($alasan) . ($perubahan ? ' (' . implode('; ', $perubahan) . ')' : ''), 497);
            $berkas = $this->simpanPdfNota($naskah, $user, $keterangan);
            $this->catat($naskah, $user, 'terbit_ulang_nota', $keterangan, null, $berkas->nama_asli);

            return $berkas;
        });
    }

    public function bolehTerbitUlangNota(Naskah $naskah, User $user): bool
    {
        return $user->hasAnyRole(self::PENERBIT_ULANG_NOTA)
            && $naskah->tahap !== T::Selesai
            && $naskah->notaDinas()->exists();
    }

    /** Membuat PDF nota dinas dari pengaturan sekarang dan menyimpannya sebagai versi yang berlaku. */
    private function simpanPdfNota(Naskah $naskah, User $user, ?string $alasan): NaskahBerkas
    {
        $isi = app(CetakNaskah::class)->pdfNotaDinasTerkini($naskah);
        $versi = $naskah->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS)->count() + 1;
        $path = "naskah/{$naskah->slug}/nota-dinas-v{$versi}-" . Str::random(8) . '.pdf';

        Storage::disk('local')->put($path, $isi);
        $naskah->berkas()->where('jenis', NaskahBerkas::NOTA_DINAS)->update(['aktif' => false]);

        return $naskah->berkas()->create([
            'jenis' => NaskahBerkas::NOTA_DINAS,
            'path' => $path,
            'nama_asli' => "Nota Dinas {$naskah->nomor_tampil} versi {$versi}.pdf",
            'aktif' => true,
            'keterangan' => $alasan,
            'diunggah_oleh' => $user->id,
        ]);
    }

    private function nomorNotaDipakai(int $nomor, string $jenis, int $tahun, bool $perJenis = true): bool
    {
        // Alur lama masih menyimpan Keputusan Bupati dengan nama "Surat Keputusan".
        $jenisLama = $jenis === 'Keputusan Bupati' ? 'Surat Keputusan' : $jenis;

        return NaskahNotaDinas::whereRaw('CAST(nomor_inputan AS UNSIGNED) = ?', [$nomor])
            ->whereYear('tanggal_nota', $tahun)
            ->when($perJenis, fn ($q) => $q->whereHas('naskah', fn ($q) => $q->where('jenis', $jenis)))
            ->exists()
            || NotaDinas::whereRaw('CAST(nomor_inputan AS UNSIGNED) = ?', [$nomor])
                ->whereYear('tanggal_nota', $tahun)
                ->when($perJenis, fn ($q) => $q->whereHas('fasilitasi.rancangan', fn ($q) => $q->where('jenis_rancangan', $jenisLama)))
                ->exists();
    }

    /**
     * Mencatat satu tahapan tanda tangan. Tanpa $kunci, tahapan berikutnya menurut urutan.
     * Urutan boleh dilompati karena di lapangan kadang begitu, tetapi dicatat di riwayat.
     * Tahapan terakhir yang lengkap membawa naskah ke arsip.
     */
    public function tandaiTahapan(Naskah $naskah, User $user, ?string $tanggal = null, ?string $kunci = null): Naskah
    {
        $tanggal = $this->tanggalTahapan($tanggal);

        return DB::transaction(function () use ($naskah, $user, $tanggal, $kunci) {
            $naskah = $this->kunciUntukAksi($naskah, $user, 'tandai_tahapan');
            $berikutnya = $this->tahapanBerikutnya($naskah);
            $tahapan = $kunci ?? $berikutnya;

            abort_unless(isset(self::TAHAPAN_TTD[$tahapan]), 404);

            if (isset($naskah->tahapan_ttd[$tahapan])) {
                $this->gagal('tahapan', self::TAHAPAN_TTD[$tahapan] . ' sudah ditandai. Ubah tanggalnya bila perlu.');
            }

            $naskah->tahapan_ttd = $this->urutkanTahapan(($naskah->tahapan_ttd ?? []) + [$tahapan => $tanggal]);
            $catatan = $tahapan !== $berikutnya ? 'Tidak berurutan: ' . self::TAHAPAN_TTD[$berikutnya] . ' belum ditandai.' : null;

            $this->catat($naskah, $user, 'tahapan', $catatan, null, self::TAHAPAN_TTD[$tahapan]);

            if ($this->tahapanBerikutnya($naskah) === null) {
                return $this->pindah($naskah, $user, T::Arsip);
            }

            $naskah->save();
            $this->beriTahuPemrakarsa($naskah, $user, self::TAHAPAN_TTD[$tahapan]);

            return $naskah;
        });
    }

    /** Admin dan Tamu bisa membetulkan tahapan tanda tangan sampai naskah diarsipkan ke JDIH. */
    public function bolehKelolaTtd(Naskah $naskah, User $user): bool
    {
        return in_array($naskah->tahap, [T::Penandatanganan, T::Arsip], true) && $user->hasAnyRole(['Admin', 'Tamu']);
    }

    /** Membetulkan tanggal tahapan yang salah dipilih. */
    public function ubahTanggalTahapan(Naskah $naskah, User $user, string $kunci, ?string $tanggal): Naskah
    {
        $tanggal = $this->tanggalTahapan($tanggal);

        return DB::transaction(function () use ($naskah, $user, $kunci, $tanggal) {
            $naskah = $this->kunciTahapan($naskah, $user, $kunci);
            $lama = $naskah->tahapan_ttd[$kunci];

            if ($lama !== $tanggal) {
                $naskah->tahapan_ttd = [...$naskah->tahapan_ttd, $kunci => $tanggal];
                $naskah->save();
                $this->catat($naskah, $user, 'tahapan_tanggal', self::TAHAPAN_TTD[$kunci], $lama, $tanggal);
            }

            return $naskah;
        });
    }

    /** Menghapus tanda yang salah. Bila naskah sudah menunggu arsip, ia kembali ke penandatanganan. */
    public function batalkanTahapan(Naskah $naskah, User $user, string $kunci): Naskah
    {
        return DB::transaction(function () use ($naskah, $user, $kunci) {
            $naskah = $this->kunciTahapan($naskah, $user, $kunci);
            $nama = self::TAHAPAN_TTD[$kunci];

            $naskah->tahapan_ttd = array_diff_key($naskah->tahapan_ttd, [$kunci => true]) ?: null;
            $this->catat($naskah, $user, 'tahapan_batal', null, $nama, null);

            if ($naskah->tahap === T::Arsip) {
                return $this->pindah($naskah, $user, T::Penandatanganan, "Tanda {$nama} dibatalkan.");
            }

            $naskah->save();

            return $naskah;
        });
    }

    private function kunciTahapan(Naskah $naskah, User $user, string $kunci): Naskah
    {
        abort_unless(isset(self::TAHAPAN_TTD[$kunci]), 404);
        $naskah = Naskah::whereKey($naskah->id)->lockForUpdate()->firstOrFail();
        abort_unless($this->bolehKelolaTtd($naskah, $user), 403, 'Tahapan tanda tangan tidak bisa diubah pada tahap ini.');

        if (! isset($naskah->tahapan_ttd[$kunci])) {
            $this->gagal('tahapan', self::TAHAPAN_TTD[$kunci] . ' belum ditandai.');
        }

        return $naskah;
    }

    private function tanggalTahapan(?string $tanggal): string
    {
        $tanggal = filled($tanggal) ? $tanggal : now()->toDateString();

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || ! strtotime($tanggal) || $tanggal > now()->toDateString()) {
            $this->gagal('tanggalTahapan', 'Tanggal tidak boleh melebihi hari ini.');
        }

        return $tanggal;
    }

    /** Disimpan menurut urutan resmi, apa pun urutan penandaannya. */
    private function urutkanTahapan(array $tahapan): array
    {
        return array_intersect_key(array_replace(self::TAHAPAN_TTD, $tahapan), $tahapan);
    }

    /** Naskah bertanda tangan basah masuk ke tabel arsip yang sama dengan JDIH. */
    /**
     * $polaNomor: pola nomor lengkap Keputusan Bupati dari formulir arsip (bagian selain nomor urut bisa diganti
     * per naskah). Tanpa itu dipakai pola di menu Format Dokumen. Peraturan Bupati tidak berpola.
     */
    public function arsipkan(Naskah $naskah, User $user, ?string $nomor, ?string $tanggalPenetapan, ?string $nomorBerita, ?string $tahunBerita, UploadedFile $file, ?string $polaNomor = null): Naskah
    {
        if (! ctype_digit((string) $nomor) || strlen((string) $nomor) > 3) {
            $this->gagal('nomorArsip', 'Nomor produk hukum berupa angka, paling banyak 3 digit.');
        }
        if (blank($tanggalPenetapan) || ! strtotime($tanggalPenetapan)) {
            $this->gagal('tanggalPenetapan', 'Tanggal penetapan wajib diisi.');
        }

        $polaNomor = $naskah->jenis === FormatDokumen::KEPBUP
            ? trim($polaNomor ?? FormatDokumen::naskah(FormatDokumen::KEPBUP)['nomor_arsip'])
            : '';

        if ($polaNomor !== '' && (! str_contains($polaNomor, '{nomor}') || mb_strlen($polaNomor) > 100)) {
            $this->gagal('nomorArsip', 'Susunan nomor keputusan tidak sah.');
        }

        return DokumentasiProdukHukum::denganNomorTerkunci(function () use ($naskah, $user, $nomor, $tanggalPenetapan, $nomorBerita, $tahunBerita, $file, $polaNomor) {
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
                'pola_nomor' => $polaNomor !== '' ? $polaNomor : null,
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

    /**
     * Perangkat Daerah boleh membuang drafnya sendiri selama belum pernah diajukan (belum bernomor).
     * Rancangan yang sudah bernomor hanya bisa dihapus Admin, supaya nomor yang sudah tercatat di nota
     * dinas dan notifikasi tidak hilang tanpa jejak. Penghapusannya dicatat di log aplikasi.
     */
    public function bolehBuang(Naskah $naskah, User $user): bool
    {
        if ($naskah->nomor !== null) {
            return $user->hasAnyRole(['Admin', 'Super Admin']) && $naskah->tahap !== T::Selesai;
        }

        return $naskah->tahap === T::Draf && $naskah->pemrakarsa_id === $user->id;
    }

    /** Membuang draf (atau rancangan bernomor, oleh Admin) beserta berkasnya. */
    public function buangDraf(Naskah $naskah, User $user): void
    {
        $slug = DB::transaction(function () use ($naskah, $user) {
            $naskah = Naskah::whereKey($naskah->id)->lockForUpdate()->firstOrFail();

            if (! $this->bolehBuang($naskah, $user)) {
                abort(403, $naskah->nomor !== null
                    ? 'Rancangan yang sudah diajukan dan bernomor hanya bisa dihapus Admin.'
                    : 'Hanya draf milik Anda yang bisa dibuang.');
            }

            if ($naskah->nomor !== null) {
                Log::notice('Rancangan bernomor dihapus Admin.', [
                    'nomor' => $naskah->nomor,
                    'tentang' => $naskah->tentang,
                    'tahap' => $naskah->tahap->value,
                    'oleh' => $user->id,
                ]);
            }

            $slug = $naskah->slug;
            // Notifikasinya ikut dihapus, karena sudah tidak ada naskah yang bisa dibuka.
            $naskah->notifikasi()->delete();
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
        $this->beriTahu($naskah, $dari, $user, $catatan);

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

        $asal = $naskah;

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

            // Nomor rancangan diberikan saat pertama kali diajukan; diajukan ulang tetap memakai nomor yang sama.
            if ($kunci === 'ajukan') {
                $naskah->beriNomor();
            }

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

        // Objek milik pemanggil (misalnya komponen Livewire) ikut tahu nomor yang baru diberikan.
        if ($asal !== $naskah && $asal->nomor === null && $naskah->nomor !== null) {
            $asal->forceFill(['nomor' => $naskah->nomor])->syncOriginalAttribute('nomor');
        }

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
            // Lampiran tambahan (Lampiran II, dst.) boleh dihapus selama masih ada lampiran lain.
            if (! in_array($b->bagian, B::BAGIAN_BERULANG, true) && ! $this->lampiranTambahan($b)) {
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

    /**
     * Menyisipkan baris baru setelah baris acuan. Di luar penyusunan, isinya masuk kolom 4.
     * $bagian mengisi bagian lain dari acuannya, misalnya Memperhatikan yang disisipkan sesudah Mengingat.
     */
    public function tambahSetelah(B $acuan, User $user, ?string $label = null, ?string $bagian = null): B
    {
        return DB::transaction(function () use ($acuan, $user, $label, $bagian) {
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
                'bagian' => $bagian ?? $acuan->bagian,
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

            if (! in_array($baris->bagian, B::BAGIAN_BERULANG, true) && ! $this->lampiranTambahan($baris)) {
                abort(403, 'Bagian ini wajib ada dan tidak bisa dibuang.');
            }

            $baris->delete();
        });
    }

    /** Baris lampiran yang bukan satu-satunya lampiran yang masih berlaku. */
    public function lampiranTambahan(B $baris): bool
    {
        return $baris->bagian === 'lampiran'
            && B::where('naskah_id', $baris->naskah_id)->where('bagian', 'lampiran')
                ->whereKeyNot($baris->id)->where('status', '!=', B::DIHAPUS)->exists();
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

    private function beriTahuPemrakarsa(Naskah $naskah, User $pelaku, string $tahapan): void
    {
        if ($naskah->pemrakarsa_id !== $pelaku->id) {
            User::find($naskah->pemrakarsa_id)?->notify(new NaskahNotification(PesanNotifikasi::tahapanTtd($naskah, $pelaku, $tahapan)));
        }
    }

    private function beriTahu(Naskah $naskah, T $dari, User $pelaku, ?string $catatan): void
    {
        $tahap = $naskah->tahap;

        // Tugas tahap sebelumnya sudah lewat, termasuk bagi rekan seperan yang tidak mengerjakannya.
        $naskah->notifikasi()->whereNull('read_at')
            ->whereIn('data->kategori', [PesanNotifikasi::TUGAS, PesanNotifikasi::KEMBALI])
            ->update(['read_at' => now()]);

        // Semua peran yang boleh bertindak ikut diberi tahu; tahap Selesai diberitahukan ke Admin.
        $pemegang = match ($tahap->peran()) {
            'Perangkat Daerah' => User::whereKey($naskah->pemrakarsa_id)->get(),
            'Peneliti' => User::whereKey($naskah->peneliti_id)->get(),
            default => User::role($tahap->pemegang() ?: $tahap->peran())->get(),
        };

        $pemegang = $pemegang->reject(fn ($u) => $u->id === $pelaku->id);

        Notification::send($pemegang, new NaskahNotification(PesanNotifikasi::untukPemegang($naskah, $dari, $pelaku, $catatan)));

        // Pemrakarsa selalu tahu posisi naskahnya, termasuk koreksi oleh Verifikator.
        if ($naskah->pemrakarsa_id !== $pelaku->id && ! $pemegang->contains('id', $naskah->pemrakarsa_id)) {
            User::find($naskah->pemrakarsa_id)?->notify(new NaskahNotification(PesanNotifikasi::untukPemrakarsa($naskah, $pelaku, $catatan)));
        }
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
