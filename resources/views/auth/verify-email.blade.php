@section('title', 'Verify Email')

<x-guest-layout>
    {{-- Kanan - Form Verifikasi Email --}}
    <div class="ml-2 col-md-5 d-flex justify-content-center align-items-center bg-white shadow-sm rounded-5">
        <div class="p-5 rounded-3" style="max-width: 500px; width: 100%;">
            {{-- Alert jika verifikasi email telah dikirim --}}
            @if (session('status') == 'verification-link-sent')
                <div class="alert alert-success alert-dismissible fade show text-center small" role="alert">
                    {{ __('Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat registrasi.') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            {{-- Header --}}
            <div class="text-center mb-4">
                <h3 class="fw-bold">Verifikasi Email</h3>
                <p class="text-muted small">
                    Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email Anda dengan mengklik
                    tautan yang baru saja kami kirim. Jika Anda tidak menerima email tersebut, klik tombol di bawah
                    untuk mengirim ulang.
                </p>
            </div>

            {{-- Form Kirim Ulang Verifikasi --}}
            <div class="text-center">
                <form method="POST" action="{{ route('verification.send') }}" id="resendForm">
                    @csrf
                    <button type="submit" id="resendButton" class="btn btn-dark w-100 mb-3">
                        <span id="resendText">Kirim Ulang Email Verifikasi</span>
                        <span id="resendSpinner" class="spinner-border spinner-border-sm d-none" role="status"
                            aria-hidden="true"></span>
                    </button>
                </form>

                {{-- Form Logout --}}
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-link text-dark">
                        Keluar
                    </button>
                </form>
            </div>

            {{-- Footer --}}
            <div class="text-center mt-4 small text-muted">
                © Bagian Hukum Sekretariat Daerah Kabupaten Hulu Sungai Tengah
            </div>
        </div>
    </div>

    {{-- Script untuk Menonaktifkan Tombol setelah Diklik --}}
    <script>
        document.getElementById('resendForm').addEventListener('submit', function() {
            let button = document.getElementById('resendButton');
            let text = document.getElementById('resendText');
            let spinner = document.getElementById('resendSpinner');

            // Nonaktifkan tombol & tampilkan spinner
            button.disabled = true;
            text.textContent = "Mengirim...";
            spinner.classList.remove('d-none');
        });
    </script>


</x-guest-layout>
