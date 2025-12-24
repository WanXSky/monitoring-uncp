@if (session('status'))
  <div class="alert alert-success">
    {{ session('status') }}
  </div>
@endif

@if ($errors->any())
  <div class="alert alert-danger">
    <div class="fw-bold mb-1">Terjadi kesalahan:</div>
    <ul class="mb-0">
      @foreach ($errors->all() as $err)
        <li>{{ $err }}</li>
      @endforeach
    </ul>
  </div>
@endif
