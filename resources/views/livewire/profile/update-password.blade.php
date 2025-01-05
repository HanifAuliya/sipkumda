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
            </div>
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
            </div>
            @error('password_confirmation')
                <span class="text-danger text-sm mt-2">{{ $message }}</span>
            @enderror
        </div>

        {{-- Save Button --}}
        <div class="form-group text-right">
            <button type="submit" class="btn btn-default">{{ __('Save') }}</button>
        </div>
    </form>
</div>
