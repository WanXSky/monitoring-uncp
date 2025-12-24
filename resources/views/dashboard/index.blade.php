@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold mb-0">Dashboard</h3>
      <div class="small-muted">Ringkasan status website & performa.</div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Total Website</div>
          <div class="display-6 fw-bold">{{ $totalWebsites }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Online</div>
          <div class="display-6 fw-bold text-success">{{ $onlineCount }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Down</div>
          <div class="display-6 fw-bold text-danger">{{ $downCount }}</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div class="fw-bold">Grafik Rata-rata Response Time (5 hari)</div>
            <div class="small-muted">ms</div>
          </div>

          <canvas id="rtChart" height="110"></canvas>

          <div class="small-muted mt-2">
            Jika data kosong, berarti belum ada log monitoring atau response_time NULL.
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <div class="fw-bold mb-2">Daftar Website</div>

          <div class="table-responsive">
            <table class="table table-sm align-middle">
              <thead>
                <tr>
                  <th>Nama</th>
                  <th>URL</th>
                  <th>Status</th>
                  <th>RT (ms)</th>
                  <th>Last Checked</th>
                  <th></th>
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
                    <td>{{ $w->last_checked ? $w->last_checked->format('Y-m-d H:i:s') : '-' }}</td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-outline-primary" href="{{ route('websites.show', $w) }}">Detail</a>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="6" class="text-muted">Belum ada website.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card">
        <div class="card-body">
          <div class="fw-bold mb-2">Notifikasi Terbaru</div>

          @forelse ($latestNotifications as $n)
            <div class="border rounded p-2 mb-2">
              <div class="small-muted">
                {{ $n->sent_at ? $n->sent_at->format('Y-m-d H:i:s') : '-' }}
                • <span class="text-uppercase">{{ $n->type }}</span>
              </div>
              <div class="fw-semibold">{{ $n->website?->name ?? 'Website #' . $n->website_id }}</div>
              <div>{{ $n->message }}</div>
            </div>
          @empty
            <div class="text-muted">Belum ada notifikasi.</div>
          @endforelse
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    const labels = @json($chartLabels);
    const values = @json($chartValues);

    const ctx = document.getElementById('rtChart');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: labels,
        datasets: [{
          label: 'Avg RT (ms)',
          data: values,
          tension: 0.25,
        }]
      },
      options: {
        responsive: true,
        plugins: { legend: { display: true } },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  </script>
@endpush
