<?php

namespace App\Console\Commands;

use App\Models\MonitoringLog;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Website;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonitorWebsites extends Command
{
    /**
     * Nama command yang dipanggil dari terminal.
     */
    protected $signature = 'monitor:websites';

    /**
     * Deskripsi command.
     */
    protected $description = 'Check website status, response time, and SSL expiry then store logs';

    public function handle(): int
    {
        // Ambil settings (buat jika belum ada)
        $setting = Setting::firstOrCreate(
            ['id' => 1],
            ['check_interval' => 1, 'notify_ssl_days' => 7]
        );

        $intervalMinutes = max(1, (int) $setting->check_interval);
        $sslWarnDays     = max(1, (int) $setting->notify_ssl_days);

        $now = now();
        $count = 0;

        Website::query()->chunkById(50, function ($websites) use ($now, $intervalMinutes, $sslWarnDays, &$count) {
            foreach ($websites as $website) {

                // Skip jika belum waktunya cek berdasarkan last_checked
                if ($website->last_checked) {
                    try {
                        $last = Carbon::parse($website->last_checked);
                        if ($last->diffInMinutes($now) < $intervalMinutes) {
                            continue;
                        }
                    } catch (\Throwable $e) {
                        // kalau format last_checked aneh, tetap lanjut cek
                    }
                }

                $oldStatus = (int) $website->status;

                // 1) Cek HTTP + response time
                $isUp = false;
                $rtMs = null;

                try {
                    $start = microtime(true);
                    $res = Http::timeout(10)->get($website->url);
                    $rtMs = (int) round((microtime(true) - $start) * 1000);

                    // Anggap UP jika 2xx atau 3xx
                    $isUp = $res->status() >= 200 && $res->status() < 400;
                } catch (\Throwable $e) {
                    $isUp = false;
                    $rtMs = null;
                }

                // 2) Cek SSL expiry (hanya untuk https)
                $sslExpiredAt = $this->getSslExpiryDate($website->url);

                // 3) Update tabel websites
                $website->update([
                    'status'        => $isUp ? 1 : 0,
                    'response_time' => $rtMs,
                    'ssl_expired_at'=> $sslExpiredAt,   // bisa null jika gagal / bukan https
                    'last_checked'  => now(),
                ]);

                // 4) Insert log ke monitoring_logs
                MonitoringLog::create([
                    'website_id'    => $website->id,
                    'status'        => $isUp ? 1 : 0,
                    'response_time' => $rtMs,
                    'checked_at'    => now(),
                ]);

                $count++;

                // 5) Notifikasi downtime/recovery jika status berubah
                if ($oldStatus !== (int)($isUp ? 1 : 0)) {

                    $type = $isUp ? 'recovery' : 'downtime';

                    $statusText = $isUp ? 'ONLINE' : 'DOWN';
                    $title = $isUp ? "✅ Website Online Alert!" : "⚠️ Website Down Alert!";
                    $statusIcon = $isUp ? "📈Status: ONLINE" : "📉Status: DOWN";

                    $msg = $title . "\n\n"
                        . "📝Nama: {$website->name}\n"
                        . "🌐URL: {$website->url}\n"
                        . "⏳Waktu: " . now()->format('Y-m-d H:i:s') . "\n"
                        . $statusIcon;

                    $this->logNotification($website->id, $type, $msg);
                }

                // 6) Notifikasi SSL H-xx (hindari spam: max 1x/hari)
                if ($sslExpiredAt) {
                    $daysLeft = now()->startOfDay()
                        ->diffInDays(Carbon::parse($sslExpiredAt)->startOfDay(), false);

                    if ($daysLeft >= 0 && $daysLeft <= $sslWarnDays) {
                        $sentToday = Notification::query()
                            ->where('website_id', $website->id)
                            ->where('type', 'ssl')
                            ->whereDate('sent_at', now()->toDateString())
                            ->exists();

                        if (! $sentToday) {
                            $msg = "⚠️ SSL: {$website->name} akan expired {$sslExpiredAt} (H-{$daysLeft})";
                            $this->logNotification($website->id, 'ssl', $msg);
                        }
                    }
                }
            }
        });

        $this->info("Done. Checked {$count} website(s).");
        return self::SUCCESS;
    }

    /**
     * Simpan notifikasi ke tabel notifications.
     * (Kalau nanti kamu mau tambah kirim Telegram, tinggal tambah di sini)
     */
    private function logNotification(int $websiteId, string $type, string $message): void
    {
        Notification::create([
            'website_id' => $websiteId,
            'type'       => $type,
            'message'    => $message,
            'sent_at'    => now(),
        ]);

        // Kirim Telegram untuk downtime/recovery (dan ssl kalau mau)
        if (in_array($type, ['downtime', 'recovery', 'ssl'], true)) {
            $this->sendTelegram($message);
        }
    }

    private function sendTelegram(string $message): void
    {
        $token  = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        if (! $token || ! $chatId) {
            return;
        }

        try {
            Http::timeout(10)
                ->asForm()
                ->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'disable_web_page_preview' => true,
                ]);
        } catch (\Throwable $e) {
            // Jangan bikin command gagal cuma karena Telegram error
            Log::warning('Telegram sendMessage failed: '.$e->getMessage());
        }
    }

    /**
     * Ambil tanggal expired sertifikat SSL dengan SNI (penting untuk banyak domain modern).
     */
    private function getSslExpiryDate(string $url): ?string
    {
        $parts = parse_url($url);
        $scheme = $parts['scheme'] ?? null;
        $host   = $parts['host'] ?? null;

        if ($scheme !== 'https' || ! $host) return null;

        try {
            $context = stream_context_create([
                'ssl' => [
                    'capture_peer_cert' => true,
                    'verify_peer' => false,
                    'verify_peer_name' => false,

                    // WAJIB: SNI
                    'SNI_enabled' => true,
                    'peer_name'   => $host,
                ],
            ]);

            $client = @stream_socket_client(
                "ssl://{$host}:443",
                $errno,
                $errstr,
                10,
                STREAM_CLIENT_CONNECT,
                $context
            );

            if (! $client) return null;

            $params = stream_context_get_params($client);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            if (! $cert) return null;

            $parsed = openssl_x509_parse($cert);
            $validTo = $parsed['validTo_time_t'] ?? null;
            if (! $validTo) return null;

            return Carbon::createFromTimestamp($validTo)->toDateString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
