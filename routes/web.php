<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AstrocpLoginController;
use App\Http\Controllers\PayPalController;

Route::get('/', function () {
    return view('index');
});
Route::get('/downloads', function () {
    return view('downloads');
});
Route::get('/account', function () {
    return view('account');
});
Route::get('/donations', function () {
    return view('donations');
});
Route::get('/info', function () {
    return view('info');
});
Route::get('/vote', function () {
    return view('vote');
});

// Login form (aponta para account.blade.php mesmo)
Route::get('/login', function() {
    return view('account');
})->name('astrocp.login.form');

// Register form (nova view register.blade.php)
Route::get('/register', [AstrocpLoginController::class, 'showRegisterForm'])->name('astrocp.register.form');

// Register POST
Route::post('/register', [AstrocpLoginController::class, 'register'])->name('astrocp.register');

// Login POST
Route::post('/login', [AstrocpLoginController::class, 'login'])->name('astrocp.login');

// Logout POST
Route::post('/astrocp/logout', function () {
    session()->forget('astrocp_user');
    return redirect('/');
})->name('astrocp.logout');

// Página da doação PayPal
Route::view('/donations/paypal', 'donations.paypal')->name('donations.paypal');

Route::prefix('paypal')->name('paypal.')->group(function () {
    Route::get('/buy', [PayPalController::class, 'createOrder'])->name('buy');
    Route::get('/success', [PayPalController::class, 'captureOrder'])->name('success');
    Route::get('/cancel', [PayPalController::class, 'cancel'])->name('cancel');
    Route::get('/failed', [PayPalController::class, 'failed'])->name('failed');
});
