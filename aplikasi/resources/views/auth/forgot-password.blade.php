@section('title', 'Lupa Password')

<x-guest-layout>
    <x-auth.header title="Lupa Password" subtitle="Masukkan email akun Anda, kami akan mengirim tautan untuk mengatur ulang password." />

    @if (session('status'))
        <x-ui.alert type="success" class="mb-5">Tautan untuk mengatur ulang password telah dikirim ke email Anda.</x-ui.alert>
    @endif

    @error('email')
        <x-ui.alert type="danger" class="mb-5">Email yang Anda masukkan tidak ditemukan.</x-ui.alert>
    @enderror

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf

        <x-form.field label="Email" for="email">
            <x-form.input type="email" id="email" name="email" icon="envelope" :value="old('email')" placeholder="nama@email.com" required autofocus />
        </x-form.field>

        <x-auth.submit loading-text="Mengirim...">Kirim Tautan Reset Password</x-auth.submit>
    </form>

    <a href="{{ route('login') }}" class="mt-6 flex items-center justify-center gap-1.5 text-sm font-semibold text-slate-600 hover:text-primary-600 dark:text-slate-300">
        <i class="bi bi-arrow-left"></i> Kembali ke login
    </a>
</x-guest-layout>
