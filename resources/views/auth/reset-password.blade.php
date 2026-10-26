@section('title', 'Reset Password')

<x-guest-layout>
    <x-auth.header title="Atur Ulang Password" subtitle="Masukkan password baru untuk akun Anda." />

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-form.field label="Email" for="email" error="email">
            <x-form.input type="email" id="email" name="email" icon="envelope" :value="old('email', $request->email)" required autofocus autocomplete="username" />
        </x-form.field>

        <x-form.field label="Password Baru" for="password" error="password">
            <x-form.password id="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password" />
        </x-form.field>

        <x-form.field label="Konfirmasi Password" for="password_confirmation" error="password_confirmation">
            <x-form.password id="password_confirmation" name="password_confirmation" placeholder="Ulangi password baru" required autocomplete="new-password" />
        </x-form.field>

        <x-auth.submit loading-text="Menyimpan...">Simpan Password Baru</x-auth.submit>
    </form>

    <a href="{{ route('login') }}" class="mt-6 flex items-center justify-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-primary-600 dark:text-slate-300">
        <i class="bi bi-arrow-left"></i> Kembali ke login
    </a>
</x-guest-layout>
