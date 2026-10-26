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
    public static function sections(): array
    {
        return [
            [
                'title' => null,
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'grid-1x2'],
                ],
            ],
            [
                'title' => 'Manajemen User',
                'items' => [
                    ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'person'],
                    ['label' => 'Daftar Pengguna', 'route' => 'user.management', 'icon' => 'people', 'roles' => ['Admin', 'Verifikator', 'Super Admin']],
                ],
            ],
            [
                'title' => 'Rancangan Produk Hukum',
                'items' => [
                    ['label' => 'Rancanganku', 'route' => 'rancanganku', 'icon' => 'clipboard2', 'roles' => ['Perangkat Daerah']],
                    ['label' => 'Daftar Rancangan', 'route' => 'daftar-rancangan', 'icon' => 'clipboard2-data'],
                    ['label' => 'Persetujuan Berkas', 'route' => 'admin.persetujuan', 'icon' => 'clipboard2-check', 'roles' => ['Admin']],
                    ['label' => 'Template Bahan Penting', 'route' => 'bahan-penting', 'icon' => 'list-task', 'roles' => ['Admin']],
                    ['label' => 'Pilih Peneliti', 'route' => 'verifikator.pilih-peneliti', 'icon' => 'person-check', 'roles' => ['Verifikator']],
                    ['label' => 'Upload Penelitian', 'route' => 'revisi.rancangan', 'icon' => 'pencil-square', 'roles' => ['Peneliti']],
                    ['label' => 'Validasi Rancangan', 'route' => 'verifikator.validasi-rancangan', 'icon' => 'patch-check', 'roles' => ['Verifikator']],
                ],
            ],
            [
                'title' => 'Fasilitasi Produk Hukum',
                'items' => [
                    ['label' => 'Fasilitasiku', 'route' => 'fasilitasiku.main', 'icon' => 'files', 'roles' => ['Perangkat Daerah']],
                    ['label' => 'Persetujuan Fasilitasi', 'route' => 'persetujuan-fasilitasi.main', 'icon' => 'file-check', 'roles' => ['Peneliti']],
                    ['label' => 'Validasi Fasilitasi', 'route' => 'validasi-fasilitasi', 'icon' => 'file-earmark-check', 'roles' => ['Verifikator']],
                    ['label' => 'Kelola TTD', 'route' => 'kelola-ttd.main', 'icon' => 'pen', 'roles' => ['Verifikator']],
                    ['label' => 'Daftar Nota Dinas', 'route' => 'nota-dinas.generate', 'icon' => 'file-text', 'roles' => ['Admin', 'Verifikator', 'Perangkat Daerah']],
                    ['label' => 'Manajemen Fasilitasi', 'route' => 'manajemen-fasilitasi', 'icon' => 'file-earmark-break', 'roles' => ['Admin', 'Tamu']],
                    ['label' => 'Daftar Fasilitasi', 'route' => 'daftar-fasilitasi', 'icon' => 'collection', 'roles' => ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']],
                ],
            ],
            [
                'title' => 'Dokumentasi Produk Hukum',
                'items' => [
                    ['label' => 'Daftar Dokumentasi', 'route' => 'dokumentasi.main', 'icon' => 'folder-check', 'roles' => ['Admin', 'Verifikator', 'Perangkat Daerah', 'Peneliti']],
                    ['label' => 'Master Data', 'route' => 'masterdata.main', 'icon' => 'journals', 'roles' => ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']],
                ],
            ],
        ];
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
