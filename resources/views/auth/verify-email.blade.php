@section('title', 'Verifikasi Email')

<x-guest-layout>
    <div class="mb-6 grid place-items-center">
        <span class="grid size-16 place-items-center rounded-full bg-primary-50 text-3xl text-primary-500 dark:bg-primary-500/15">
            <i class="bi bi-envelope-check"></i>
        </span>
    </div>

    <x-auth.header title="Verifikasi Email"
        subtitle="Klik tautan verifikasi yang kami kirim ke email Anda. Belum menerima email? Kirim ulang di bawah ini." />

    @if (session('status') == 'verification-link-sent')
        <x-ui.alert type="success" class="mb-5">Tautan verifikasi baru telah dikirim ke email Anda.</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('verification.send') }}" x-data="{ loading: false }" x-on:submit="loading = true">
        @csrf
        <x-auth.submit loading-text="Mengirim...">Kirim Ulang Email Verifikasi</x-auth.submit>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold text-slate-600 hover:text-rose-600 dark:text-slate-300">Keluar</button>
    </form>
</x-guest-layout>
