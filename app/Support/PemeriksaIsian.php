<?php

namespace App\Support;

use App\Models\Naskah;
use App\Models\NaskahBaris as B;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Pemeriksa isi naskah: isian "…" dari template yang belum diganti, judul yang tidak sama dengan judul
 * pengajuan (Tentang), dan Diktum Peraturan Bupati tanpa "Menetapkan :".
 *
 * Isian "…" menghalangi pengajuan (AlurNaskah::hambatanAjukan); sisanya hanya peringatan di layar
 * kerja, seperti PemeriksaPenomoran.
 */
class PemeriksaIsian
{
    /** Titik-titik isian template: tiga titik atau lebih, atau karakter elipsis. */
    private const POLA_ISIAN = '/\.{3,}|…/u';

    /**
     * Baris yang masih berisi isian template. Penutup (tanggal, nama, Berita Daerah) dan baris NOMOR
     * pada judul memang diisi saat penetapan; lampiran boleh berisi titik-titik isian formulir.
     */
    public static function adaIsian(B $b): bool
    {
        if (in_array($b->bagian, ['penutup', 'lampiran'], true) || $b->terhapus()) {
            return false;
        }

        $teks = (string) $b->teksAkhir();

        if ($b->bagian === 'judul') {
            $teks = collect(preg_split('/\R/u', $teks))->reject(fn ($l) => preg_match('/^\s*NOMOR\b/iu', $l))->implode("\n");
        }

        return (bool) preg_match(self::POLA_ISIAN, $teks);
    }

    public static function nama(B $b): string
    {
        return $b->label ?: TemplateNaskah::BAGIAN[$b->bagian];
    }

    /** Nama dengan nomornya, mis. "Dasar Hukum Mengingat angka 3", supaya jelas baris yang mana. */
    public static function namaDenganNomor(B $b, ?string $penanda): string
    {
        $nama = self::nama($b);

        return match (true) {
            $penanda === null || filled($b->label) => $nama,
            (bool) preg_match('/^(\d+)\.$/', $penanda, $m) => "{$nama} angka {$m[1]}",
            (bool) preg_match('/^([a-z])\.$/', $penanda, $m) => "{$nama} huruf {$m[1]}",
            default => "{$nama} {$penanda}",
        };
    }

    /** Potongan teks di sekitar isian "…" pertama, supaya mudah ditemukan; null bila tidak ada isian. */
    public static function cuplikanIsian(B $b): ?string
    {
        if (! self::adaIsian($b)) {
            return null;
        }

        $teks = Str::squish((string) $b->teksAkhir());
        preg_match(self::POLA_ISIAN, $teks, $m, PREG_OFFSET_CAPTURE);
        $posisi = mb_strlen(substr($teks, 0, $m[0][1]));
        $mulai = max(0, $posisi - 35);
        $potong = mb_substr($teks, $mulai, 70 + mb_strlen($m[0][0]));

        return ($mulai > 0 ? '…' : '') . $potong . ($mulai + mb_strlen($potong) < mb_strlen($teks) ? '…' : '');
    }

    /**
     * Baris yang menghalangi pengajuan rancangan: kosong, atau masih berisi isian "…" dari template.
     * Lampiran boleh kosong.
     *
     * @param  Collection<int, B>  $baris  semua baris naskah, urut
     * @return list<array{baris_id: int, pesan: string, kosong: bool}>
     */
    public static function kurangLengkap(Naskah $naskah, Collection $baris): array
    {
        $penanda = TemplateNaskah::penanda($naskah, $baris);
        $hasil = [];

        foreach ($baris as $b) {
            if ($b->bagian === 'lampiran' || $b->terhapus()) {
                continue;
            }

            $nama = self::namaDenganNomor($b, $penanda[$b->id] ?? null);

            if (blank($b->rumusan_awal)) {
                $hasil[] = ['baris_id' => $b->id, 'pesan' => "{$nama} belum diisi.", 'kosong' => true];
            } elseif ($cuplikan = self::cuplikanIsian($b)) {
                $hasil[] = ['baris_id' => $b->id, 'pesan' => "{$nama} masih berisi titik-titik isian: “{$cuplikan}”", 'kosong' => false];
            }
        }

        return $hasil;
    }

