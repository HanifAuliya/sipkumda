@section('title', 'Konfirmasi Password')

<x-guest-layout>
    <div class="mb-6 grid place-items-center">
        <span class="grid size-16 place-items-center rounded-full bg-amber-50 text-3xl text-amber-500 dark:bg-amber-500/15">
            <i class="bi bi-shield-lock"></i>
        </span>
    </div>

    <x-auth.header title="Konfirmasi Password" subtitle="Demi keamanan, masukkan kembali password Anda sebelum membuka halaman profil." />

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf

        <x-form.field label="Password" for="password" error="password">
            <x-form.password id="password" name="password" placeholder="Masukkan password" required autofocus autocomplete="current-password" />
        </x-form.field>

        <x-auth.submit loading-text="Memeriksa...">Konfirmasi</x-auth.submit>
    </form>

    <a href="{{ route('dashboard') }}" class="mt-6 flex items-center justify-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-primary-600 dark:text-slate-300">
        <i class="bi bi-arrow-left"></i> Kembali ke dashboard
    </a>
</x-guest-layout>
