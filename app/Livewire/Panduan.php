<?php

namespace App\Livewire;

use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Panduan pengguna di dalam aplikasi. Isinya dibaca dari docs/panduan/*.md, jadi cukup satu tempat
 * yang diperbarui: berkas yang sama juga terbaca di repositori.
 */
#[Layout('layouts.app')]
#[Title('Panduan')]
class Panduan extends Component
{
    /** slug => [judul, peran yang memakainya] */
    public const PANDUAN = [
        'perangkat-daerah' => ['Perangkat Daerah', ['Perangkat Daerah']],
        'peneliti' => ['Peneliti', ['Peneliti']],
        'verifikator' => ['Verifikator', ['Verifikator']],
        'admin' => ['Admin', ['Admin', 'Super Admin']],
        'tamu' => ['Pencatat Tanda Tangan', ['Tamu']],
    ];

    #[Locked]
    public string $peran = '';

    /** Admin bisa membaca semua panduan, untuk melatih pengguna lain; yang lain hanya panduan perannya. */
    private function tersedia()
    {
        $user = auth()->user();

        return collect(self::PANDUAN)
            ->filter(fn ($p) => $user->hasAnyRole(['Admin', 'Super Admin']) || $user->hasAnyRole($p[1]))
            ->map(fn ($p) => $p[0]);
    }

    public function mount(?string $peran = null): void
    {
        $tersedia = $this->tersedia();
        abort_if($tersedia->isEmpty(), 404);

        $this->peran = $peran ?? $tersedia->keys()->first();
        abort_unless($tersedia->has($this->peran), 404);
    }

    public function render()
    {
        $isi = file_get_contents(base_path("docs/panduan/{$this->peran}.md"));

        return view('livewire.panduan', [
            'tersedia' => $this->tersedia(),
            // Berkas dari repositori sendiri; HTML mentah tetap dimatikan.
            'html' => Str::markdown($isi, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
        ]);
    }
}
