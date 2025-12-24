<nav class="navbar navbar-expand-lg bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="{{ route('dashboard') }}">
      <img src="{{ asset('img/icon.jpg') }}" alt="Logo UNCP" style="height:46px;width:auto;">
    </a>

    <style>
      .navbar-brand img { max-height: 44px; }
      @media (max-width: 576px){
        .navbar-brand img { max-height: 34px; }
      }
    </style>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div id="nav" class="collapse navbar-collapse">
      <ul class="navbar-nav me-auto">
        @auth
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
              Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('websites.*') ? 'active' : '' }}" href="{{ route('websites.index') }}">
              Data Website
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
              Pengaturan
            </a>
          </li>
        @endauth
      </ul>

      <ul class="navbar-nav ms-auto">
        @guest
          <li class="nav-item">
            <a class="nav-link" href="{{ route('login') }}">Login</a>
          </li>
        @endguest

        @auth
          <li class="nav-item me-2 d-flex align-items-center small-muted">
            {{ auth()->user()->name }}
            ({{ auth()->user()->email }})
          </li>

          <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}">
              @csrf
              <button type="submit" class="btn btn-outline-danger btn-sm">Logout</button>
            </form>
          </li>
        @endauth
      </ul>
    </div>
  </div>
</nav>
