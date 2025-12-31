@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="fw-bold mb-0">Dashboard</h3>
      <div class="small-muted">Ringkasan status website & performa.</div>
      <div class="small-muted mt-1">
        Live update: <span class="fw-semibold" id="dashLiveText">aktif</span>
        • Terakhir update: <span class="mono" id="dashLiveAt">-</span>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Total Website</div>
          <div class="display-6 fw-bold" id="statTotal">{{ $totalWebsites }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Online</div>
          <div class="display-6 fw-bold text-success" id="statOnline">{{ $onlineCount }}</div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card">
        <div class="card-body">
          <div class="text-muted">Down</div>
          <div class="display-6 fw-bold text-danger" id="statDown">{{ $downCount }}</div>
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
          <div class="fw-bold mb-2">Daftar Website (maks 6)</div>

          <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
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
              <tbody id="dashWebsitesBody">
                @forelse ($websites as $w)
                  <tr data-website-id="{{ $w->id }}">
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
          <div class="fw-bold mb-2">Notifikasi Terbaru (Live)</div>

          <div class="dash-notif-scroll" id="dashNotifScroll">
            <div id="dashNotifList">
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
    </div>
  </div>
@endsection

@push('head')
<style>
  /* tinggi kira-kira 5 notifikasi */
  .dash-notif-scroll{
    max-height: 440px;
    overflow-y: auto;
    padding-right: 6px;
    overscroll-behavior: contain;
  }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
  const dashLiveUrl = @json(route('dashboard.live'));
  const intervalMs = 15000;

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

  async function refreshDashboard(){
    try{
      const res = await fetch(dashLiveUrl, { headers: { 'Accept': 'application/json' }});
      if(!res.ok) throw new Error('HTTP '+res.status);
      const json = await res.json();

      document.getElementById('dashLiveAt').textContent = json.server_time ?? '-';
      document.getElementById('dashLiveText').textContent = 'aktif';

      // stats
      if(json.stats){
        document.getElementById('statTotal').textContent  = json.stats.total ?? '0';
        document.getElementById('statOnline').textContent = json.stats.online ?? '0';
        document.getElementById('statDown').textContent   = json.stats.down ?? '0';
      }

      // websites table (maks 6)
      const body = document.getElementById('dashWebsitesBody');
      const ws = json.websites ?? [];
      if(ws.length){
        body.innerHTML = ws.map(w => `
          <tr data-website-id="${w.id}">
            <td class="fw-semibold">${w.name}</td>
            <td class="mono"><a href="${w.url}" target="_blank">${w.url}</a></td>
            <td>${badgeHtml(Number(w.status) === 1)}</td>
            <td>${w.response_time ?? '-'}</td>
            <td>${w.last_checked ?? '-'}</td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="/websites/${w.id}">Detail</a>
            </td>
          </tr>
        `).join('');
      } else {
        body.innerHTML = `<tr><td colspan="6" class="text-muted">Belum ada website.</td></tr>`;
      }

      // notifications (jangan ganggu kalau user lagi scroll)
      const notifScroll = document.getElementById('dashNotifScroll');
      const notifAtTop = notifScroll ? notifScroll.scrollTop <= 5 : true;

      if(notifAtTop){
        const list = document.getElementById('dashNotifList');
        const ns = json.notifications ?? [];
        if(ns.length){
          list.innerHTML = ns.map(n => `
            <div class="border rounded p-2 mb-2">
              <div class="small-muted">${n.sent_at ?? '-'} • <span class="text-uppercase">${n.type ?? ''}</span></div>
              <div class="fw-semibold">${n.website ?? '-'}</div>
              <div>${(n.message ?? '').toString().replaceAll('<','&lt;').replaceAll('>','&gt;')}</div>
            </div>
          `).join('');
        } else {
          list.innerHTML = `<div class="text-muted">Belum ada notifikasi.</div>`;
        }
      }

      // chart
      if(json.chart){
        rtChart.data.labels = json.chart.labels ?? [];
        rtChart.data.datasets[0].data = json.chart.values ?? [];
        rtChart.update();
      }

    } catch(e){
      document.getElementById('dashLiveText').textContent = 'koneksi bermasalah';
    }
  }

  refreshDashboard();
  setInterval(refreshDashboard, intervalMs);
</script>
@endpush
