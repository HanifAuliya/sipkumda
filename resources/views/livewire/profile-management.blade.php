@section('title', 'Atur Profil')

<div class="space-y-6">
    <livewire:profile.kartu-profil />

    <div class="grid items-start gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <livewire:profile.update-profile-information />
        </div>
        <div class="space-y-6 lg:col-span-2">
            <livewire:profile.update-password />
            {{-- Tanda tangan diatur pemiliknya sendiri: Peneliti untuk matriks, Verifikator (Kepala Bagian Hukum) untuk nota dinas dan matriks. --}}
            @if (auth()->user()->hasRole('Peneliti'))
                <livewire:profile.tanda-tangan-saya />
            @elseif (auth()->user()->hasRole('Verifikator'))
                <livewire:profile.tanda-tangan-kabag />
            @endif
        </div>
    </div>
</div>
