<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use phpseclib3\Net\SFTP;
use phpseclib3\Crypt\PublicKeyLoader;

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
            if (!empty($item->AegisName) && $item->price > 0) {
                $itemsArray[] = [
                    'Item' => $item->AegisName,
                    'Price' => (int) $item->price,
                ];
            }
        }

        $body[] = [
            'Tab' => $tab,
            'Items' => $itemsArray,
        ];
    }

    // Gera o YAML
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

    // Salva localmente
    $localPath = base_path('item_cash.yml');
    file_put_contents($localPath, $yamlString);

    // Envia via SSH
    $privateKeyPath = '/etc/secrets/id_ed25519'; // Caminho onde o Render monta o secret file
    $privateKey = PublicKeyLoader::loadPrivateKey(file_get_contents($privateKeyPath));

    $sftp = new SFTP('ip-do-servidor'); // Coloque o IP ou hostname do seu servidor
    if (!$sftp->login('root', $privateKey)) {
        return response()->json(['error' => 'SSH login failed'], 500);
    }

    // Envia o arquivo
    $remotePath = '/root/astroremote/db/import/item_cash.yml';
    $uploadSuccess = $sftp->put($remotePath, $yamlString);

    if (!$uploadSuccess) {
        return response()->json(['error' => 'Failed to upload item_cash.yml to remote server'], 500);
    }

    return response()->json(['message' => 'item_cash.yml exported and uploaded successfully.']);
}
public function addItems(Request $request)
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

    $tab = $request->input('tab');
    $raw = $request->input('bulk_items');
    $pairs = explode(',', $raw);

    $inserted = 0;
    foreach ($pairs as $pair) {
        [$id, $price] = explode(':', trim($pair));

        $aegis = DB::connection('ragnarok')
            ->table('items_cash_db')
            ->where('Id', (int)$id)
            ->value('AegisName');

        if ($aegis) {
            DB::connection('ragnarok')->table('cash_shop')->insert([
                'id' => (int)$id,
                'tab' => $tab,
                'AegisName' => $aegis,
                'price' => (int)$price,
            ]);
            $inserted++;
        }
    }

    return redirect()->route('cash.shop')->with('success', "$inserted items added to $tab tab.");
}
public function destroy($id)
{
    \DB::connection('ragnarok')->table('cash_shop')->where('item_id', $id)->delete();
    return response()->json(['success' => true]);
}

}
