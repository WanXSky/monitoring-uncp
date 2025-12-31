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
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('url', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('websites.index', compact('websites', 'q'));
    }

    public function status(Request $request)
    {
        $ids = $request->query('ids', []);
        if (!is_array($ids)) $ids = [];

        $rows = Website::query()
            ->select('id', 'status', 'response_time', 'ssl_expired_at', 'last_checked')
            ->when(count($ids) > 0, fn ($q) => $q->whereIn('id', $ids))
            ->get()
            ->map(function ($w) {
                return [
                    'id'            => $w->id,
                    'status'        => (int) $w->status,
                    'response_time' => $w->response_time,
                    'ssl_expired_at'=> $w->ssl_expired_at ? $w->ssl_expired_at->toDateString() : null,
                    'last_checked'  => $w->last_checked ? $w->last_checked->format('Y-m-d H:i:s') : null,
                ];
            });

        return response()->json([
            'data' => $rows,
            'server_time' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    public function create()
    {
        return view('websites.create');
    }

    /**
     * Normalisasi URL:
     * - kalau user input "example.com" -> jadi "https://example.com"
     */
    private function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . $url;
        }

        return $url;
    }

    public function store(Request $request)
    {
        // Normalisasi dulu
        $request->merge([
            'url' => $this->normalizeUrl((string) $request->input('url')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'url'  => [
                'required',
                'string',
                'max:255',
                'url',

                // ✅ WAJIB ada TLD (contoh .com / .id / .ac.id dll)
                'regex:/^https?:\/\/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}(?::\d{1,5})?(?:\/[^\s]*)?$/i',

                Rule::unique('websites', 'url'),
            ],
        ], [
            'url.url'    => 'URL tidak valid.',
            'url.regex'  => 'URL tidak valid. Wajib pakai domain lengkap (contoh: https://contoh.com).',
            'url.unique' => 'URL sudah terdaftar.',
        ]);

        Website::create([
            'name'          => $data['name'],
            'url'           => $data['url'],
            'status'        => 0,
            'response_time' => null,
            'ssl_expired_at'=> null,
            'last_checked'  => null,
        ]);

        return redirect()->route('websites.index')
            ->with('status', 'Website berhasil ditambahkan.');
    }

    public function show(Website $website)
    {
        $logs = MonitoringLog::query()
            ->where('website_id', $website->id)
            ->orderByDesc('checked_at')
            ->limit(200)
            ->get();

        $notifications = Notification::query()
            ->where('website_id', $website->id)
            ->orderByDesc('sent_at')
            ->limit(50)
            ->get();

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
        // Normalisasi dulu
        $request->merge([
            'url' => $this->normalizeUrl((string) $request->input('url')),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'url'  => [
                'required',
                'string',
                'max:255',
                'url',

                // ✅ WAJIB ada TLD
                'regex:/^https?:\/\/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}(?::\d{1,5})?(?:\/[^\s]*)?$/i',

                Rule::unique('websites', 'url')->ignore($website->id),
            ],
        ], [
            'url.url'    => 'URL tidak valid.',
            'url.regex'  => 'URL tidak valid. Wajib pakai domain lengkap (contoh: https://contoh.com).',
            'url.unique' => 'URL sudah terdaftar.',
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
        MonitoringLog::where('website_id', $website->id)->delete();
        Notification::where('website_id', $website->id)->delete();

        $website->delete();

        return redirect()->route('websites.index')
            ->with('status', 'Website berhasil dihapus.');
    }
}
