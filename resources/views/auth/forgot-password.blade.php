@section('title', 'Lupa Password')
<x-guest-layout>
    {{-- Kanan - Form Lupa Password --}}
    <div class="ml-2 col-md-5 d-flex justify-content-center align-items-center bg-white shadow-sm rounded-5">
        <div class="p-5 rounded-3" style="max-width: 500px; width: 100%;">
            {{-- Header --}}
            <div class="text-center mb-4">
                <h3 class="fw-bold">Lupa Password</h3>
                <p class="text-muted small">Masukkan email untuk reset password</p>
            </div>

            {{-- Alert jika email reset password telah dikirim --}}
            @if (session('status'))
                <div class="alert alert-success alert-dismissible fade show text-center small" role="alert">
                    {{ __('Tautan untuk mengatur ulang kata sandi telah dikirim ke alamat email Anda.') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            {{-- Alert jika email tidak ditemukan --}}
            @if ($errors->has('email'))
                <div class="alert alert-danger alert-dismissible fade show text-center small" role="alert">
                    {{ __('Email yang Anda masukkan tidak ditemukan.') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            {{-- Form Lupa Password --}}
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                {{-- Email Input --}}
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda"
                            required>
                    </div>
                </div>

                {{-- Submit Button --}}
                <div class="text-center">
                    <button type="submit" id="resetPasswordButton" class="btn btn-dark w-100 btn-hover-effect">
                        <span id="resetText">Kirim Link Reset Password</span>
                        <span id="resetSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                    </button>
                </div>
            </form>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.getElementById('resetPasswordButton').addEventListener('click', function(event) {
                        let button = document.getElementById('resetPasswordButton');
                        let text = document.getElementById('resetText');
                        let spinner = document.getElementById('resetSpinner');

                        // Nonaktifkan tombol agar tidak diklik berulang kali
                        button.disabled = true;
                        text.textContent = "Mengirim...";
                        spinner.classList.remove('d-none');

                        // Form tetap dikirim ke server
                        button.closest("form").submit();
                    });
                });
            </script>

            {{-- Kembali ke Login --}}
            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="text-decoration-none text-dark">
                    <small>Kembali ke Login</small>
                </a>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-4 small text-muted">
                © Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah
            </div>
        </div>
    </div>

</x-guest-layout>
