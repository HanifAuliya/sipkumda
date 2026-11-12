<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * Struktur menu sidebar beserta role yang boleh melihat tiap item.
 * Item tanpa "roles" tampil untuk semua pengguna yang login.
 */
class Navigation
{
    /**
     * Susunan menu mengikuti aplikasi sebelumnya supaya tetap dikenali pengguna: semua pekerjaan
     * rancangan dan fasilitasi membuka naskah yang sama, tanpa unggah ulang berkas.
     */
    public static function sections(): array
    {
        $daftar = fn (string $label, string $kelompok, string $icon, ?array $roles = null) => array_filter([
            'label' => $label,
            'route' => 'daftar',
            'params' => ['kelompok' => $kelompok],
            'icon' => $icon,
            'roles' => $roles,
        ]);

        return [
            [
                'title' => null,
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid-1x2'],
                ],
            ],
            [
                'title' => 'Akun',
                'items' => [
                    ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'person'],
                    ['label' => 'Panduan', 'route' => 'panduan', 'icon' => 'book'],
                    ['label' => 'Daftar Pengguna', 'route' => 'user.management', 'icon' => 'people', 'roles' => ['Admin', 'Verifikator', 'Super Admin']],
                ],
            ],
            [
                'title' => 'Rancangan Produk Hukum',
                'items' => [
                    $daftar('Rancanganku', 'rancanganku', 'clipboard2', ['Perangkat Daerah']),
                    $daftar('Daftar Rancangan', 'rancangan', 'clipboard2-data', ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']),
                    $daftar('Persetujuan Berkas', 'persetujuan-berkas', 'clipboard2-check', ['Admin']),
                    $daftar('Pilih Peneliti', 'pilih-peneliti', 'person-check', ['Verifikator']),
                    $daftar('Penelitian Rancangan', 'penelitian', 'pencil-square', ['Peneliti']),
                    $daftar('Validasi Rancangan', 'validasi-rancangan', 'patch-check', ['Verifikator']),
                ],
            ],
            [
                'title' => 'Fasilitasi Produk Hukum',
                'items' => [
                    $daftar('Fasilitasiku', 'fasilitasiku', 'files', ['Perangkat Daerah']),
                    $daftar('Persetujuan Fasilitasi', 'persetujuan-fasilitasi', 'file-check', ['Peneliti']),
                    $daftar('Pengecekan Fasilitasi', 'cek-peneliti', 'ui-checks', ['Peneliti']),
                    $daftar('Validasi Fasilitasi', 'validasi-fasilitasi', 'file-earmark-check', ['Verifikator']),
                    $daftar('Daftar Nota Dinas', 'nota-dinas', 'file-text', ['Admin', 'Verifikator', 'Perangkat Daerah']),
                    $daftar('Penandatanganan', 'penandatanganan', 'vector-pen', ['Admin', 'Tamu']),
                    $daftar('Daftar Fasilitasi', 'fasilitasi', 'collection', ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']),
                ],
            ],
            [
                'title' => 'Dokumentasi Produk Hukum',
                'items' => [
                    // Antrean arsip menjadi tab di halaman Dokumentasi (khusus Bagian Hukum).
                    ['label' => 'Dokumentasi', 'route' => 'dokumentasi.main', 'icon' => 'folder-check', 'roles' => ['Admin', 'Verifikator', 'Perangkat Daerah', 'Peneliti']],
                    ['label' => 'Master Data', 'route' => 'masterdata.main', 'icon' => 'journals', 'roles' => ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']],
                ],
            ],
            [
                'title' => 'Pengaturan',
                'items' => [
                    ['label' => 'Pengaturan Naskah', 'route' => 'pengaturan', 'icon' => 'gear', 'roles' => ['Admin', 'Verifikator', 'Super Admin']],
                    ['label' => 'Format Dokumen', 'route' => 'format', 'icon' => 'file-earmark-text', 'roles' => ['Admin', 'Verifikator', 'Super Admin']],
                    // Dipakai saat menerbitkan nota dinas dan saat PD menyusun rancangan, bukan tahap pekerjaan.
                    ['label' => 'Kelola TTD', 'route' => 'kelola-ttd.main', 'icon' => 'pen', 'roles' => ['Verifikator']],
                    ['label' => 'Template Bahan Penting', 'route' => 'bahan-penting', 'icon' => 'list-task', 'roles' => ['Admin']],
                    ['label' => 'Perangkat Daerah', 'route' => 'perangkat-daerah', 'icon' => 'building', 'roles' => ['Admin', 'Super Admin']],
                ],
            ],
        ];
    }

    public static function beranda(): string
    {
        return route('dashboard');
    }

    public static function aktif(array $item): bool
    {
        $rute = request()->route();

        if (! $rute || $rute->getName() !== $item['route']) {
            return false;
        }

        foreach ($item['params'] ?? [] as $nama => $nilai) {
            if ((string) $rute->parameter($nama) !== (string) $nilai) {
                return false;
            }
        }

        return true;
    }

    /**
     * Menu yang terlihat oleh pengguna; bagian tanpa item disembunyikan.
     */
    public static function for(Authenticatable $user): array
    {
        return collect(self::sections())
            ->map(function (array $section) use ($user) {
                $section['items'] = array_values(array_filter(
                    $section['items'],
                    fn (array $item) => Route::has($item['route'])
                        && (empty($item['roles']) || $user->hasAnyRole($item['roles'])),
                ));

                return $section;
            })
            ->filter(fn (array $section) => $section['items'] !== [])
            ->values()
            ->all();
    }
}
