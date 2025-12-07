<?php

use App\Http\Controllers\CheckinController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SuccessController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing Page
Route::get('/', [HomeController::class, 'index'])->name('home');

// Check-in Flow (QR Scan)
Route::get('/checkin', [CheckinController::class, 'index'])->name('checkin');
Route::post('/checkin', [CheckinController::class, 'store'])->name('checkin.store');

// Success Page
Route::get('/success', [SuccessController::class, 'index'])->name('success');

// Custom Login (OTP & Admin)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login/otp/request', [LoginController::class, 'requestOtp'])->name('login.otp.request');
    Route::post('/login/otp/verify', [LoginController::class, 'verifyOtp'])->name('login.otp.verify');
    Route::post('/login/admin', [LoginController::class, 'adminLogin'])->name('login.admin');
});

// Magic Link Login (from WhatsApp)
Route::get('/dashboard/magic/{token}', [CustomerDashboardController::class, 'magicLogin'])
    ->name('customer.magic.login');

// Logout
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Customer Dashboard (Protected)
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('customer.dashboard');
    
    // Export Route
    Route::get('/admin/export/customers', \App\Http\Controllers\CustomerExportController::class)
        ->name('admin.export.customers')
        ->middleware(\App\Http\Middleware\CheckAdminRole::class);

    // Profile routes (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


Route::get('/qr/preview/{code}', [\App\Http\Controllers\QrCodeController::class, 'preview'])->name('qr.preview');
Route::get('/qr/download/{code}', [\App\Http\Controllers\QrCodeController::class, 'download'])->name('qr.download');

Route::get('/pdf/customers/carwash', function () {
    $customers = \App\Models\Customer::with('user')->where('carwash_total_visits', '>', 0)->orderBy('carwash_last_visit_at', 'desc')->get();
    return view('pdf.customers', ['customers' => $customers, 'title' => 'Car Wash Customers', 'type' => 'carwash', 'date' => now()->format('d F Y')]);
})->name('pdf.customers.carwash');

Route::get('/pdf/customers/motorwash', function () {
    $customers = \App\Models\Customer::with('user')->where('motorwash_total_visits', '>', 0)->orderBy('motorwash_last_visit_at', 'desc')->get();
    return view('pdf.customers', ['customers' => $customers, 'title' => 'Motor Wash Customers', 'type' => 'motorwash', 'date' => now()->format('d F Y')]);
})->name('pdf.customers.motorwash');

Route::get('/pdf/customers/coffeeshop', function () {
    $customers = \App\Models\Customer::with('user')->where('coffeeshop_total_visits', '>', 0)->orderBy('coffeeshop_last_visit_at', 'desc')->get();
    return view('pdf.customers', ['customers' => $customers, 'title' => 'Coffee Shop Customers', 'type' => 'coffeeshop', 'date' => now()->format('d F Y')]);
})->name('pdf.customers.coffeeshop');
