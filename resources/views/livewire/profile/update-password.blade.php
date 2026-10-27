<form wire:submit="updatePassword" class="space-y-5">
    <x-form.field label="Password Saat Ini" for="current_password" error="current_password" required>
        <x-form.password id="current_password" name="current_password" wire:model="current_password" autocomplete="current-password" required />
    </x-form.field>

    <x-form.field label="Password Baru" for="password" error="password" required
        hint="Minimal 8 karakter, mengandung huruf, angka, dan satu karakter spesial (@, $, !, %, *, ?, &, #).">
        <x-form.password id="password" name="password" wire:model="password" autocomplete="new-password" required />
    </x-form.field>

    <x-form.field label="Konfirmasi Password Baru" for="password_confirmation" error="password_confirmation" required
        hint="Ketik ulang password baru yang sama.">
        <x-form.password id="password_confirmation" name="password_confirmation" wire:model="password_confirmation" autocomplete="new-password" required />
    </x-form.field>

    <div class="flex justify-end">
        <x-ui.button type="submit" variant="navy" icon="shield-lock" loading="updatePassword">Perbarui Password</x-ui.button>
    </div>
</form>
