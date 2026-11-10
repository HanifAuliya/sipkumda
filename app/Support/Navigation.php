<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;

/**
 * Struktur menu sidebar beserta role yang boleh melihat tiap item.
 * Item tanpa "roles" tampil untuk semua pengguna yang login.
 *
 * Aplikasi lama dan v2 memakai kerangka tampilan yang sama; yang berbeda hanya isi menunya.
 */
class Navigation
{
    /** Kunci sesi pilihan aplikasi setelah login. */
    public const SESI_APLIKASI = 'aplikasi';

    public const LAMA = 'lama';

    public const V2 = 'v2';

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
                    ['label' => 'Daftar Rancangan', 'route' => 'daftar-rancangan', 'icon' => 'clipboard2-data', 'roles' => ['Admin', 'Verifikator', 'Perangkat Daerah', 'Peneliti', 'Super Admin']],
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
     * Menu v2 mengikuti susunan menu lama, disesuaikan dengan alur baru:
     * semua pekerjaan rancangan dan fasilitasi membuka naskah yang sama, tanpa unggah ulang berkas.
     */
    public static function sectionsV2(): array
    {
        $daftar = fn (string $label, string $kelompok, string $icon, ?array $roles = null) => array_filter([
            'label' => $label,
            'route' => 'v2.daftar',
            'params' => ['kelompok' => $kelompok],
            'icon' => $icon,
            'roles' => $roles,
        ]);

        return [
            [
                'title' => null,
                'items' => [
                    ['label' => 'Dashboard', 'route' => 'v2.beranda', 'icon' => 'grid-1x2'],
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
                    $daftar('Rancanganku', 'rancanganku', 'clipboard2', ['Perangkat Daerah']),
                    $daftar('Daftar Rancangan', 'rancangan', 'clipboard2-data', ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']),
                    $daftar('Persetujuan Berkas', 'persetujuan-berkas', 'clipboard2-check', ['Admin']),
                    ['label' => 'Template Bahan Penting', 'route' => 'bahan-penting', 'icon' => 'list-task', 'roles' => ['Admin']],
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
                    ['label' => 'Kelola TTD', 'route' => 'kelola-ttd.main', 'icon' => 'pen', 'roles' => ['Verifikator']],
                    $daftar('Daftar Nota Dinas', 'nota-dinas', 'file-text', ['Admin', 'Verifikator', 'Perangkat Daerah']),
                    $daftar('Penandatanganan', 'penandatanganan', 'vector-pen', ['Admin', 'Tamu']),
                    $daftar('Daftar Fasilitasi', 'fasilitasi', 'collection', ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']),
                ],
            ],
            [
                'title' => 'Dokumentasi Produk Hukum',
                'items' => [
                    $daftar('Arsipkan Naskah', 'arsip', 'archive', ['Admin', 'Verifikator']),
                    ['label' => 'Daftar Dokumentasi', 'route' => 'dokumentasi.main', 'icon' => 'folder-check', 'roles' => ['Admin', 'Verifikator', 'Perangkat Daerah', 'Peneliti']],
                    ['label' => 'Master Data', 'route' => 'masterdata.main', 'icon' => 'journals', 'roles' => ['Admin', 'Verifikator', 'Peneliti', 'Super Admin']],
                ],
            ],
            [
                'title' => 'Pengaturan',
                'items' => [
                    ['label' => 'Pengaturan Naskah', 'route' => 'v2.pengaturan', 'icon' => 'gear', 'roles' => ['Admin', 'Verifikator', 'Super Admin']],
                    ['label' => 'WhatsApp', 'route' => 'v2.whatsapp', 'icon' => 'whatsapp', 'roles' => ['Admin', 'Super Admin']],
                ],
            ],
        ];
    }

    /**
     * Halaman v2 selalu memakai menu v2. Halaman lama yang juga ada di menu v2 (Profil, Kelola TTD, dst.)
     * mengikuti aplikasi yang dipilih, sehingga pengguna tidak terlempar ke menu lama saat membukanya.
     */
    public static function modeV2(): bool
    {
        $rute = request()->route()?->getName() ?? '';

        if (str_starts_with($rute, 'v2.')) {
            return true;
        }

        if (session(self::SESI_APLIKASI) !== self::V2) {
            return false;
        }

        return collect(self::sectionsV2())->pluck('items')->flatten(1)->contains('route', $rute);
    }

    /** Aplikasi pilihan pengguna, juga terbaca pada permintaan pembaruan Livewire. */
    public static function dipilihV2(): bool
    {
        return session(self::SESI_APLIKASI) === self::V2;
    }

    public static function beranda(): string
    {
        return route(self::modeV2() ? 'v2.beranda' : 'dashboard');
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
        return collect(self::modeV2() ? self::sectionsV2() : self::sections())
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
