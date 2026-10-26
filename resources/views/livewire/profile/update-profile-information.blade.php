<form wire:submit="updateProfile" class="space-y-5">
    <x-form.field label="Nama" for="nama_user" error="nama_user" required>
        <x-form.input id="nama_user" wire:model="nama_user" icon="person" required autofocus />
    </x-form.field>

    <x-form.field label="NIP" for="NIP">
        <x-form.input id="NIP" wire:model="NIP" icon="person-badge" readonly disabled />
    </x-form.field>

    <x-form.field label="Email" for="email" error="email" required>
        <x-form.input type="email" id="email" wire:model="email" icon="envelope" required />

        @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
            <x-ui.alert type="warning" class="mt-3">
                Alamat email Anda belum diverifikasi.
                <button type="button" wire:click="sendVerificationEmail" class="font-semibold underline underline-offset-2">
                    Kirim ulang email verifikasi.
                </button>
            </x-ui.alert>
        @endif

        @if (session('status') === 'verification-link-sent')
            <x-ui.alert type="success" class="mt-3">Tautan verifikasi baru telah dikirim ke email Anda.</x-ui.alert>
        @endif
    </x-form.field>

    <div class="grid gap-5 sm:grid-cols-2">
        <x-form.field label="Perangkat Daerah">
            <x-form.input icon="building" disabled
                :value="$daftar_perangkat_daerah->firstWhere('id', $perangkat_daerah_id)?->nama_perangkat_daerah ?? '-'" />
        </x-form.field>

        <x-form.field label="Role">
            <x-form.input icon="shield-check" disabled :value="auth()->user()->getRoleNames()->first() ?? 'Tidak ada role'" />
        </x-form.field>
    </div>

    <div class="flex justify-end">
        <x-ui.button type="submit" variant="navy" icon="floppy" loading="updateProfile">Simpan Perubahan</x-ui.button>
    </div>
</form>
