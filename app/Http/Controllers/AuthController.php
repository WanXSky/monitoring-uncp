<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1) Validasi input + captcha wajib
        $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
            'g-recaptcha-response' => ['required'],
        ], [
            'g-recaptcha-response.required' => 'Silakan centang reCAPTCHA terlebih dahulu.',
        ]);

        $secret = config('services.recaptcha.secret_key');

        if (! $secret) {
            return back()
                ->withErrors(['g-recaptcha-response' => 'Secret key reCAPTCHA belum terbaca. Cek config/services.php dan .env'])
                ->onlyInput('email');
        }

        // 2) Verifikasi reCAPTCHA (tahan banting: timeout pendek + retry + paksa IPv4 + fallback)
        try {
            $verify = Http::asForm()
                ->timeout(10)
                ->connectTimeout(5)
                ->retry(2, 300)
                ->withOptions([
                    // Mengurangi kasus timeout random karena IPv6 bermasalah
                    'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                ])
                ->post('https://www.google.com/recaptcha/api/siteverify', [
                    'secret'   => $secret,
                    'response' => $request->input('g-recaptcha-response'),
                    'remoteip' => $request->ip(),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('reCAPTCHA google.com timeout, try recaptcha.net', ['err' => $e->getMessage()]);

            try {
                $verify = Http::asForm()
                    ->timeout(10)
                    ->connectTimeout(5)
                    ->retry(2, 300)
                    ->withOptions([
                        'curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4],
                    ])
                    ->post('https://www.recaptcha.net/recaptcha/api/siteverify', [
                        'secret'   => $secret,
                        'response' => $request->input('g-recaptcha-response'),
                        'remoteip' => $request->ip(),
                    ]);
            } catch (ConnectionException $e2) {
                Log::error('reCAPTCHA verify failed (both endpoints)', ['err' => $e2->getMessage()]);

                return back()
                    ->withErrors(['g-recaptcha-response' => 'Server tidak bisa menghubungi reCAPTCHA. Coba lagi beberapa saat.'])
                    ->onlyInput('email');
            }
        }

        if (! $verify->successful() || ! ($verify->json('success') ?? false)) {
            Log::warning('reCAPTCHA not success', ['errors' => $verify->json('error-codes')]);

            return back()
                ->withErrors(['g-recaptcha-response' => 'Verifikasi reCAPTCHA gagal. Coba lagi.'])
                ->onlyInput('email');
        }

        // 3) Login normal (email & password saja)
        $cred = $request->only('email', 'password');

        if (Auth::attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()
                ->route('dashboard')
                ->with('welcome_popup', [
                    'name' => Auth::user()->name,
                ]);
        }

        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
