<?php

namespace App\Livewire\V2;

use App\Enums\TahapNaskah;
use App\Livewire\V2\Concerns\KelolaTahapanTtd;
use App\Models\Naskah;
use App\Services\AlurNaskah;
use App\Support\TataLetakNaskah;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Halaman Tamu: memantau sampai mana paraf dan tanda tangan basah naskah yang diserahkan ke Sekda,
 * mencatat tahapannya, dan membetulkannya bila salah. Isi naskah hanya bisa dibaca.
 */
#[Layout('layouts.app')]
class PantauNaskah extends Component
{
    use KelolaTahapanTtd;

    #[Locked]
    public Naskah $naskah;

    public bool $lihatIsi = false;

    public function mount(Naskah $naskah): void
    {
        Gate::authorize('view', $naskah);
        $this->naskah = $naskah;
        $naskah->tandaiNotifikasiDibaca(auth()->user());
    }

    public function render(AlurNaskah $alur)
    {
        $this->naskah->refresh()->load(['pemrakarsa.perangkatDaerah', 'peneliti', 'notaDinas']);

        // Riwayat yang berkaitan dengan tanda tangan saja: tahapan, dan perpindahan ke/dari penandatanganan dan arsip.
        $tahapAkhir = collect([TahapNaskah::Penandatanganan, TahapNaskah::Arsip, TahapNaskah::Selesai])->map->label()->all();
        $riwayat = $this->naskah->riwayat()
            ->with('user:id,nama_user')
            ->where(fn ($q) => $q->whereIn('aksi', ['tahapan', 'tahapan_tanggal', 'tahapan_batal'])
                ->orWhere(fn ($q) => $q->where('aksi', 'tahap')->where(fn ($q) => $q->whereIn('sesudah', $tahapAkhir)->orWhereIn('sebelum', $tahapAkhir))))
            ->limit(50)
            ->get();

        return view('v2.pantau-naskah', [
            'tahap' => $this->naskah->tahap,
            'nota' => $this->naskah->notaDinas,
            'bolehKelola' => $alur->bolehKelolaTtd($this->naskah, auth()->user()),
            'riwayat' => $riwayat,
            'blokNaskah' => $this->lihatIsi ? TataLetakNaskah::susun($this->naskah, $this->naskah->baris()->get()) : [],
        ])->title('Pantau ' . $this->naskah->nomor);
    }
}
