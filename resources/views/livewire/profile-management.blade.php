@section('title', 'Atur Profil')

<div>
    <x-ui.page-header title="Profil Saya" description="Kelola informasi akun dan keamanan password Anda" icon="person-gear" />

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Informasi Profil" description="Perbarui nama dan alamat email akun Anda.">
            <livewire:profile.update-profile-information />
        </x-ui.card>

        <x-ui.card title="Ubah Password" description="Gunakan password yang panjang dan acak agar akun tetap aman.">
            <livewire:profile.update-password />
        </x-ui.card>
    </div>
</div>
