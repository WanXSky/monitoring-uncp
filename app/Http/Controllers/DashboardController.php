<?php

namespace App\Http\Controllers;

use App\Models\MonitoringLog;
use App\Models\Notification;
use App\Models\Website;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Ringkasan
        $totalWebsites = Website::count();
        $onlineCount   = Website::where('status', 1)->count();
        $downCount     = Website::where('status', 0)->count();

        // Daftar website terpantau (untuk tabel di dashboard)
        $websites = Website::query()
            ->orderBy('name')
            ->get();

        // Notifikasi terbaru (opsional ditampilkan di dashboard)
        $latestNotifications = Notification::query()
            ->with('website')
            ->orderByDesc('sent_at')
            ->limit(10)
            ->get();

        // Data grafik performa (contoh: rata-rata response_time per hari, 5 hari terakhir)
        $chartRows = MonitoringLog::query()
            ->selectRaw('DATE(checked_at) as day, AVG(response_time) as avg_rt')
            ->where('checked_at', '>=', now()->subDays(5))
            ->whereNotNull('response_time')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Siapkan array sederhana untuk Chart.js / tampilan
        $chartLabels = $chartRows->pluck('day')->toArray();
        $chartValues = $chartRows->pluck('avg_rt')->map(fn ($v) => (int) round($v))->toArray();

        return view('dashboard.index', compact(
            'totalWebsites',
            'onlineCount',
            'downCount',
            'websites',
            'latestNotifications',
            'chartLabels',
            'chartValues'
        ));
    }
}
