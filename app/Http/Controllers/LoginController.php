<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        return view('login');
    }

    public function loginWithPin(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|min:10|max:15',
            'pin' => 'required|string|size:6',
        ]);

        $phone = $this->normalizePhone($request->phone);
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return back()->with('error', 'Nomor tidak terdaftar. Scan QR untuk check-in dulu.');
        }

        if ($user->isBanned()) {
            return back()->with('error', 'Akun Anda diblokir.');
        }

        $customer = $user->customer;

        if (!$customer) {
            return back()->with('error', 'Data customer tidak ditemukan. Silakan check-in ulang.');
        }

        if (!$customer->hasPin()) {
            return back()->with('error', 'PIN belum diatur. Silakan check-in untuk mendapatkan link dashboard dan atur PIN.');
        }

        if (!$customer->verifyPin($request->pin)) {
            return back()->with('error', 'PIN salah. Lupa PIN? Check-in ulang untuk reset.');
        }

        Auth::login($user, remember: true);
        $user->recordLogin($request->ip());

        return redirect()->route('customer.dashboard');
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

        $user = Auth::user();
        $request->session()->regenerate();

        if (!$user->isAdmin()) {
            Auth::logout();
            return back()->with('error', 'Access denied. Admin only.');
        }

        if ($user->isBanned()) {
            Auth::logout();
            return back()->with('error', 'Akun Anda diblokir.');
        }

        $user->recordLogin($request->ip());

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
}
