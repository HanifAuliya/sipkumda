@section('title', 'Atur Profil')

<div class="space-y-6">
    <livewire:profile.kartu-profil />

    <div class="grid items-start gap-6 lg:grid-cols-5">
        <div class="lg:col-span-3">
            <livewire:profile.update-profile-information />
        </div>
        <div class="lg:col-span-2">
            <livewire:profile.update-password />
        </div>
    </div>
</div>
