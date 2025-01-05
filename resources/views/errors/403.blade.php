<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 Forbidden</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .custom-bg {
            background: linear-gradient(to right, #e2e8f0, #e5e7eb);
            position: relative;
            overflow: hidden;
        }

        .custom-bg::before {
            content: "";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: url('/../assets/img/anggang.webp') no-repeat center;
            background-size: contain;
            opacity: 0.1;
            /* Membuat gambar samar */
            width: 110%;
            height: 110%;
            z-index: 0;
        }

        .custom-content {
            position: relative;
            z-index: 1;
            /* Supaya konten di atas background */
        }

        .custom-btn:hover {
            background-color: #f3e8ff !important;
            transition: background-color 0.3s ease-in-out;
        }

        @media (prefers-color-scheme: dark) {
            .custom-bg {
                background: linear-gradient(to right, #1f2937, #111827);
                color: white !important;
            }

            .custom-btn {
                background-color: #374151 !important;
                color: white !important;
            }

            .custom-btn:hover {
                background-color: #4b5563 !important;
            }
        }
    </style>
</head>

<body>
    <div class="custom-bg text-dark">
        <div class="custom-content d-flex align-items-center justify-content-center min-vh-100 px-2">
            <div class="text-center">
                <h1 class="display-1 fw-bold">403</h1>
                <p class="fs-2 fw-medium mt-4">Access Denied</p>
                <p class="mt-4 mb-5">You do not have permission to access this page.</p>
                <a href="{{ route('dashboard') }}" class="btn btn-light fw-semibold rounded-pill px-4 py-2 custom-btn">
                    Go Back
                </a>
            </div>
        </div>
    </div>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
