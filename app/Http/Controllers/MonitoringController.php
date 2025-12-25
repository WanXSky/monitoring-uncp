<?php

namespace App\Http\Controllers;

use App\Models\Website;
use App\Models\MonitoringLog;
use Illuminate\Support\Facades\Http;

class MonitoringController extends Controller
{
    public function checkWebsites()
    {
        foreach (Website::all() as $site) {

            $start = microtime(true);

            try {
                $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',])->timeout(10)->get($site->url);
                $status = $response->successful();
                $time = (microtime(true) - $start) * 1000;
            } catch (\Exception $e) {
                $status = false;
                $time = null;
            }

            $site->update([
                'status' => $status,
                'response_time' => $time,
                'last_checked' => now()
            ]);

            MonitoringLog::create([
                'website_id' => $site->id,
                'status' => $status,
                'response_time' => $time,
                'checked_at' => now()
            ]);
        }
    }
}
