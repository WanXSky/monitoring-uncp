<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - Monitoring UNCP</title>
  <link rel="icon" type="image/png" href="{{ asset('img/thum.png') }}?v=1">
  <link rel="apple-touch-icon" href="{{ asset('img/thum.png') }}">
  <meta property="og:image" content="{{ asset('img/thum.png') }}">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="512">
  <meta property="og:image:height" content="512">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card { border:0; box-shadow: 0 8px 22px rgba(0,0,0,0.08); }
    .card-body { overflow: hidden; } /* jaga-jaga biar tidak tembus */

      /* Logo responsif */
    .login-logo{
      max-width: 100%;
      height: auto;
      max-height: 90px; /* atur tinggi maksimum di desktop */
      object-fit: contain;
    }

    /* Kalau mobile, biasanya perlu lebih kecil */
    @media (max-width: 576px){
      .login-logo{
        max-height: 60px;
      }
    }
  </style>
  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
  <div class="container">
    <div class="row justify-content-center" style="margin-top: 90px;">
      <div class="col-md-5">
        <div class="card">
          <div class="card-body p-4">

            {{-- LOGO DI PALING ATAS --}}
            <div class="text-center mb-3 px-2">
              <img
                src="{{ asset('img/icon.jpg') }}"
                alt="Logo UNCP"
                class="img-fluid login-logo"
              >
            </div>

            <h4 class="fw-bold mb-1 text-center">Login Admin</h4>

            @if ($errors->any())
              <div class="alert alert-danger">
                {{ $errors->first() }}
              </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}">
              @csrf

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="{{ old('email') }}" required autofocus>
              </div>

              <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" type="password" name="password" required>
              </div>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <label class="form-check-label">
                  <input class="form-check-input me-1" type="checkbox" name="remember" value="1">
                  Remember me
                </label>
              </div>

              <div class="mb-3 d-flex justify-content-center">
                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
              </div>

              <button class="btn btn-primary w-100" type="submit">Masuk</button>
            </form>

          </div>
        </div>

        <div class="text-center text-muted small mt-3">
          &copy; {{ date('Y') }} Monitoring UNCP
        </div>
      </div>
    </div>
  </div>
</body>
</html>
