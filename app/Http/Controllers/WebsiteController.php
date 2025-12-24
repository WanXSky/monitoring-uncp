<?php

namespace App\Http\Controllers;

use App\Models\MonitoringLog;
use App\Models\Notification;
use App\Models\Website;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WebsiteController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $websites = Website::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('url', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('websites.index', compact('websites', 'q'));
    }

    public function create()
    {
        return view('websites.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'url'  => ['required', 'string', 'max:255', 'url'],
        ]);

        // Default awal: belum dicek
        Website::create([
            'name'         => $data['name'],
            'url'          => $data['url'],
            'status'       => 0,
            'response_time'=> null,
            'ssl_expired_at' => null,
            'last_checked' => null,
        ]);

        return redirect()->route('websites.index')
            ->with('status', 'Website berhasil ditambahkan.');
    }

    public function show(Website $website)
    {
        // Log terakhir (misal 200 data terbaru untuk tabel / grafik)
        $logs = MonitoringLog::query()
            ->where('website_id', $website->id)
            ->orderByDesc('checked_at')
            ->limit(200)
            ->get();

        // Notifikasi terakhir untuk website ini
        $notifications = Notification::query()
            ->where('website_id', $website->id)
            ->orderByDesc('sent_at')
            ->limit(50)
            ->get();

        // Grafik sederhana (misal rata-rata per jam 24 jam terakhir)
        $chartRows = MonitoringLog::query()
            ->selectRaw("DATE_FORMAT(checked_at, '%Y-%m-%d %H:00:00') as t, AVG(response_time) as avg_rt")
            ->where('website_id', $website->id)
            ->where('checked_at', '>=', now()->subHours(24))
            ->whereNotNull('response_time')
            ->groupBy('t')
            ->orderBy('t')
            ->get();

        $chartLabels = $chartRows->pluck('t')->toArray();
        $chartValues = $chartRows->pluck('avg_rt')->map(fn ($v) => (int) round($v))->toArray();

        return view('websites.show', compact(
            'website',
            'logs',
            'notifications',
            'chartLabels',
            'chartValues'
        ));
    }

    public function edit(Website $website)
    {
        return view('websites.edit', compact('website'));
    }

    public function update(Request $request, Website $website)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'url'  => ['required', 'string', 'max:255', 'url'],
        ]);

        $website->update([
            'name' => $data['name'],
            'url'  => $data['url'],
        ]);

        return redirect()->route('websites.index')
            ->with('status', 'Website berhasil diubah.');
    }

    public function destroy(Website $website)
    {
        // Kalau FK kamu RESTRICT, hapus log/notif dulu baru hapus website
        MonitoringLog::where('website_id', $website->id)->delete();
        Notification::where('website_id', $website->id)->delete();

        $website->delete();

        return redirect()->route('websites.index')
            ->with('status', 'Website berhasil dihapus.');
    }
}
