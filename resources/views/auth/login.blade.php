@section('title', 'Login')

<x-guest-layout>
    <x-auth.header title="Masuk ke SIFKUMDA" subtitle="Gunakan NIP dan password akun Anda." />

    @if (session('error'))
        <x-ui.alert type="danger" class="mb-5">{{ session('error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert type="danger" class="mb-5" dismissible>
            @foreach ($errors->all() as $error)
                <span class="block">{{ $error }}</span>
            @endforeach
        </x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf

        <x-form.field label="NIP" for="NIP">
            <x-form.input id="NIP" name="NIP" icon="person-badge" :value="old('NIP')" placeholder="Masukkan NIP" required autofocus autocomplete="username" />
        </x-form.field>

        <x-form.field for="password">
            <div class="flex items-center justify-between">
                <label for="password" class="text-sm font-semibold text-slate-700 dark:text-slate-200">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 dark:text-primary-300">Lupa password?</a>
            </div>
            <x-form.password id="password" name="password" placeholder="Masukkan password" required autocomplete="current-password" />
        </x-form.field>

        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
            <input type="checkbox" name="remember" class="rounded border-slate-300 text-primary-500 focus:ring-primary-200 dark:border-navy-700 dark:bg-navy-900">
            Ingat saya
        </label>

        <x-auth.submit loading-text="Sedang masuk...">Masuk</x-auth.submit>
    </form>

    <x-ui.alert type="warning" icon="shield-lock" class="mt-6 text-xs">
        Login hanya untuk perangkat daerah dan pegawai Bagian Hukum HST.
    </x-ui.alert>
</x-guest-layout>
