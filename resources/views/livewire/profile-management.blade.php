@section('title', 'Atur Profil')
@section('manual')
    <div class="card  mb--2">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="d-flex flex-column">
                <h3 class="mb-0">Menu Atur Profile</h3>
                <p class="description">
                    Profile
                </p>
            </div>

            {{-- Tombol untuk Verifikator --}}
            <a href="{{ route('dashboard') }}" class="btn btn-outline-primary">
                <i class="bi bi-skip-backward mr-2"></i> Kembali
            </a>

        </div>
    </div>
@endsection

<div class="row justify-content-center">
    {{-- Profile Information --}}
    <div class="col-md-6 col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="text-default mb-0">{{ __('Informasi Profil') }}</h3>
                <p class="text-muted mt-2">{{ __('Perbarui informasi profil akun dan alamat email Anda.') }}</p>
            </div>
            <div class="card-body">
                <livewire:profile.update-profile-information />
            </div>
        </div>
    </div>

    {{-- Update Password --}}
    <div class="col-md-6 col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="text-default mb-0">{{ __('Update Password') }}</h3>
                <p class="text-muted mt-2">
                    {{ __('Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.') }}
                </p>
            </div>
            <div class="card-body">
                <livewire:profile.update-password />
            </div>
        </div>
    </div>
</div>
