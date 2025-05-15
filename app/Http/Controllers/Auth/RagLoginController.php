<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\RagnarokAccount;

class RagLoginController extends Controller
{
    public function showLoginForm()
    {
        return view('account');
    }

    public function login(Request $request)
    {
        $request->validate([
            'userid' => 'required|string',
            'password' => 'required|string',
        ]);

        $account = RagnarokAccount::where('userid', $request->userid)->first();

        if (!$account) {
            return back()->withErrors(['userid' => 'Account not found']);
        }

        $inputPassword = strtoupper(md5($request->password));

        if ($account->user_pass !== $inputPassword) {
            return back()->withErrors(['password' => 'Incorrect password']);
        }

        if ($account->state != 0) {
            return back()->withErrors(['userid' => 'Account is not active']);
        }

        Session::put('account_id', $account->account_id);
        Session::put('userid', $account->userid);

        return redirect('/dashboard');
    }

    public function logout()
    {
        Session::flush();
        return redirect('/account');
    }
}