<div class="card-body">
    <form wire:submit.prevent="updatePassword">
        {{-- Current Password --}}
        <div class="form-group">
            <label for="current_password" class="form-control-label">{{ __('Current Password') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                </div>
                <input type="password" id="current_password" wire:model.defer="current_password" class="form-control"
                    autocomplete="current-password" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-default"
                        onclick="togglePassword('current_password', this)">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            @error('current_password')
                <span class="text-danger text-sm mt-2">{{ $message }}</span>
            @enderror
        </div>

        {{-- New Password --}}
        <div class="form-group">
            <label for="password" class="form-control-label">{{ __('New Password') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                </div>
                <input type="password" id="password" wire:model.defer="password" class="form-control"
                    autocomplete="new-password" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-default" onclick="togglePassword('password', this)">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            {{-- Penjelasan Persyaratan Password --}}
            <small class="form-text text-muted">
                Password harus memiliki minimal 8 karakter, mengandung huruf, angka, dan satu karakter spesial (@, $, !,
                %, *, ?, &, #).
            </small>
            @error('password')
                <span class="text-danger text-sm mt-2">{{ $message }}</span>
            @enderror
        </div>

        {{-- Confirm Password --}}
        <div class="form-group">
            <label for="password_confirmation" class="form-control-label">{{ __('Confirm Password') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                </div>
                <input type="password" id="password_confirmation" wire:model.defer="password_confirmation"
                    class="form-control" autocomplete="new-password" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-default"
                        onclick="togglePassword('password_confirmation', this)">
                        <i class="bi bi-eye-fill"></i>
                    </button>
                </div>
            </div>
            {{-- Penjelasan untuk Konfirmasi Password --}}
            <small class="form-text text-muted">
                Pastikan password yang dimasukkan sesuai dengan password di atas.
            </small>
            @error('password_confirmation')
                <span class="text-danger text-sm mt-2">{{ $message }}</span>
            @enderror
        </div>


        {{-- Save Button --}}
        <div class="form-group text-right">
            <button type="submit" class="btn btn-default" wire:loading.attr="disabled" wire:target="updatePassword">
                <span wire:loading wire:target="updatePassword">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
                <i class="bi bi-floppy2-fill mr-2"></i> {{ __('Save') }}
            </button>
        </div>
    </form>

    <script>
        function togglePassword(fieldId, button) {
            let field = document.getElementById(fieldId);
            let icon = button.querySelector("i");

            if (field.type === "password") {
                field.type = "text";
                icon.classList.replace("bi-eye-fill", "bi-eye-slash-fill");
            } else {
                field.type = "password";
                icon.classList.replace("bi-eye-slash-fill", "bi-eye-fill");
            }
        }
    </script>

</div>
