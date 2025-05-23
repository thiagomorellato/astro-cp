<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;

class CashShopController extends Controller
{
    private $tabs = ['New', 'Hot', 'Limited', 'Rental', 'Permanent', 'Scrolls', 'Consumables', 'Other', 'Sale'];

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

        // Apenas renderiza view vazia, ou pode enviar tabs para inicializar
        return view('cash_shop', ['tabs' => $this->tabs]);
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

    // Nova função para retornar items por tab e pagina
    public function showItemsByTab(Request $request)
    {
        $userid = Session::get('astrocp_user.userid');
        if (!$userid) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $groupId = DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->value('group_id');

        if ($groupId != 99) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $tab = $request->query('tab', 'New');
        if (!in_array($tab, $this->tabs)) {
            $tab = 'New';
        }

        $perPage = 16;
        $page = (int) $request->query('page', 1);
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $perPage;

        $total = DB::connection('ragnarok')
            ->table('cash_shop')
            ->where('tab', $tab)
            ->count();

        $items = DB::connection('ragnarok')
            ->table('cash_shop')
            ->where('tab', $tab)
            ->orderBy('id')
            ->offset($offset)
            ->limit($perPage)
            ->get(['id', 'aegisname', 'price']);

        return response()->json([
            'items' => $items,
            'total' => $total,
            'perPage' => $perPage,
            'currentPage' => $page,
            'totalPages' => ceil($total / $perPage),
            'tab' => $tab,
        ]);
    }
public function exportYaml()
{
    $tabs = DB::connection('ragnarok')
        ->table('cash_shop')
        ->distinct()
        ->pluck('tab');

    $body = [];

    foreach ($tabs as $tab) {
        $items = DB::connection('ragnarok')
            ->table('cash_shop')
            ->where('tab', $tab)
            ->get();

        $itemsArray = [];

        foreach ($items as $item) {
            if (!empty($item->aegisname) && $item->price > 0) {
                $itemsArray[] = [
                    'Item' => $item->aegisname,
                    'Price' => (int) $item->price,
                ];
            }
        }

        $body[] = [
            'Tab' => $tab,
            'Items' => $itemsArray,
        ];
    }

    $yamlString = "Header:\n";
    $yamlString .= "  Type: ITEM_CASH_DB\n";
    $yamlString .= "  Version: 1\n";
    $yamlString .= "Body:\n";

    foreach ($body as $entry) {
        $yamlString .= "  - Tab: {$entry['Tab']}\n";
        $yamlString .= "    Items:\n";
        foreach ($entry['Items'] as $item) {
            $yamlString .= "      - Item: {$item['Item']}\n";
            $yamlString .= "        Price: {$item['Price']}\n";
        }
    }

    $path = base_path('item_cash.yml');
    file_put_contents($path, $yamlString);

    return response()->download($path)->deleteFileAfterSend();
}
}
