{{--
    Daftar tahapan tanda tangan basah (Paraf Koordinasi → Asisten → Sekda → Bupati) beserta tombolnya.
    Komponen pemakainya memakai trait KelolaTahapanTtd. Variabel: $naskah, $bolehKelola.
--}}
@php
    use App\Enums\TahapNaskah;
    use App\Services\AlurNaskah as A;

    $tercatat = $naskah->tahapan_ttd ?? [];
    $berikutnya = app(A::class)->tahapanBerikutnya($naskah);
    $bisaTandai = $bolehKelola && $naskah->tahap === TahapNaskah::Penandatanganan;
    $selesai = count(array_intersect_key($tercatat, A::TAHAPAN_TTD));
    $urutan = implode(' → ', A::TAHAPAN_TTD);
@endphp

<div>
    <div class="mb-4 flex items-center gap-3">
        <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-navy-800" role="progressbar"
            aria-valuenow="{{ $selesai }}" aria-valuemin="0" aria-valuemax="{{ count(A::TAHAPAN_TTD) }}" aria-label="Tahapan tanda tangan selesai">
            <div class="h-full rounded-full bg-emerald-500 transition-all" style="width: {{ $selesai / count(A::TAHAPAN_TTD) * 100 }}%"></div>
        </div>
        <span class="shrink-0 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $selesai }} dari {{ count(A::TAHAPAN_TTD) }} selesai</span>
    </div>

    <ol class="space-y-2">
        @foreach (A::TAHAPAN_TTD as $kunci => $nama)
            @php
                $tanggal = $tercatat[$kunci] ?? null;
                $giliran = ! $tanggal && $kunci === $berikutnya && $naskah->tahap === TahapNaskah::Penandatanganan;
            @endphp
            <li wire:key="tahapan-{{ $kunci }}" @class([
                'flex flex-wrap items-center gap-x-4 gap-y-3 rounded-xl p-3 ring-1 ring-inset sm:flex-nowrap',
                'bg-emerald-50/60 ring-emerald-100 dark:bg-emerald-500/5 dark:ring-emerald-500/15' => $tanggal,
                'bg-primary-50/60 ring-primary-200 dark:bg-primary-500/10 dark:ring-primary-500/25' => $giliran,
                'ring-slate-100 dark:ring-navy-800' => ! $tanggal && ! $giliran,
            ])>
                <span @class([
                    'grid size-10 shrink-0 place-items-center rounded-full text-sm font-bold',
                    'bg-emerald-500 text-white' => $tanggal,
                    'bg-primary-500 text-white shadow-soft' => $giliran,
                    'bg-slate-100 text-slate-500 dark:bg-navy-800 dark:text-slate-400' => ! $tanggal && ! $giliran,
                ]) aria-hidden="true">
                    @if ($tanggal)
                        <i class="bi bi-check-lg text-lg"></i>
                    @else
                        {{ $loop->iteration }}
                    @endif
                </span>

                <div class="min-w-0 flex-1">
                    <p class="font-semibold text-navy-900 dark:text-white">{{ $nama }}</p>
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        @if ($tanggal)
                            Selesai {{ tanggal($tanggal) }}
                        @elseif ($giliran)
                            Berikutnya
                        @else
                            Belum
                        @endif
                    </p>
                </div>

                @if ($bolehKelola)
                    <div class="flex w-full shrink-0 flex-wrap justify-end gap-2 sm:w-auto">
                        @if ($tanggal)
                            <x-ui.button size="sm" variant="ghost" icon="calendar-event" wire:click="bukaTahapan('{{ $kunci }}', 'ubah')" loading="bukaTahapan">Ubah Tanggal</x-ui.button>
                            <x-ui.button size="sm" variant="ghost" icon="arrow-counterclockwise" class="hover:text-rose-600 dark:hover:text-rose-300"
                                wire:click="batalkanTahapan('{{ $kunci }}')" loading="batalkanTahapan"
                                wire:confirm="Batalkan tanda {{ $nama }}?{{ $naskah->tahap === TahapNaskah::Arsip ? ' Naskah akan kembali ke tahap Penandatanganan.' : '' }}">Batalkan</x-ui.button>
                        @elseif ($bisaTandai)
                            <x-ui.button size="sm" :variant="$giliran ? 'primary' : 'outline'" icon="check2" wire:click="bukaTahapan('{{ $kunci }}', 'tandai')" loading="bukaTahapan">Tandai Selesai</x-ui.button>
                        @endif
                    </div>
                @endif
            </li>
        @endforeach
    </ol>

    @if ($bisaTandai)
        <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">
            Urutan yang disarankan: {{ $urutan }}. Setelah keempatnya ditandai, naskah menunggu diarsipkan Bagian Hukum.
        </p>
    @endif

    @if ($bolehKelola)
        @php
            $namaDipilih = A::TAHAPAN_TTD[$tahapanDipilih] ?? '';
            $lompat = $modeTahapan === 'tandai' && $tahapanDipilih && $tahapanDipilih !== $berikutnya;
        @endphp
        <x-ui.modal id="modalTahapan" :title="$modeTahapan === 'ubah' ? 'Ubah tanggal ' . $namaDipilih : 'Tandai ' . $namaDipilih . ' selesai'" size="md">
            <div class="space-y-4">
                @error('tahapan')
                    <x-ui.alert type="danger">{{ $message }}</x-ui.alert>
                @enderror

                @if ($lompat)
                    <x-ui.alert type="warning" icon="exclamation-triangle-fill" title="Tidak sesuai urutan">
                        {{ A::TAHAPAN_TTD[$berikutnya] }} belum ditandai. Urutan yang disarankan: {{ $urutan }}.
                        Lanjutkan hanya bila di lapangan memang begitu kejadiannya; hal ini dicatat di riwayat.
                    </x-ui.alert>
                @endif

                <x-form.field label="Tanggal selesai" for="tanggalTtd" error="tanggalTahapan" required>
                    <input id="tanggalTtd" type="date" max="{{ now()->toDateString() }}" wire:model="tanggalTtd"
                        class="w-full rounded-lg border-0 text-sm ring-1 ring-slate-200 focus:ring-2 focus:ring-primary-300 dark:bg-navy-800 dark:ring-navy-700">
                </x-form.field>
            </div>

            <x-slot:footer>
                <x-ui.button variant="ghost" x-on:click="close()">Batal</x-ui.button>
                <x-ui.button :variant="$lompat ? 'warning' : 'primary'" icon="check2" wire:click="simpanTahapan" loading="simpanTahapan">
                    {{ $modeTahapan === 'ubah' ? 'Simpan Tanggal' : ($lompat ? 'Tetap Tandai' : 'Tandai Selesai') }}
                </x-ui.button>
            </x-slot:footer>
        </x-ui.modal>
    @endif
</div>
