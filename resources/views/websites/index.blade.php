@extends('layouts.app')

@section('title', 'Data Website')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold mb-0">Data Website</h3>
      <div class="small-muted">Tambah / ubah / hapus website yang dimonitor.</div>
    </div>
    <a href="{{ route('websites.create') }}" class="btn btn-primary">+ Tambah Website</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <form class="row g-2" method="GET" action="{{ route('websites.index') }}">
        <div class="col-md-10">
          <input class="form-control" name="q" value="{{ $q }}" placeholder="Cari nama atau URL...">
        </div>
        <div class="col-md-2 d-grid">
          <button class="btn btn-outline-primary" type="submit">Cari</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Nama</th>
              <th>URL</th>
              <th>Status</th>
              <th>RT (ms)</th>
              <th>SSL Exp</th>
              <th>Last Checked</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($websites as $w)
              <tr>
                <td class="fw-semibold">{{ $w->name }}</td>
                <td class="mono"><a href="{{ $w->url }}" target="_blank">{{ $w->url }}</a></td>
                <td>
                  @if ($w->status)
                    <span class="badge-soft badge-online">ONLINE</span>
                  @else
                    <span class="badge-soft badge-down">DOWN</span>
                  @endif
                </td>
                <td>{{ $w->response_time ?? '-' }}</td>
                <td>{{ $w->ssl_expired_at ? $w->ssl_expired_at->toDateString() : '-' }}</td>
                <td>{{ $w->last_checked ? $w->last_checked->format('Y-m-d H:i:s') : '-' }}</td>
                <td class="text-end">
                  <a class="btn btn-sm btn-outline-primary" href="{{ route('websites.show', $w) }}">Detail</a>
                  <a class="btn btn-sm btn-outline-secondary" href="{{ route('websites.edit', $w) }}">Edit</a>

                  <form method="POST" action="{{ route('websites.destroy', $w) }}" class="d-inline"
                        onsubmit="return confirm('Yakin hapus website ini? Log & notifikasi juga akan dihapus.')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-muted">Belum ada data.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3">
        {{ $websites->links() }}
      </div>
    </div>
  </div>
@endsection
