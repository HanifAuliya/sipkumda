@extends('layouts.guestauth')

@section('title', 'Register')

@section('content')
    <div class="container mt--200 pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card bg-secondary border-0 mb-0">
                    <div class="card-body px-lg-5 py-lg-5">
                        <form method="POST" action="{{ route('password.store') }}">
                            @csrf

                            {{-- Password Reset Token --}}
                            <input type="hidden" name="token" value="{{ $request->route('token') }}">

                            {{-- Email Address --}}
                            <div class="form-group">
                                <label for="email" class="form-control-label">Email</label>
                                <div class="input-group input-group-merge">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-email-83"></i></span>
                                    </div>
                                    <input type="email" id="email" name="email" class="form-control"
                                        placeholder="Enter your email" value="{{ old('email', $request->email) }}" required
                                        autofocus>
                                </div>
                                @if ($errors->has('email'))
                                    <small class="text-danger">{{ $errors->first('email') }}</small>
                                @endif
                            </div>

                            {{-- Password --}}
                            <div class="form-group">
                                <label for="password" class="form-control-label">Password</label>
                                <div class="input-group input-group-merge">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                    </div>
                                    <input type="password" id="password" name="password" class="form-control"
                                        placeholder="Enter new password" required>
                                </div>
                                @if ($errors->has('password'))
                                    <small class="text-danger">{{ $errors->first('password') }}</small>
                                @endif
                            </div>

                            {{-- Confirm Password --}}
                            <div class="form-group">
                                <label for="password_confirmation" class="form-control-label">Confirm Password</label>
                                <div class="input-group input-group-merge">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                    </div>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" placeholder="Confirm new password" required>
                                </div>
                                @if ($errors->has('password_confirmation'))
                                    <small class="text-danger">{{ $errors->first('password_confirmation') }}</small>
                                @endif
                            </div>

                            {{-- Submit Button --}}
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-block">Reset Password</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
