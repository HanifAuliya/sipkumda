@section('title', 'Register')

<x-guest-layout>
    {{-- Kanan - Form Reset Password --}}
    <div class="ml-2 col-md-5 d-flex justify-content-center align-items-center bg-white shadow-sm rounded-5">
        <div class="p-5 rounded-3" style="max-width: 500px; width: 100%;">
            {{-- Header --}}
            <div class="text-center mb-4">
                <h3 class="fw-bold">Reset Password</h3>
                <p class="text-muted small">Masukkan password baru Anda</p>
            </div>

            {{-- Form Reset Password --}}
            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                {{-- Token Reset Password --}}
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                {{-- Email Address --}}
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" id="email" name="email" class="form-control"
                            placeholder="Masukkan email Anda" value="{{ old('email', $request->email) }}" required
                            autofocus>
                    </div>
                    @if ($errors->has('email'))
                        <small class="text-danger">{{ $errors->first('email') }}</small>
                    @endif
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label for="password" class="form-label">Password Baru</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password" name="password" class="form-control"
                            placeholder="Masukkan password baru" required>
                    </div>
                    @if ($errors->has('password'))
                        <small class="text-danger">{{ $errors->first('password') }}</small>
                    @endif
                </div>

                {{-- Confirm Password --}}
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" id="password_confirmation" name="password_confirmation"
                            class="form-control" placeholder="Konfirmasi password baru" required>
                    </div>
                    @if ($errors->has('password_confirmation'))
                        <small class="text-danger">{{ $errors->first('password_confirmation') }}</small>
                    @endif
                </div>

                {{-- Submit Button --}}
                <div class="text-center">
                    <button type="submit" class="btn btn-dark w-100">Reset Password</button>
                </div>
            </form>

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
