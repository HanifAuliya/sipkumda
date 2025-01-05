@extends('layouts.guestauth')

@section('title', 'Register')

@section('content')

    <div class="container mt--200 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                @if (session('status') == 'verification-link-sent')
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ __('Tautan verifikasi baru telah dikirim ke alamat email yang Anda gunakan saat registrasi.') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="card bg-secondary shadow border-0">
                    <div class="card-header bg-white pb-3">
                        <div class="text-muted text-center">
                            <h3>{{ __('Verifikasi Email') }}</h3>
                        </div>
                    </div>
                    <div class="card-body px-lg-5 py-lg-5">
                        <p class="text-center mb-4">
                            {{ __('Terima kasih telah mendaftar! Sebelum memulai, silakan verifikasi alamat email Anda dengan mengklik tautan yang baru saja kami kirim. Jika Anda tidak menerima email tersebut, kami dengan senang hati akan mengirim ulang.') }}
                        </p>

                        <div class="d-flex justify-content-between">
                            <form method="POST" action="{{ route('verification.send') }}">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Kirim Ulang Email Verifikasi') }}
                                </button>
                            </form>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="btn btn-link">
                                    {{ __('Logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="{{ route('login') }}" class="text-primary">
                        <small>{{ __('Kembali ke Halaman Login') }}</small>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
