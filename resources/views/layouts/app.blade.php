<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>@yield('title', 'Monitoring UNCP')</title>

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
</body>
</html>
