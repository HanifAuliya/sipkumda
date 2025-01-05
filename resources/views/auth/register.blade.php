@extends('layouts.guestauth')

@section('title', 'Register')

@section('content')

    <div class="container mt--200 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-7">
                <div class="card bg-secondary shadow border-0">
                    <div class="card-header bg-transparent text-center">
                        <h3>Register</h3>
                    </div>
                    <div class="card-body">
                        @if ($errors->any())
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <strong>Terjadi Kesalahan:</strong>
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif

                        {{-- Registration Form --}}
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            {{-- Name --}}
                            <div class="form-group">
                                <div class="input-group input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-circle-08"></i></span>
                                    </div>
                                    <input type="text" name="nama_user" class="form-control" placeholder="Full Name"
                                        value="{{ old('nama_user') }}" required>
                                </div>
                            </div>
                            {{-- NIP --}}
                            <div class="form-group">
                                <div class="input-group input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-badge"></i></span>
                                    </div>
                                    <input type="text" name="NIP" class="form-control" placeholder="NIP"
                                        value="{{ old('NIP') }}" required>
                                </div>
                            </div>
                            {{-- Email --}}
                            <div class="form-group">
                                <div class="input-group input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                    </div>
                                    <input type="email" name="email" class="form-control" placeholder="Email"
                                        value="{{ old('email') }}" required>
                                </div>
                            </div>
                            {{-- Perangkat Daerah --}}
                            <div class="form-group">
                                <select name="perangkat_daerah_id" id="perangkat_daerah_id"
                                    class="form-control dropdown-custom" required>
                                    <option value="" disabled selected>Pilih Perangkat Daerah</option>
                                    @foreach ($perangkatDaerah as $pd)
                                        <option value="{{ $pd->id }}"
                                            {{ old('perangkat_daerah_id') == $pd->id ? 'selected' : '' }}>
                                            {{ $pd->nama_perangkat_daerah }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            {{-- Password --}}
                            <div class="form-group">
                                <div class="input-group input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                    </div>
                                    <input type="password" name="password" class="form-control" placeholder="Password"
                                        required>
                                </div>
                            </div>
                            {{-- Confirm Password --}}
                            <div class="form-group">
                                <div class="input-group input-group-alternative">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                    </div>
                                    <input type="password" name="password_confirmation" class="form-control"
                                        placeholder="Confirm Password" required>
                                </div>
                            </div>
                            {{-- Submit --}}
                            <div class="text-center">
                                <button type="submit" class="btn btn-default my-2">Register</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <a href="{{ route('login') }}" class="text-default"><small>Already have an account?
                                Login</small></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
