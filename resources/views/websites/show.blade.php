@extends('layouts.app')

@section('title', 'Detail Website')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold mb-0">Detail Website</h3>
      <div class="small-muted">Monitor status, log pengecekan, dan notifikasi.</div>
    </div>
    <div class="d-flex gap-2">
      <a href="{{ route('websites.edit', $website) }}" class="btn btn-outline-secondary">Edit</a>
      <a href="{{ route('websites.index') }}" class="btn btn-outline-secondary">Kembali</a>
    </div>
  </div>

  <div class="row g-3">
    {{-- Kiri --}}
    <div class="col-lg-5">
      {{-- Info website --}}
      <div class="card">
        <div class="card-body">
          <div class="fw-bold mb-2">{{ $website->name }}</div>

          <div class="mb-2">
            <div class="small-muted">URL</div>
            <div class="mono">
              <a href="{{ $website->url }}" target="_blank">{{ $website->url }}</a>
            </div>
          </div>

          <div class="mb-2">
            <div class="small-muted">Status</div>
            @if ($website->status)
              <span class="badge-soft badge-online">ONLINE</span>
            @else
              <span class="badge-soft badge-down">DOWN</span>
            @endif
          </div>

          <div class="mb-2">
            <div class="small-muted">Response Time</div>
            <div>{{ $website->response_time ?? '-' }} ms</div>
          </div>

          <div class="mb-2">
            <div class="small-muted">SSL Expired At</div>
            <div>
              {{ $website->ssl_expired_at ? \Carbon\Carbon::parse($website->ssl_expired_at)->format('d/m/Y H:i:s') : '-' }}
            </div>
          </div>

          <div class="mb-0">
            <div class="small-muted">Last Checked</div>
            <div>{{ $website->last_checked ? $website->last_checked->format('Y-m-d H:i:s') : '-' }}</div>
          </div>
        </div>
      </div>

      {{-- Notifikasi terbaru (scroll ~4 item) --}}
      <div class="card mt-3">
        <div class="card-body">
          <div class="fw-bold mb-2">Notifikasi (Terbaru)</div>

          <div class="notif-scroll">
            @forelse ($notifications as $n)
              <div class="border rounded p-2 mb-2">
                <div class="small-muted">
                  {{ $n->sent_at ? $n->sent_at->format('Y-m-d H:i:s') : '-' }}
                  • <span class="text-uppercase">{{ $n->type }}</span>
                </div>

                <div>{{ $n->message }}</div>
              </div>
            @empty
              <div class="text-muted">Belum ada notifikasi.</div>
            @endforelse
          </div>
        </div>
      </div>
    </div>

    {{-- Kanan --}}
    <div class="col-lg-7">
      {{-- Grafik --}}
      <div class="card">
        <div class="card-body">
          <div class="fw-bold mb-2">Grafik Response Time (24 jam)</div>
          <canvas id="rtChart" height="110"></canvas>
          <div class="small-muted mt-2">Jika kosong, berarti belum ada log dalam 24 jam terakhir.</div>
        </div>
      </div>

      {{-- Log monitoring (scroll ~10 baris) --}}
      <div class="card mt-3">
        <div class="card-body">
          <div class="fw-bold mb-2">Log Monitoring (200 terakhir)</div>

          <div class="table-responsive log-scroll">
            <table class="table table-sm align-middle mb-0">
              <thead class="sticky-top bg-white">
                <tr>
                  <th>Waktu</th>
                  <th>Status</th>
                  <th>RT (ms)</th>
                </tr>
              </thead>
              <tbody>
                @forelse ($logs as $log)
                  <tr>
                    <td class="mono">{{ $log->checked_at ? $log->checked_at->format('Y-m-d H:i:s') : '-' }}</td>
                    <td>
                      @if ($log->status)
                        <span class="badge-soft badge-online">ONLINE</span>
                      @else
                        <span class="badge-soft badge-down">DOWN</span>
                      @endif
                    </td>
                    <td>{{ $log->response_time ?? '-' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-muted">Belum ada log monitoring.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </div>
  </div>
@endsection

@push('head')
<style>
  /* Scroll log: kira-kira 10 baris */
  .log-scroll{
    max-height: 393px;
    overflow-y: auto;
    overscroll-behavior: contain;
  }

  /* Scroll notifikasi: kira-kira 4 item */
  .notif-scroll{
    max-height: 384px;
    overflow-y: auto;
    padding-right: 6px;
    overscroll-behavior: contain;
  }
</style>
@endpush

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
        scales: { y: { beginAtZero: true } }
      }
    });
  </script>
@endpush
