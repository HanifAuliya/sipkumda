@section('header', 'Atur Profil')
@section('title', 'Profilku')

<div class="row">
    {{-- Profile Information --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="text-default mb-0">{{ __('Informasi Profile') }}</h3>
                <p class="text-muted mt-2">{{ __('Perbarui informasi profil akun dan alamat email Anda..') }}</p>
            </div>
            <div class="card-body">
                @livewire('profile.update-profile-information')

            </div>
        </div>
    </div>

    {{-- Update Password --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header">
                <h3 class="text-default mb-0">{{ __('Update Password') }}</h3>
                <p class="text-muted mt-2">
                    {{ __('Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.') }}
                </p>
            </div>
            <div class="card-body">
                @livewire('profile.update-password')
            </div>
        </div>
    </div>

    {{-- Delete Account --}}
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-transparent text-white">
                <h3 class="mb-0">{{ __('Delete Account') }}</h3>
            </div>
            <div class="card-body">
                <livewire:profile.delete-user />
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('swal:modal', function(event) {
            // Ambil elemen pertama dari array
            const data = event.detail[0];

            $('#confirmDeleteModal').modal('hide'); // Tutup modal
            // $('.modal-backdrop').remove(); // Hapus backdrop modal

            // Tampilkan SweetAlert
            Swal.fire({
                icon: data.type,
                title: data.title,
                text: data.message,
                showConfirmButton: true,
            });
        });
    </script>

</div>
