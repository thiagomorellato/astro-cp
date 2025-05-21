<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AstrocpLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'userid' => 'required|string',
            'password' => 'required|string',
        ]);

        $userid = $request->input('userid');
        $password = $request->input('password');

        $user = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();

        if (!$user) {
            return back()->withErrors(['userid' => 'User not found']);
        }

        $inputHash = md5($password);

        if ($inputHash !== $user->user_pass) {
            return back()->withErrors(['password' => 'Invalid password']);
        }

        session(['astrocp_user' => ['userid' => $user->userid]]);

        return redirect('/');
    }

    public function showRegisterForm()
    {
        return view('register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'userid' => 'required|min:4|max:23|alpha_num|unique:ragnarok.login,userid',
            'password' => 'required|string|min:6|max:32|confirmed',
            'email' => 'required|email|max:39',
        ]);

        DB::connection('ragnarok')->table('login')->insert([
            'userid' => $request->userid,
            'user_pass' => md5($request->password),
            'sex' => 'M',
            'email' => $request->email,
            'character_slots' => '15'
        ]);

        return view('register_success');
    }
}
