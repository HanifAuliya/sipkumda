<div class="card-body">
    {{-- Form Update Profile --}}
    <form wire:submit.prevent="updateProfile">
        {{-- Name --}}
        <div class="form-group">
            <label for="name" class="form-control-label">{{ __('Name') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-single-02"></i></span>
                </div>
                <input type="text" id="nama_user" wire:model="nama_user" class="form-control" required autofocus>
            </div>
            @error('nama_user')
                <span class="text-danger text-sm mt-2">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label for="NIP" class="form-control-label">{{ __('NIP') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-badge"></i></span>
                </div>
                <input type="text" id="NIP" wire:model.defer="NIP" class="form-control" required readonly>
            </div>
        </div>

        {{-- Email --}}
        <div class="form-group">
            <label for="email" class="form-control-label">{{ __('Email') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                </div>
                <input type="email" id="email" wire:model="email" class="form-control" required>
            </div>
            @error('email')
                <span class="text-danger text-sm mt-2">{{ $message }}</span>
            @enderror

            {{-- Email Verification --}}
            @if (Auth::user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && !Auth::user()->hasVerifiedEmail())
                <div class="alert alert-warning mt-3" role="alert">
                    {{ __('Alamat email Anda belum diverifikasi.') }}
                    <button wire:click.prevent="sendVerificationEmail" class="btn btn-link p-0">
                        {{ __('Klik di sini untuk mengirim ulang email verifikasi.') }}
                    </button>
                </div>
            @endif
            @if (session('status') === 'verification-link-sent')
                <div class="alert alert-success mt-3" role="alert">
                    {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
                </div>
            @endif
        </div>


        {{-- Perangkat Daerah --}}
        <div class="form-group">
            <label for="perangkat_daerah_id" class="form-control-label">{{ __('Perangkat Daerah') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text">
                        <i class="bi bi-building"></i> {{-- Icon Perangkat Daerah --}}
                    </span>
                </div>
                <input type="text" class="form-control"
                    value="{{ $daftar_perangkat_daerah->where('id', $perangkat_daerah_id)->first()?->nama_perangkat_daerah }}"
                    readonly>

            </div>
            @error('perangkat_daerah_id')
                <span class="text-danger text-sm mt-2">{{ $message }}</span>
            @enderror
        </div>


        {{-- role --}}
        <div class="form-group">
            <label for="role" class="form-control-label">{{ __('Role') }}</label>
            <div class="input-group input-group-alternative">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="ni ni-settings"></i></span>
                </div>
                <input type="text" id="role"
                    value=" {{ Auth::user()->getRoleNames()->first() ?? 'Tidak Ada Role' }}" class="form-control"
                    readonly>
            </div>
        </div>


        {{-- Save Button --}}
        <div class="form-group text-right">
            <button type="submit" class="btn btn-default" wire:loading.attr="disabled" wire:target="updateProfile">
                <span wire:loading wire:target="updateProfile">
                    <i class="fa fa-spinner fa-spin"></i>
                </span>
                <i class="bi bi-floppy2-fill mr-2"></i>
                {{ __('Save') }}
            </button>
        </div>
    </form>
</div>
