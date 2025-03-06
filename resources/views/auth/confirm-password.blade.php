<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="Start your development with a Dashboard for Bootstrap 4." />
    <meta name="author" content="Creative Tim" />
    <title>@yield('title', 'Default Title')</title>
    {{-- Favicon --}}
    <link rel="icon" href="{{ asset('../assets/img/brand/favicon.ico') }}" type="image/png" />

    {{-- Fonts --}}
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700" />

    {{-- Icons --}}
    <link rel="stylesheet" href="{{ asset('../assets/vendor/nucleo/css/nucleo.css') }}" type="text/css" />
    <link rel="stylesheet" href="{{ asset('../assets/vendor/@fortawesome/fontawesome-free/css/all.min.css') }}"
        type="text/css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        type="text/css" />

    {{-- Bootstrap & Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.4.1/dist/css/bootstrap.min.css"
        integrity="sha384-Vkoo8x4CGsO3+Hhxv8T/Q5PaXtkKtu6ug5TOeNV6gBiFeWPGFN9MuhOf23Q9Ifjh" crossorigin="anonymous" />

    {{-- Argon CSS --}}
    <link rel="stylesheet" href="{{ asset('../assets/css/argon.css?v=1.2.0') }}" type="text/css" />

    {{-- Custom CSS --}}
    <link rel="stylesheet" href="{{ asset('../assets/css/sipkumda.css') }}" />
</head>

<body>
    <div class="container mt-9">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                <div class="card bg-secondary shadow border-0">
                    <div class="card-header bg-transparent text-center">
                        <h3>Confirm Password</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-4 text-muted">
                            {{ __('Harap konfirmasikan kata sandi Anda sebelum mengakses halaman profil.') }}
                        </div>

                        <form method="POST" action="{{ route('password.confirm') }}">
                            @csrf
                            <div class="form-group">
                                <label for="password" class="form-control-label">Password</label>
                                <div class="input-group input-group-merge">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="ni ni-lock-circle-open"></i></span>
                                    </div>
                                    <input type="password" id="password" name="password" class="form-control"
                                        placeholder="Enter your password" required>
                                </div>
                                @if ($errors->has('password'))
                                    <small class="text-danger">{{ $errors->first('password') }}</small>
                                @endif
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-default btn-block">Confirm</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
