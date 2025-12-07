<?php

use App\Models\Customer;
use Illuminate\Support\Facades\Route;

Route::get('/pdf/customers/carwash', function () {
    $customers = Customer::with('user')
        ->where('carwash_total_visits', '>', 0)
        ->orderBy('carwash_last_visit_at', 'desc')
        ->get();

    return view('pdf.customers', [
        'customers' => $customers,
        'title' => 'Car Wash Customers',
        'type' => 'carwash',
        'date' => now()->format('d F Y'),
    ]);
})->name('pdf.customers.carwash');

Route::get('/pdf/customers/motorwash', function () {
    $customers = Customer::with('user')
        ->where('motorwash_total_visits', '>', 0)
        ->orderBy('motorwash_last_visit_at', 'desc')
        ->get();

    return view('pdf.customers', [
        'customers' => $customers,
        'title' => 'Motor Wash Customers',
        'type' => 'motorwash',
        'date' => now()->format('d F Y'),
    ]);
})->name('pdf.customers.motorwash');

Route::get('/pdf/customers/coffeeshop', function () {
    $customers = Customer::with('user')
        ->where('coffeeshop_total_visits', '>', 0)
        ->orderBy('coffeeshop_last_visit_at', 'desc')
        ->get();

    return view('pdf.customers', [
        'customers' => $customers,
        'title' => 'Coffee Shop Customers',
        'type' => 'coffeeshop',
        'date' => now()->format('d F Y'),
    ]);
})->name('pdf.customers.coffeeshop');
