<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class CashShopController extends Controller
{
    public function index()
    {
        $userid = Session::get('astrocp_user.userid');

        if (!$userid) {
            return redirect('/login');
        }

        // Acessa banco 'ragnarok' e pega o group_id
        $groupId = DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->value('group_id');

        if ($groupId != 99) {
            return redirect('/user');
        }

        // Lê o CSV
        return view('cash_shop', ['items' => $items]);
    }
    public function import()
    {
        $userid = Session::get('astrocp_user.userid');

        if (!$userid) {
            return redirect('/login');
        }

        $groupId = DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->value('group_id');

        if ($groupId != 99) {
            return redirect('/user');
        }

        $csvPath = resource_path('csv/items.csv');

        if (!file_exists($csvPath)) {
            return back()->with('error', 'CSV file not found.');
        }

        $file = fopen($csvPath, 'r');
        $header = fgetcsv($file); // Skip header: Id,AegisName,Name

        DB::connection('ragnarok')->table('items_cash_db')->truncate(); // Limpa tabela antes

        while (($data = fgetcsv($file)) !== FALSE) {
            DB::connection('ragnarok')->table('items_cash_db')->insert([
                'Id' => $data[0],
                'AegisName' => $data[1],
                'Name' => $data[2],
            ]);
        }

        fclose($file);

        return redirect()->route('cash.shop')->with('success', 'Items imported successfully!');
    }
}
