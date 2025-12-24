<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Pastikan ada 1 row setting (id=1)
        $setting = Setting::firstOrCreate(
            ['id' => 1],
            ['check_interval' => 1, 'notify_ssl_days' => 7]
        );

        return view('settings.index', compact('user', 'setting'));
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $setting = Setting::firstOrCreate(
            ['id' => 1],
            ['check_interval' => 1, 'notify_ssl_days' => 7]
        );

        $updatedParts = [];

        /**
         * A) Update Pengaturan Umum (profile admin)
         * Kirim field: name, email, password, password_confirmation
         */
        $wantsProfileUpdate =
            $request->has('name') ||
            $request->has('email') ||
            $request->filled('password');

        if ($wantsProfileUpdate) {
            $profileData = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required', 'email', 'max:255',
                    Rule::unique('users', 'email')->ignore($user->id),
                ],
                'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            ]);

            $user->name = $profileData['name'];
            $user->email = $profileData['email'];

            if (!empty($profileData['password'])) {
                $user->password = Hash::make($profileData['password']);
            }

            $user->save();
            $updatedParts[] = 'Pengaturan Umum';
        }

        /**
         * B) Update Pengaturan Notifikasi / Monitoring
         * Kirim field: check_interval, notify_ssl_days
         */
        $wantsMonitoringUpdate =
            $request->has('check_interval') ||
            $request->has('notify_ssl_days');

        if ($wantsMonitoringUpdate) {
            $monitorData = $request->validate([
                'check_interval' => ['required', 'integer', 'min:1', 'max:60'],     // menit
                'notify_ssl_days' => ['required', 'integer', 'min:1', 'max:365'],  // hari
            ]);

            $setting->update([
                'check_interval' => $monitorData['check_interval'],
                'notify_ssl_days' => $monitorData['notify_ssl_days'],
            ]);

            $updatedParts[] = 'Pengaturan Notifikasi';
        }

        if (empty($updatedParts)) {
            return back()->with('status', 'Tidak ada perubahan yang disimpan.');
        }

        return back()->with('status', implode(' & ', $updatedParts) . ' berhasil disimpan.');
    }
}
