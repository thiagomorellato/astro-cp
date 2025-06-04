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
public function updatePassword(Request $request)
    {
        // Verificação de sessão (padrão das suas funções)
        if (!session()->has('astrocp_user')) {
            return redirect()->route('astrocp.login.form')->with('error', 'You must be logged in.');
        }
        $user = session('astrocp_user');
        $userid = $user['userid'];

        // Validação dos campos do formulário
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:32|confirmed', // 'confirmed' valida se 'new_password_confirmation' é igual
        ]);

        // Busca o usuário no banco
        $userData = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();

        // Se, por algum motivo, o usuário da sessão não existir mais no banco
        if (!$userData) {
            session()->forget('astrocp_user'); // Limpa a sessão
            return redirect()->route('astrocp.login.form')->with('error', 'User not found. Please log in again.');
        }

        // Verifica a senha atual (usando md5 como no seu sistema)
        if (md5($request->current_password) !== $userData->user_pass) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.'])->withInput();
        }

        // Opcional: Verifica se a nova senha é diferente da antiga
        if (md5($request->new_password) === $userData->user_pass) {
            return back()->withErrors(['new_password' => 'The new password cannot be the same as the current password.'])->withInput();
        }

        // Atualiza a senha no banco
        DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->update(['user_pass' => md5($request->new_password)]);

        return back()->with('success', 'Password changed successfully!');
    }

    public function updateEmail(Request $request)
    {
        // Verificação de sessão
        if (!session()->has('astrocp_user')) {
            return redirect()->route('astrocp.login.form')->with('error', 'You must be logged in.');
        }
        $user = session('astrocp_user');
        $userid = $user['userid']; // Este é o 'userid' (username) do usuário logado

        // Busca o usuário no banco
        $userData = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();

        if (!$userData) {
            session()->forget('astrocp_user');
            return redirect()->route('astrocp.login.form')->with('error', 'User not found. Please log in again.');
        }

        // Validação dos campos
        $request->validate([
            'current_password' => 'required|string',
            'new_email' => [
                'required',
                'string',
                'email',
            ],
        ]);

        // Verifica a senha atual
        if (md5($request->current_password) !== $userData->user_pass) {
             return back()->withErrors(['current_password' => 'The provided current password does not match our records.'])->withInput();
        }

        // Opcional: Verifica se o novo e-mail é diferente do atual
        if (strtolower($request->new_email) === strtolower($userData->email)) {
             return back()->withErrors(['new_email' => 'The new email cannot be the same as the current email.'])->withInput();
        }

        // Atualiza o e-mail no banco
        DB::connection('ragnarok')->table('login')
            ->where('userid', $userid) 
            ->update(['email' => $request->new_email]);
        
        return back()->with('success', 'E-mail changed successfully!');
    }
}
