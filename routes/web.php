<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Auth\RagLoginController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

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


Route::get('/account', [RagLoginController::class, 'showLoginForm']);
Route::post('/account', [RagLoginController::class, 'login'])->name('rathena.login');
Route::get('/logout', [RagLoginController::class, 'logout'])->name('rathena.logout');

Route::get('/test-db-connection', function () {
    try {
        DB::connection('mysql_ragnarok')->getPdo();
        return 'Conexão com o banco Ragnarok OK!';
    } catch (\Exception $e) {
        return 'Erro ao conectar: ' . $e->getMessage();
    }
});