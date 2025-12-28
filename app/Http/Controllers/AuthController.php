<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 1) Validasi input + captcha wajib
        $cred = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
            'g-recaptcha-response' => ['required'],
        ], [
            'g-recaptcha-response.required' => 'Silakan centang reCAPTCHA terlebih dahulu.',
        ]);

        // 2) Verifikasi reCAPTCHA ke Google
        $secret = config('services.recaptcha.secret_key');

        $verify = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret'   => $secret,
            'response' => $request->input('g-recaptcha-response'),
            'remoteip' => $request->ip(), // opsional
        ]);

        if (! $verify->json('success')) {
            return back()
                ->withErrors(['g-recaptcha-response' => 'Verifikasi reCAPTCHA gagal. Coba lagi.'])
                ->onlyInput('email');
        }

        // 3) Lanjut login normal
        // Hapus captcha dari credential supaya tidak ikut dikirim ke Auth::attempt
        unset($cred['g-recaptcha-response']);

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