    /** Nama baris yang masih berisi isian, untuk pesan penghambat pengajuan. */
    public static function barisBerisian(Collection $baris): Collection
    {
        return $baris->filter(fn (B $b) => self::adaIsian($b))->map(fn (B $b) => self::nama($b))->unique()->values();
    }

    /** "Konsiderans Menimbang, Pasal 1, dan 2 lainnya" */
    public static function daftarNama(Collection $nama, int $batas = 4): string
    {
        $tampil = $nama->take($batas)->implode(', ');

        return $nama->count() > $batas ? $tampil . ', dan ' . ($nama->count() - $batas) . ' lainnya' : $tampil;
    }

    /** Judul pengajuan dalam huruf kapital, seperti tertulis di naskah. */
    public static function judulKapital(string $tentang): string
    {
        return mb_strtoupper(Str::squish($tentang));
    }

    /** Bagian sesudah baris "TENTANG" pada judul, atau null bila tidak ada. */
    public static function tentangDiJudul(string $judul): ?string
    {
        $baris = preg_split('/\R/u', trim($judul));
        $i = collect($baris)->search(fn ($l) => preg_match('/^\s*TENTANG\s*$/iu', $l));

        return $i === false ? null : Str::squish(implode(' ', array_slice($baris, $i + 1)));
    }

    private static function sama(?string $a, string $b): bool
    {
        $rapi = fn (?string $s) => rtrim(mb_strtoupper(Str::squish((string) $s)), '.');

        return $rapi($a) === $rapi($b);
    }

    /**
     * @param  Collection<int, B>  $baris  semua baris naskah, urut
     * @param  bool  $denganIsian  false saat isian "…" sudah dirinci terpisah (kurangLengkap, pada Draf)
     * @return array<int, array{baris_id: int, pesan: string}>
     */
    public static function periksa(Naskah $naskah, Collection $baris, bool $denganIsian = true): array
    {
        $hidup = $baris->whereNotIn('status', B::TERHAPUS);
        $pesan = [];

        foreach ($denganIsian ? $hidup : [] as $b) {
            if (self::adaIsian($b)) {
                $pesan[] = ['baris_id' => $b->id, 'pesan' => self::nama($b) . ' masih berisi isian “…” dari template.'];
            }
        }

        $judulPengajuan = self::judulKapital($naskah->tentang);
        $judul = $hidup->firstWhere('bagian', 'judul');

        if ($judul && ! self::adaIsian($judul)) {
            $diJudul = self::tentangDiJudul((string) $judul->teksAkhir());

            if ($diJudul !== null && ! self::sama($diJudul, $judulPengajuan)) {
                $pesan[] = ['baris_id' => $judul->id, 'pesan' => "Judul di naskah berbeda dengan judul pengajuan (“{$naskah->tentang}”). Pakai Ubah Judul supaya keduanya sama."];
            }
        }

        if ($naskah->jenis === 'Peraturan Bupati') {
            $diktum = $hidup->where('bagian', 'diktum');
            $menetapkan = $diktum->map(fn (B $b) => (string) $b->teksAkhir())
                ->flatMap(fn ($t) => preg_split('/\R/u', $t))
                ->first(fn ($l) => preg_match('/^\s*menetapkan\s*:/iu', $l));

            if ($diktum->isNotEmpty() && $menetapkan === null) {
                $pesan[] = ['baris_id' => $diktum->first()->id, 'pesan' => 'Diktum tidak memuat “Menetapkan :”, sehingga judul penetapan tidak tercetak.'];
            } elseif ($menetapkan !== null && ! preg_match(self::POLA_ISIAN, $menetapkan)) {
                $isi = preg_replace('/^\s*menetapkan\s*:\s*PERATURAN BUPATI\s+TENTANG\s*/iu', '', $menetapkan);

                if (! self::sama($isi, $judulPengajuan)) {
                    $pesan[] = ['baris_id' => $diktum->first()->id, 'pesan' => 'Bunyi “Menetapkan” berbeda dengan judul pengajuan. Pakai Ubah Judul supaya keduanya sama.'];
                }
            }
        }

        return $pesan;
    }
}
