<?php

namespace App\Http\Controllers;

use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class LoginController extends Controller
{
    /**
     * Display the login page.
     */
    public function index()
    {
        return view('login');
    }

    /**
     * Request OTP for customer login.
     */
    public function requestOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
        ]);

        // Normalize phone number
        $normalizedPhone = $this->normalizePhone($request->phone);

        // Rate limiting: Max 3 OTP requests per hour per phone
        $key = 'otp-request:' . $normalizedPhone;
        
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);
            
            return back()->with('error', "Terlalu banyak permintaan. Coba lagi dalam {$minutes} menit.");
        }

        // Check if user exists
        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user) {
            return back()->with('error', 'Nomor tidak terdaftar. Silakan scan QR untuk check-in terlebih dahulu.');
        }

        // Generate and send OTP
        $otp = OtpCode::generate($normalizedPhone);
        
        // Send OTP via WhatsApp (placeholder)
        $this->sendOtpWhatsApp($normalizedPhone, $otp->otp_code);

        // Increment rate limiter
        RateLimiter::hit($key, 3600); // 1 hour

        return back()->with('success', 'Kode OTP telah dikirim ke WhatsApp Anda. Berlaku 5 menit.');
    }

    /**
     * Verify OTP and log in customer.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        $normalizedPhone = $this->normalizePhone($request->phone);

        // Verify OTP
        if (!OtpCode::verify($normalizedPhone, $request->otp_code)) {
            return back()->with('error', 'Kode OTP salah atau sudah kadaluarsa.');
        }

        // Find user
        $user = User::where('phone', $normalizedPhone)->first();

        if (!$user) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        // Log in user
        Auth::login($user, remember: true);

        // Redirect based on role
        if ($user->isAdmin()) {
            return redirect('/admin');
        }

        return redirect()->route('customer.dashboard');
    }

    /**
     * Admin login with email and password.
     */
    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        // Attempt login
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // Check if user is admin
            if (Auth::user()->isAdmin()) {
                return redirect()->intended('/admin');
            }

            // Not admin, logout
            Auth::logout();
            return back()->with('error', 'Access denied. Admin credentials required.');
        }

        return back()->with('error', 'Email atau password salah.');
    }

    /**
     * Logout user.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Normalize phone number to 62xxx format.
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (substr($phone, 0, 1) === '0') {
            $phone = '62' . substr($phone, 1);
        }

        if (substr($phone, 0, 2) !== '62') {
            $phone = '62' . $phone;
        }

        return $phone;
    }

    /**
     * Send OTP via WhatsApp.
     */
    private function sendOtpWhatsApp(string $phone, string $otp): void
    {
        $message = "🔐 Kode OTP Getwashed Loyalty kamu:\n\n*{$otp}*\n\nBerlaku 5 menit. Jangan bagikan ke siapapun!";
        
        $whatsappService = app(\App\Services\WhatsAppService::class);
        $whatsappService->sendMessage($phone, $message);
    }
}
