@section('title', 'Aktivasi Akun')

<x-guest-layout>
    <x-auth.header title="Aktivasi Akun" subtitle="Buat password untuk mulai memakai SIFKUMDA." />

    <form method="POST" action="{{ $aksi }}" class="space-y-5" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf

        <x-form.field label="NIP" for="NIP">
            <x-form.input id="NIP" icon="person-badge" :value="$user->NIP" readonly autocomplete="username" />
        </x-form.field>

        <x-form.field label="Password Baru" for="password" error="password">
            <x-form.password id="password" name="password" placeholder="Minimal 8 karakter" required autofocus autocomplete="new-password" />
        </x-form.field>

        <x-form.field label="Konfirmasi Password" for="password_confirmation" error="password_confirmation">
            <x-form.password id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru" required autocomplete="new-password" />
        </x-form.field>

        <x-auth.submit loading-text="Menyimpan...">Aktifkan Akun</x-auth.submit>
    </form>
</x-guest-layout>
