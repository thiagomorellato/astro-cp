<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function deleteChar(Request $request)
{
    if (!session()->has('astrocp_user')) {
        return redirect()->route('login')->with('error', 'You must be logged in.');
    }

    $user = session('astrocp_user');
    $inputPassword = $request->input('password');
    $charName = $request->input('char_name');

    // Busca o usuário no banco
    $userData = DB::connection('ragnarok')
        ->table('login')
        ->where('account_id', $user['account_id'])
        ->first();

    if (!$userData) {
        return back()->with('error', 'User not found.');
    }

    // Checa senha (md5 padrão do rAthena)
    if (md5($inputPassword) !== $userData->user_pass) {
        return back()->with('error', 'Incorrect password.');
    }

    // Verifica se o char pertence à conta
    $char = DB::connection('ragnarok')
        ->table('char')
        ->where('name', $charName)
        ->where('account_id', $user['account_id'])
        ->first();

    if (!$char) {
        return back()->with('error', 'Character not found.');
    }

    // Deleta o personagem
    DB::connection('ragnarok')
        ->table('char')
        ->where('char_id', $char->char_id)
        ->delete();

    return back()->with('success', 'Character deleted.');
}

}

