<x-guest-layout>

    {{-- Kanan - Form Login --}}
    <div class="ml-2 col-md-5 d-flex justify-content-center align-items-center bg-white shadow-sm rounded-5">
        <div class="p-5 rounded-3" style="max-width: 500px; width: 100%;">
            {{-- Header --}}
            <div class="text-center mb-4">
                <h3 class="fw-bold">Login</h3>
                <p class="text-muted small">Dengan NIP</p>
            </div>

            {{-- Form Login --}}
            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- NIP Input --}}
                <div class="mb-4">
                    <div class="input-group">
                        <input type="text" name="NIP" id="NIP" class="form-control" placeholder="NIP"
                            required>
                    </div>
                </div>

                {{-- Password Input --}}
                <div class="mb-3 position-relative">
                    <input type="password" name="password" id="password" class="form-control"
                        placeholder="Masukkan Password" required>
                </div>

                @if (session('error'))
                    <p class="text-danger small mb-2">
                        {{ session('error') }}
                    </p>
                @endif


                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show text-center">
                        @foreach ($errors->all() as $error)
                            <strong class="font-weight-300 d-block">{{ $error }}</strong>
                        @endforeach
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                @endif

                {{-- Link Lupa Password --}}
                <div class="text-end mb-3">
                    <a href="{{ route('password.request') }}" class="text-decoration-none text-dark">
                        <small>Lupa password?</small>
                    </a>
                </div>

                {{-- Submit Button --}}
                <div class="text-center">
                    <button type="submit" id="loginButton" class="btn btn-dark w-100">
                        <span id="loginText">Login</span>
                        <span id="loginSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                    </button>
                </div>

                {{-- Info Tambahan --}}
                <p class="text-danger small text-center mt-3">
                    Login website ini hanya untuk perangkat daerah dan pegawai Bagian Hukum HST.
                </p>
            </form>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    // Tombol login loading effect
                    document.getElementById('loginButton').addEventListener('click', function(event) {
                        let button = document.getElementById('loginButton');
                        let text = document.getElementById('loginText');
                        let spinner = document.getElementById('loginSpinner');

                        // Nonaktifkan tombol dan tampilkan spinner
                        button.disabled = true;
                        text.textContent = "Logging in...";
                        spinner.classList.remove('d-none');

                        // Kirim form
                        button.closest("form").submit();
                    });
                });
            </script>
            {{-- Footer --}}
            <div class="text-center mt-4 small text-muted">
                © Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah
            </div>
        </div>
    </div>


</x-guest-layout>
