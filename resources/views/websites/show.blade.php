@extends('layouts.app')

@section('title', 'Detail Website')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold mb-0">Detail Website</h3>
      <div class="small-muted">Monitor status, log pengecekan, dan notifikasi.</div>
      <div class="small-muted mt-1">
        Live update: <span class="fw-semibold" id="wsLiveText">aktif</span>
        • Terakhir update: <span class="mono" id="wsLiveAt">-</span>
      </div>
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
            <div id="wsStatus">
              @if ($website->status)
                <span class="badge-soft badge-online">ONLINE</span>
              @else
                <span class="badge-soft badge-down">DOWN</span>
              @endif
            </div>
          </div>

          <div class="mb-2">
            <div class="small-muted">Response Time</div>
            <div id="wsRt">{{ $website->response_time ?? '-' }} ms</div>
          </div>

          <div class="mb-2">
            <div class="small-muted">SSL Expired At</div>
            <div id="wsSsl">
              {{ $website->ssl_expired_at ? \Carbon\Carbon::parse($website->ssl_expired_at)->format('d/m/Y H:i:s') : '-' }}
            </div>
          </div>

          <div class="mb-0">
            <div class="small-muted">Last Checked</div>
            <div id="wsLast">{{ $website->last_checked ? $website->last_checked->format('Y-m-d H:i:s') : '-' }}</div>
          </div>
        </div>
      </div>

      {{-- Notifikasi terbaru --}}
      <div class="card mt-3">
        <div class="card-body">
          <div class="fw-bold mb-2">Notifikasi (Terbaru)</div>

          <div class="notif-scroll" id="wsNotifScroll">
            <div id="wsNotifList">
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

      {{-- Log monitoring --}}
      <div class="card mt-3">
        <div class="card-body">
          <div class="fw-bold mb-2">Log Monitoring (200 terakhir)</div>

          <div class="table-responsive log-scroll" id="wsLogScroll">
            <table class="table table-sm align-middle mb-0">
              <thead class="sticky-top bg-white">
                <tr>
                  <th>Waktu</th>
                  <th>Status</th>
                  <th>RT (ms)</th>
                </tr>
              </thead>
              <tbody id="wsLogsBody">
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
  .log-scroll{
    max-height: 393px;
    overflow-y: auto;
    overscroll-behavior: contain;
  }
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
  const liveUrl = @json(route('websites.live', $website));
  const intervalMs = 15000;

  // init chart pertama dari server
  const initLabels = @json($chartLabels);
  const initValues = @json($chartValues);

  const ctx = document.getElementById('rtChart');
  const rtChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: initLabels,
      datasets: [{
        label: 'Avg RT (ms)',
        data: initValues,
        tension: 0.25,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: true } },
      scales: { y: { beginAtZero: true } }
    }
  });

  function badgeHtml(isUp){
    return `<span class="badge-soft ${isUp ? 'badge-online' : 'badge-down'}">${isUp ? 'ONLINE' : 'DOWN'}</span>`;
  }

  async function refreshShow(){
    try{
      const res = await fetch(liveUrl, { headers: { 'Accept': 'application/json' }});
      if(!res.ok) throw new Error('HTTP '+res.status);

      const json = await res.json();

      document.getElementById('wsLiveAt').textContent = json.server_time ?? '-';
      document.getElementById('wsLiveText').textContent = 'aktif';

      // website info
      const w = json.website;
      if(w){
        document.getElementById('wsStatus').innerHTML = badgeHtml(Number(w.status) === 1);
        document.getElementById('wsRt').textContent = (w.response_time ?? '-') + ' ms';
        document.getElementById('wsSsl').textContent = w.ssl_text ?? '-';
        document.getElementById('wsLast').textContent = w.last_checked ?? '-';
      }

      // update notif (jangan ganggu kalau user lagi scroll)
      const notifScroll = document.getElementById('wsNotifScroll');
      const notifAtTop = notifScroll ? notifScroll.scrollTop <= 5 : true;

      if (notifAtTop) {
        const list = document.getElementById('wsNotifList');
        const items = json.notifications ?? [];
        if(items.length){
          list.innerHTML = items.map(n => `
            <div class="border rounded p-2 mb-2">
              <div class="small-muted">${n.sent_at ?? '-'} • <span class="text-uppercase">${n.type ?? ''}</span></div>
              <div>${(n.message ?? '').toString().replaceAll('<','&lt;').replaceAll('>','&gt;')}</div>
            </div>
          `).join('');
        } else {
          list.innerHTML = `<div class="text-muted">Belum ada notifikasi.</div>`;
        }
      }

      // update logs (jangan ganggu kalau user lagi scroll)
      const logScroll = document.getElementById('wsLogScroll');
      const logAtTop = logScroll ? logScroll.scrollTop <= 5 : true;

      if (logAtTop) {
        const body = document.getElementById('wsLogsBody');
        const logs = json.logs ?? [];
        if(logs.length){
          body.innerHTML = logs.map(l => `
            <tr>
              <td class="mono">${l.checked_at ?? '-'}</td>
              <td>${badgeHtml(Number(l.status) === 1)}</td>
              <td>${l.response_time ?? '-'}</td>
            </tr>
          `).join('');
        } else {
          body.innerHTML = `<tr><td colspan="3" class="text-muted">Belum ada log monitoring.</td></tr>`;
        }
      }

      // update chart
      if(json.chart){
        rtChart.data.labels = json.chart.labels ?? [];
        rtChart.data.datasets[0].data = json.chart.values ?? [];
        rtChart.update();
      }

    } catch(e){
      document.getElementById('wsLiveText').textContent = 'koneksi bermasalah';
    }
  }

  refreshShow();
  setInterval(refreshShow, intervalMs);
</script>
@endpush
