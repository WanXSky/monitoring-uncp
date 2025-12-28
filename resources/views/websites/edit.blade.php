@extends('layouts.app')

@section('title', 'Edit Website')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold mb-0">Edit Website</h3>
      <div class="small-muted">Ubah nama/URL website.</div>
    </div>
    <a href="{{ route('websites.index') }}" class="btn btn-outline-secondary">Kembali</a>
  </div>

  <div class="card">
    <div class="card-body">
      <form method="POST" action="{{ route('websites.update', $website) }}">
        @csrf
        @method('PUT')

        <div class="mb-3">
          <label class="form-label">Nama Website</label>
          <input class="form-control" name="name" value="{{ old('name', $website->name) }}" required maxlength="150">
        </div>

        <div class="mb-3">
          <label class="form-label">URL</label>
          <input
            type="url"
            name="url"
            value="{{ old('url', $website->url) }}"
            class="form-control @error('url') is-invalid @enderror"
            placeholder="https://contoh.com"
            required
          >
          @error('url')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <button class="btn btn-primary" type="submit">Update</button>
      </form>
    </div>
  </div>
@endsection
