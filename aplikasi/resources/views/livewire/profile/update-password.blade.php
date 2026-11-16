<x-ui.card title="Keamanan"
    :description="$mengubah ? 'Gunakan password yang panjang dan acak agar akun tetap aman.' : 'Password dipakai untuk masuk ke SIFKUMDA.'">
    @if ($mengubah)
        <form wire:submit="updatePassword" class="space-y-5">
            @if (auth()->user()->wajib_ganti_sandi)
                <x-ui.alert type="warning" icon="exclamation-triangle">
                    Anda masuk dengan <strong>kata sandi sementara</strong> dari Admin. Isi "Password Saat Ini" dengan kata sandi sementara itu,
                    lalu buat password baru. Menu lain terbuka setelah password diganti.
                </x-ui.alert>
            @endif
            <x-form.field label="Password Saat Ini" for="current_password" error="current_password" required>
                <x-form.password id="current_password" name="current_password" wire:model="current_password" autocomplete="current-password" required x-init="$el.focus()" />
            </x-form.field>

            <x-form.field label="Password Baru" for="password" error="password" required
                hint="Minimal 8 karakter, mengandung huruf, angka, dan satu karakter spesial (@, $, !, %, *, ?, &, #).">
                <x-form.password id="password" name="password" wire:model="password" autocomplete="new-password" required />
            </x-form.field>

            <x-form.field label="Konfirmasi Password Baru" for="password_confirmation" error="password_confirmation" required
                hint="Ketik ulang password baru yang sama.">
                <x-form.password id="password_confirmation" name="password_confirmation" wire:model="password_confirmation" autocomplete="new-password" required />
            </x-form.field>

            <div class="flex justify-end gap-2">
                @unless (auth()->user()->wajib_ganti_sandi)
                    <x-ui.button variant="outline" wire:click="batal">Batal</x-ui.button>
                @endunless
                <x-ui.button type="submit" variant="navy" icon="shield-lock" loading="updatePassword">Perbarui Password</x-ui.button>
            </div>
        </form>
    @else
        <x-profil.baris icon="lock" label="Password"><span class="tracking-widest">••••••••</span></x-profil.baris>

        <x-ui.button variant="outline" icon="shield-lock" class="mt-4 w-full" wire:click="mulaiUbah" loading="mulaiUbah">Ubah Password</x-ui.button>

        <ul class="mt-5 space-y-2 border-t border-slate-100 pt-4 text-sm text-slate-500 dark:border-navy-800 dark:text-slate-400">
            <li class="flex gap-2"><i class="bi bi-check2 mt-0.5 text-emerald-500" aria-hidden="true"></i> Jangan bagikan password kepada siapa pun, termasuk admin.</li>
            <li class="flex gap-2"><i class="bi bi-check2 mt-0.5 text-emerald-500" aria-hidden="true"></i> Keluar dari akun bila memakai komputer bersama.</li>
        </ul>
    @endif
</x-ui.card>
