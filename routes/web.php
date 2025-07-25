<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AstrocpLoginController;
use App\Http\Controllers\PayPalController;
use App\Http\Controllers\PayPalSubscriptionController;
use App\Http\Controllers\PayPalSubscriptionWebhookController;
use App\Http\Controllers\CashShopController;
use App\Http\Controllers\NOWPaymentsController;

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

Route::get('/donations/payment-successful', function () {
    return view('donations.payment_successful');
})->name('donations.payment_successful');

Route::get('/donations/payment-cancelled', function () {
    return view('donations.payment_failed');
})->name('donations.payment_failed');

use App\Http\Controllers\PayPalWebhookController;

Route::post('/paypal/webhook', [PayPalWebhookController::class, 'handle'])->name('paypal.webhook');

use App\Http\Controllers\UserController;

Route::get('/user', [UserController::class, 'index'])->name('user');
Route::post('/char/delete', [UserController::class, 'deleteChar'])->name('char.delete');
Route::post('/char/reset-position', [UserController::class, 'resetPosition'])->name('char.resetPosition');
Route::post('/char/reset-look', [UserController::class, 'resetLook'])->name('char.resetLook');
Route::post('/account/change-password', [UserController::class, 'updatePassword'])->name('account.changePassword');
Route::post('/account/change-email', [UserController::class, 'updateEmail'])->name('account.changeEmail');


Route::post('/paypal/subscribe/create', [PayPalSubscriptionController::class, 'create']);
Route::post('/paypal/subscribe', [PayPalSubscriptionWebhookController::class, 'handle']);


Route::get('/cash_shop', [CashShopController::class, 'index'])->name('cash.shop');
Route::post('/cash-shop/import', [CashShopController::class, 'import'])->name('cash.shop.import');
// Rota API para buscar os itens por tab e página
Route::get('/cash-shop/items', [CashShopController::class, 'showItemsByTab'])->name('cash.shop.items');
Route::get('/cash-shop/export-yaml', [CashShopController::class, 'exportYaml'])->name('cash.shop.exportYaml');
Route::post('/cash-shop/add-items', [CashShopController::class, 'addItems'])->name('cash.shop.addItems');
// Rota de exclusão de item ajustada
Route::delete('/cash-shop/item/{itemId}/tab/{tabName}', [CashShopController::class, 'destroyItemFromTab'])->name('cash.shop.destroyItemFromTab');
// Rota para limpar todos os itens de uma aba (permanece a mesma)
Route::delete('/cash-shop/tab/{tabName}', [CashShopController::class, 'clearTabItems'])->name('cash.shop.clearTabItems');


Route::get('/donation/crypto', [NOWPaymentsController::class, 'showCryptoForm'])->name('donations.crypto.form');
Route::post('/donation/crypto', [NOWPaymentsController::class, 'createDonation'])->name('nowpayments.buy');
Route::post('/webhook/nowpayments', [NOWPaymentsController::class, 'webhook'])->name('nowpayments.webhook');


use App\Http\Controllers\AsaasController;

// Rota da página PIX (exibe a blade pix.blade.php)
Route::view('/donations/pix', 'donations.pix')->name('donations.asaas.form');

// Rota POST para criar a cobrança no Asaas via controller
Route::post('/donations/asaas/create', [AsaasController::class, 'createDonation'])->name('donations.asaas.create');
Route::post('/asaas/webhook', [AsaasController::class, 'webhook']);
