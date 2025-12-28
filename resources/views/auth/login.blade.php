<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login - Monitoring UNCP</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background:#f6f7fb; }
    .card { border:0; box-shadow: 0 8px 22px rgba(0,0,0,0.08); }
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
            <div class="text-center mb-3">
              <img
                src="{{ asset('img/icon.jpg') }}"
                alt="Logo UNCP"
                style="height:50px; width:auto;"
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

              <div class="mb-3">
                <div class="g-recaptcha" data-sitekey="{{ env('RECAPTCHA_SITE_KEY') }}"></div>
                @error('g-recaptcha-response')
                  <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
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
