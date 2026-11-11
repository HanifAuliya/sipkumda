@php $akun = auth()->user(); @endphp

<x-ui.card title="Informasi Akun"
    :description="$mengubah ? 'Perbarui nama dan alamat email akun Anda.' : 'Data akun Anda di SIFKUMDA.'">
    @unless ($mengubah)
        <x-slot:actions>
            <x-ui.button variant="outline" size="sm" icon="pencil" wire:click="mulaiUbah" loading="mulaiUbah">Ubah</x-ui.button>
        </x-slot:actions>
    @endunless

    @if ($mengubah)
        <form wire:submit="updateProfile" class="space-y-5">
            <x-form.field label="Nama" for="nama_user" error="nama_user" required>
                <x-form.input id="nama_user" wire:model="nama_user" icon="person" required x-init="$el.focus()" />
            </x-form.field>

            <x-form.field label="Email" for="email" error="email" required>
                <x-form.input type="email" id="email" wire:model="email" icon="envelope" required />
            </x-form.field>

            {{-- Kata sandi hanya diminta bila email diganti: email dipakai untuk mengatur ulang kata sandi. --}}
            <div x-data="{ emailAwal: @js($akun->email) }" x-show="$wire.email !== emailAwal" x-cloak>
                <x-form.field label="Kata Sandi Saat Ini" for="sandiSekarang" error="sandiSekarang" required
                    hint="Diminta karena email dipakai untuk mengatur ulang kata sandi.">
                    <x-form.password id="sandiSekarang" name="sandiSekarang" wire:model="sandiSekarang" autocomplete="current-password" />
                </x-form.field>
            </div>

            <p class="text-sm text-slate-500 dark:text-slate-400">NIP, perangkat daerah, dan role hanya bisa diubah oleh admin.</p>

            <div class="flex justify-end gap-2">
                <x-ui.button variant="outline" wire:click="batal">Batal</x-ui.button>
                <x-ui.button type="submit" variant="navy" icon="floppy" loading="updateProfile">Simpan Perubahan</x-ui.button>
            </div>
        </form>
    @else
        <div class="divide-y divide-slate-100 dark:divide-navy-800">
            <x-profil.baris icon="person" label="Nama">{{ $nama_user }}</x-profil.baris>
            <x-profil.baris icon="person-badge" label="NIP">{{ $NIP }}</x-profil.baris>
            <x-profil.baris icon="envelope" label="Email">
                <span class="mr-1.5">{{ $email }}</span>
                @if ($akun->hasVerifiedEmail())
                    <x-ui.badge color="success" icon="check2">Terverifikasi</x-ui.badge>
                @endif
            </x-profil.baris>
            <x-profil.baris icon="building" label="Perangkat Daerah">{{ $daftar_perangkat_daerah->firstWhere('id', $perangkat_daerah_id)?->nama_perangkat_daerah ?? '-' }}</x-profil.baris>
            <x-profil.baris icon="shield-check" label="Role">{{ $akun->getRoleNames()->first() ?? 'Tidak ada role' }}</x-profil.baris>
            <x-profil.baris icon="calendar-check" label="Terdaftar Sejak">{{ tanggal($akun->created_at) }}</x-profil.baris>
        </div>

        @unless ($akun->hasVerifiedEmail())
            <x-ui.alert type="warning" class="mt-4">
                Alamat email Anda belum diverifikasi.
                <button type="button" wire:click="sendVerificationEmail" class="font-semibold underline underline-offset-2">
                    Kirim ulang email verifikasi.
                </button>
            </x-ui.alert>
        @endunless

        @if (session('status') === 'verification-link-sent')
            <x-ui.alert type="success" class="mt-4">Tautan verifikasi baru telah dikirim ke email Anda.</x-ui.alert>
        @endif
    @endif
</x-ui.card>
