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

    public function live()
    {
        $total = Website::count();
        $online = Website::where('status', 1)->count();
        $down   = Website::where('status', 0)->count();

        // daftar website untuk dashboard (maks 6)
        $websites = Website::query()
            ->orderBy('name')
            ->limit(6)
            ->get()
            ->map(fn ($w) => [
                'id'           => $w->id,
                'name'         => $w->name,
                'url'          => $w->url,
                'status'       => (int) $w->status,
                'response_time'=> $w->response_time,
                'last_checked' => $w->last_checked ? $w->last_checked->format('Y-m-d H:i:s') : null,
            ]);

        // notifikasi terbaru (maks 5)
        $latestNotifications = Notification::query()
            ->with('website:id,name')
            ->orderByDesc('sent_at')
            ->limit(5)
            ->get()
            ->map(fn ($n) => [
                'sent_at' => $n->sent_at ? $n->sent_at->format('Y-m-d H:i:s') : null,
                'type'    => strtoupper((string) $n->type),
                'website' => $n->website?->name ?? ('Website #'.$n->website_id),
                'message' => (string) $n->message,
            ]);

        // chart 5 hari (avg per hari) - sesuaikan dengan logic dashboard kamu
        $chartRows = MonitoringLog::query()
            ->selectRaw("DATE(checked_at) as d, AVG(response_time) as avg_rt")
            ->where('checked_at', '>=', now()->subDays(5))
            ->whereNotNull('response_time')
            ->groupBy('d')
            ->orderBy('d')
            ->get();

        $chartLabels = $chartRows->pluck('d')->toArray();
        $chartValues = $chartRows->pluck('avg_rt')->map(fn ($v) => (int) round($v))->toArray();

        return response()->json([
            'server_time' => now()->format('Y-m-d H:i:s'),
            'stats' => [
                'total'  => $total,
                'online' => $online,
                'down'   => $down,
            ],
            'websites' => $websites,
            'notifications' => $latestNotifications,
            'chart' => [
                'labels' => $chartLabels,
                'values' => $chartValues,
            ],
        ]);
    }
}
