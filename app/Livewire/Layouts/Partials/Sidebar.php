<?php

namespace App\Livewire\Layouts\Partials;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class Sidebar extends Component
{
    public $menuSections = [];

    public function mount()
    {
        $user = Auth::user();

        // Menu disusun dalam section agar tidak ada yang dihapus
        $this->menuSections = [
            [
                'title' => null, // Tidak perlu heading
                'items' => [
                    [
                        'title' => 'Dashboard',
                        'route' => 'dashboard',
                        'icon' => 'bi bi-window-fullscreen',
                        'roles' => ['Admin', 'Super Admin', 'Verifikator', 'Peneliti', 'Perangkat Daerah']
                    ]
                ]
            ],
            [
                'title' => 'Manajemen User',
                'items' => [
                    [
                        'title' => 'Profile',
                        'route' => 'profile.edit',
                        'icon' => 'bi bi-person-fill',
                        'roles' => ['Admin', 'Super Admin', 'Verifikator', 'Peneliti', 'Perangkat Daerah']
                    ],
                    [
                        'title' => 'Daftar Pengguna',
                        'route' => 'user.management',
                        'icon' => 'bi bi-people-fill',
                        'roles' => ['Admin', 'Super Admin', 'Verifikator']
                    ]
                ]
            ],
            [
                'title' => 'Rancangan Produk Hukum',
                'items' => [
                    [
                        'title' => 'Rancanganku',
                        'route' => 'rancanganku',
                        'icon' => 'bi bi-clipboard2',
                        'roles' => ['Perangkat Daerah']
                    ],
                    [
                        'title' => 'Daftar Rancangan',
                        'route' => 'daftar-rancangan',
                        'icon' => 'bi bi-clipboard2-data',
                        'roles' => ['Admin', 'Super Admin', 'Verifikator', 'Peneliti', 'Perangkat Daerah']
                    ],
                    [
                        'title' => 'Persetujuan Berkas',
                        'route' => 'admin.persetujuan',
                        'icon' => 'bi bi-card-checklist',
                        'roles' => ['Admin', 'Super Admin']
                    ],
                    [
                        'title' => 'Pilih Peneliti',
                        'route' => 'verifikator.pilih-peneliti',
                        'icon' => 'bi bi-person-check',
                        'roles' => ['Verifikator', 'Super Admin']
                    ],
                    [
                        'title' => 'Upload Revisi',
                        'route' => 'revisi.rancangan',
                        'icon' => 'bi bi-pencil-square',
                        'roles' => ['Peneliti', 'Super Admin']
                    ],
                    [
                        'title' => 'Validasi Rancangan',
                        'route' => 'verifikator.validasi-rancangan',
                        'icon' => 'bi bi-clipboard2-check',
                        'roles' => ['Verifikator', 'Super Admin']
                    ]
                ]
            ],
            [
                'title' => 'Dokumentasi Produk Hukum',
                'items' => [
                    [
                        'title' => 'Daftar Dokumentasi',
                        'route' => 'dokumentasi.main',
                        'icon' => 'bi bi-folder-check',
                        'roles' => ['Admin', 'Super Admin', 'Verifikator', 'Peneliti', 'Perangkat Daerah']
                    ],
                    [
                        'title' => 'Master Data',
                        'route' => 'masterdata.main',
                        'icon' => 'bi bi-journals',
                        'roles' => ['Admin', 'Verifikator', 'Peneliti']
                    ]
                ]
            ]
        ];
    }


    public function render()
    {
        return view('livewire.layouts.partials.sidebar');
    }
}
