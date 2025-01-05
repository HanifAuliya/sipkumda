@extends('layouts.guestauth')

@section('title', 'Register')

@section('content')
    <div class="container mt--200 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card bg-secondary shadow border-0">
                    <div class="card-header bg-transparent text-center">
                        <h3>Lupa Password</h3>
                    </div>
                    <div class="card-body">
                        {{-- Alert jika email reset password telah dikirim --}}
                        @if (session('status'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ __('Tautan untuk mengatur ulang kata sandi telah dikirim ke alamat email Anda.') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        {{-- Alert jika email tidak ditemukan --}}
                        @if ($errors->has('email'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ __('Email yang Anda masukkan tidak ditemukan.') }}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('password.email') }}">
                            @csrf
                            {{-- Email --}}
                            <div class="form-group">
                                <div class="input-group input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                    </div>
                                    <input type="email" name="email" class="form-control" placeholder="Enter your email"
                                        required>
                                </div>
                            </div>
                            {{-- Submit --}}
                            <div class="text-center">
                                <button type="submit" class="btn btn-default my-4">Send Password Reset Link</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection
