<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\OtpService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function __construct(
        private OtpService $otpService,
        private WhatsAppService $whatsappService
    ) {}

    public function index()
    {
        return view('login');
    }

    public function requestOtp(Request $request)
    {
        $request->validate(['phone' => 'required|string|min:10|max:15']);

        $phone = $this->normalizePhone($request->phone);

        if ($this->otpService->isRateLimited($phone)) {
            $minutes = ceil($this->otpService->getRemainingTime($phone) / 60);
            return back()->with('error', "Terlalu banyak permintaan. Coba lagi dalam {$minutes} menit.");
        }

        if (!$this->userExists($phone)) {
            return back()->with('error', 'Nomor tidak terdaftar. Scan QR untuk check-in dulu.');
        }

        $otp = $this->otpService->generate($phone);

        if (!$otp) {
            return back()->with('error', 'Gagal generate OTP. Coba lagi nanti.');
        }

        $this->sendOtp($phone, $otp->otp_code);

        return back()
            ->with('success', 'Kode OTP dikirim ke WhatsApp. Berlaku 5 menit.')
            ->with('phone', $request->phone);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string',
            'otp_code' => 'required|string|size:6',
        ]);

        $phone = $this->normalizePhone($request->phone);

        if (!$this->otpService->verify($phone, $request->otp_code)) {
            return back()->with('error', 'Kode OTP salah atau kadaluarsa.');
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return back()->with('error', 'User tidak ditemukan.');
        }

        Auth::login($user, remember: true);

        return $user->isAdmin()
            ? redirect('/admin')
            : redirect()->route('customer.dashboard');
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->with('error', 'Email atau password salah.');
        }

        $request->session()->regenerate();

        if (!Auth::user()->isAdmin()) {
            Auth::logout();
            return back()->with('error', 'Access denied. Admin only.');
        }

        return redirect()->intended('/admin');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '62')) {
            return '62' . $phone;
        }

        return $phone;
    }

    private function userExists(string $phone): bool
    {
        return User::where('phone', $phone)->exists();
    }

    private function sendOtp(string $phone, string $otp): void
    {
        $message = "🔐 Kode OTP Getwashed Loyalty:\n\n*{$otp}*\n\nBerlaku 5 menit. Jangan bagikan!";
        $this->whatsappService->sendMessage($phone, $message);
    }
}
