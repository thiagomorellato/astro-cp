<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        if (!session()->has('astrocp_user')) {
            return redirect()->route('astrocp.login.form')->with('error', 'You must be logged in.');
        }

        $user = session('astrocp_user');
        $userid = $user['userid'];

        // Busca dados do usuário pelo userid para obter account_id e vip_time
        $userData = DB::connection('ragnarok')
            ->table('login')
            ->where('userid', $userid)
            ->first();

        if (!$userData) {
            return redirect()->route('login')->with('error', 'User not found.');
        }

        // Verifica se o usuário é VIP
        $isVip = $userData->vip_time != 0;

        // Busca personagens pelo account_id
        $characters = DB::connection('ragnarok')
            ->table('char')
            ->where('account_id', $userData->account_id)
            ->get();

        // Passa dados para a view
        return view('user', [
            'userData' => $userData,
            'characters' => $characters,
            'username' => $userid,
            'isVip' => $isVip,
        ]);
    }

    public function deleteChar(Request $request)
    {
        if (!session()->has('astrocp_user')) {
            return redirect()->route('login')->with('error', 'You must be logged in.');
        }

        $user = session('astrocp_user');
        $userid = $user['userid'];

        $inputPassword = $request->input('password');
        $charName = $request->input('char_name');

        // Busca usuário pelo userid para pegar account_id e senha
        $userData = DB::connection('ragnarok')
            ->table('login')
            ->where('userid', $userid)
            ->first();

        if (!$userData) {
            return back()->with('error', 'User not found.');
        }

        // Checa senha (md5 padrão do rAthena)
        if (md5($inputPassword) !== $userData->user_pass) {
            return back()->with('error', 'Incorrect password.');
        }

        // Verifica se o personagem pertence a esta conta
        $char = DB::connection('ragnarok')
            ->table('char')
            ->where('name', $charName)
            ->where('account_id', $userData->account_id)
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
    public function resetPosition(Request $request)
    {
        if (!session()->has('astrocp_user')) {
            return redirect()->route('login')->with('error', 'You must be logged in.');
        }

        $user = session('astrocp_user');
        $userid = $user['userid'];
        $charName = $request->input('char_name');

        $userData = DB::connection('ragnarok')
            ->table('login')
            ->where('userid', $userid)
            ->first();

        if (!$userData) {
            return back()->with('error', 'User not found.');
        }

        $char = DB::connection('ragnarok')
            ->table('char')
            ->where('name', $charName)
            ->where('account_id', $userData->account_id)
            ->first();

        if (!$char) {
            return back()->with('error', 'Character not found.');
        }

        // Atualiza a posição do personagem para Prontera (229, 309)
        DB::connection('ragnarok')
            ->table('char')
            ->where('char_id', $char->char_id)
            ->update([
                'last_map' => 'prontera',
                'last_x' => 229,
                'last_y' => 309,
            ]);

        return back()->with('success', 'Character position reset to Prontera.');
    }
    
    public function resetLook(Request $request)
    {
        if (!session()->has('astrocp_user')) {
            return redirect()->route('login')->with('error', 'You must be logged in.');
        }

        $user = session('astrocp_user');
        $userid = $user['userid'];
        $charName = $request->input('char_name');

        $userData = DB::connection('ragnarok')
            ->table('login')
            ->where('userid', $userid)
            ->first();

        if (!$userData) {
            return back()->with('error', 'User not found.');
        }

        $char = DB::connection('ragnarok')
            ->table('char')
            ->where('name', $charName)
            ->where('account_id', $userData->account_id)
            ->first();

        if (!$char) {
            return back()->with('error', 'Character not found.');
        }

        DB::connection('ragnarok')
            ->table('char')
            ->where('char_id', $char->char_id)
            ->update([
                'hair' => 1,
                'hair_color' => 0,
                'clothes_color' => 0,
            ]);

        return back()->with('success', 'Character look has been reset.');
    }

}
