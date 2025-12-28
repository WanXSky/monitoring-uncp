<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title', 'Monitoring UNCP')</title>
  <link rel="icon" type="image/png" href="{{ asset('img/thum.png') }}?v=1">
  <link rel="apple-touch-icon" href="{{ asset('img/thum.png') }}">
  <meta property="og:image" content="{{ asset('img/thum.png') }}">
  <meta property="og:image:type" content="image/png">
  <meta property="og:image:width" content="512">
  <meta property="og:image:height" content="512">

  {{-- Bootstrap CDN (tanpa npm) --}}
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background: #f6f7fb; }
    .card { border: 0; box-shadow: 0 6px 18px rgba(0,0,0,0.06); }
    .badge-soft { font-weight: 600; padding: .45rem .7rem; border-radius: 999px; }
    .badge-online { background: #d1fae5; color: #065f46; }
    .badge-down { background: #fee2e2; color: #991b1b; }
    .mono { font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; }
    .small-muted { color: #6b7280; font-size: .9rem; }
  </style>

  @stack('head')
</head>
<body>

  @include('partials.nav')

  <main class="container py-4">
    @include('partials.flash')
    @yield('content')
  </main>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')

  {{-- SweetAlert2 --}}
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  @if (session()->has('welcome_popup'))
    <script>
      const welcome = @json(session('welcome_popup'));

      Swal.fire({
        title: `Selamat datang, ${welcome.name} 👋`,
        html: `
          <div style="line-height:1.6">
            Di <b>Sistem Monitoring Website UNCP</b>.<br>
            Semoga monitoring hari ini lancar ✅
          </div>
        `,
        icon: 'success',
        confirmButtonText: 'Iye',
        confirmButtonColor: '#0d6efd',
        timer: 4500,
        timerProgressBar: true,
      });
    </script>
  @endif
</body>
</html>
