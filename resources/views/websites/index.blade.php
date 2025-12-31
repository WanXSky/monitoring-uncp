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

      {{-- Live status --}}
      <div class="small-muted mb-2">
        Live update: <span class="fw-semibold" id="liveStatusText">aktif</span>
        • Terakhir update: <span class="mono" id="liveUpdatedAt">-</span>
      </div>

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
              <tr data-website-id="{{ $w->id }}">
                <td class="fw-semibold">{{ $w->name }}</td>
                <td class="mono"><a href="{{ $w->url }}" target="_blank">{{ $w->url }}</a></td>

                <td class="js-status">
                  @if ($w->status)
                    <span class="badge-soft badge-online">ONLINE</span>
                  @else
                    <span class="badge-soft badge-down">DOWN</span>
                  @endif
                </td>

                <td class="js-rt">{{ $w->response_time ?? '-' }}</td>
                <td class="js-ssl">{{ $w->ssl_expired_at ? $w->ssl_expired_at->toDateString() : '-' }}</td>
                <td class="js-last">{{ $w->last_checked ? $w->last_checked->format('Y-m-d H:i:s') : '-' }}</td>

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

@push('scripts')
<script>
  const statusUrl = @json(route('websites.status'));
  const intervalMs = 15000; // 15 detik

  async function refreshStatuses() {
    const rows = document.querySelectorAll('tr[data-website-id]');
    const ids = Array.from(rows).map(r => r.dataset.websiteId);
    if (!ids.length) return;

    const qs = new URLSearchParams();
    ids.forEach(id => qs.append('ids[]', id));

    try {
      const res = await fetch(statusUrl + '?' + qs.toString(), {
        headers: { 'Accept': 'application/json' }
      });

      if (!res.ok) throw new Error('HTTP ' + res.status);

      const json = await res.json();

      const updatedAt = document.getElementById('liveUpdatedAt');
      if (updatedAt) updatedAt.textContent = json.server_time ?? '-';

      const liveText = document.getElementById('liveStatusText');
      if (liveText) liveText.textContent = 'aktif';

      json.data.forEach(item => {
        const row = document.querySelector(`tr[data-website-id="${item.id}"]`);
        if (!row) return;

        const isUp = Number(item.status) === 1;

        // status
        const statusCell = row.querySelector('.js-status');
        if (statusCell) {
          statusCell.innerHTML = `
            <span class="badge-soft ${isUp ? 'badge-online' : 'badge-down'}">
              ${isUp ? 'ONLINE' : 'DOWN'}
            </span>
          `;
        }

        // rt
        const rtCell = row.querySelector('.js-rt');
        if (rtCell) rtCell.textContent = item.response_time ?? '-';

        // ssl exp
        const sslCell = row.querySelector('.js-ssl');
        if (sslCell) sslCell.textContent = item.ssl_expired_at ?? '-';

        // last checked
        const lastCell = row.querySelector('.js-last');
        if (lastCell) lastCell.textContent = item.last_checked ?? '-';
      });

    } catch (e) {
      const liveText = document.getElementById('liveStatusText');
      if (liveText) liveText.textContent = 'koneksi bermasalah';
    }
  }

  refreshStatuses();
  setInterval(refreshStatuses, intervalMs);
</script>
@endpush
