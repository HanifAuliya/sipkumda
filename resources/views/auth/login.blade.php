@extends('layouts.guestauth')

@section('title', 'Login')

@section('content')
    <div class="container mt--200 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card bg-secondary border-0 mb-0">
                    {{-- Card Header --}}
                    <div class="card-header bg-transparent text-center">
                        <h3 class="text-default mb-0">Login</h3>
                    </div>
                    <div class="card-body px-lg-5 py-lg-5">

                        <div class="text-center text-muted mb-4">
                            <small>Silahkan Login dengan NIP dan password</small>
                        </div>
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            {{-- NIP Input --}}
                            <div class="form-group">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-badge"></i></span>
                                        {{-- Ganti Ikon jika diperlukan --}}
                                    </div>
                                    <input type="text" name="NIP" class="form-control" placeholder="Masukkan NIP">
                                </div>
                            </div>

                            {{-- Password Input --}}
                            <div class="form-group">
                                <div class="input-group input-group-merge input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                    </div>
                                    <input type="password" name="password" class="form-control" placeholder="Password">
                                </div>
                            </div>
                            @if (session('error'))
                                <div class="alert alert-danger">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger alert-dismissible fade show text-center">
                                    @foreach ($errors->all() as $error)
                                        <strong class="font-weight-300 d-block">{{ $error }}</strong>
                                    @endforeach
                                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                            @endif
                            {{-- Remember Me --}}
                            <div class="custom-control custom-control-alternative custom-checkbox">
                                <input type="checkbox" name="remember" id="remember" class="custom-control-input">
                                <label class="custom-control-label" for="remember">
                                    <span>Remember Me</span>
                                </label>
                            </div>
                            {{-- Submit Button --}}
                            <div class="text-center">
                                <button type="submit" class="btn btn-default my-4">Login</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-6">
                        <a href="{{ route('password.request') }}" class="text-light">
                            <small>Lupa password?</small>
                        </a>

                    </div>
                    <div class="col-6 text-right">
                        <a href="{{ route('register') }}" class="text-light"><small>Buat akun baru</small></a>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
