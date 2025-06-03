<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class CashShopController extends Controller
{
    private $tabs = ['New', 'Hot', 'Limited', 'Rental', 'Permanent', 'Scrolls', 'Consumables', 'Other', 'Sale'];

    public function index()
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
        $header = fgetcsv($file); 

        DB::connection('ragnarok')->table('items_cash_db')->truncate(); 

        while (($data = fgetcsv($file)) !== FALSE) {
            if (count($data) >= 3) { // Garante que há dados suficientes
                DB::connection('ragnarok')->table('items_cash_db')->insert([
                    'Id' => $data[0],        // ID do item
                    'AegisName' => $data[1], // Nome Aegis
                    'Name' => $data[2],      // Nome legível
                ]);
            }
        }

        fclose($file);

        return redirect()->route('cash.shop')->with('success', 'Items imported successfully from CSV to items_cash_db!');
    }

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

        $tab = $request->query('tab', $this->tabs[0] ?? 'New');
        if (!in_array($tab, $this->tabs)) {
            $tab = $this->tabs[0] ?? 'New';
        }

        $perPage = 16;
        $page = (int) $request->query('page', 1);
        if ($page < 1) $page = 1;
        $offset = ($page - 1) * $perPage;

        $query = DB::connection('ragnarok')
            ->table('cash_shop')
            ->where('tab', $tab);

        $total = $query->count();

        // 'id' na tabela cash_shop é o item_id.
        $items = $query->orderBy('id') // Ordena pelo item_id (coluna 'id')
            ->offset($offset)
            ->limit($perPage)
            ->get(['id', 'aegisname', 'price', 'tab', 'name']); // 'name' é o nome legível do item

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
        $userid = Session::get('astrocp_user.userid');
        if (!$userid) {
            return redirect('/login')->with('error', 'Unauthorized access.');
        }
        $groupId = DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->value('group_id');
        if ($groupId != 99) {
            return redirect('/user')->with('error', 'Forbidden access.');
        }

        $tabsFromDb = DB::connection('ragnarok')
            ->table('cash_shop')
            ->distinct()
            ->orderBy('tab')
            ->pluck('tab');

        $body = [];
        foreach ($tabsFromDb as $tab) {
            $items = DB::connection('ragnarok')
                ->table('cash_shop')
                ->where('tab', $tab)
                ->orderBy('id') // Ordena por item_id (coluna 'id')
                ->get(); // Pega todas as colunas, incluindo 'id' (item_id)

            $itemsArray = [];
            foreach ($items as $item) {
                if (!empty($item->aegisname) && isset($item->price) && $item->price >= 0) {
                    $itemEntry = [
                        // 'Id' => (int) $item->id, // Adiciona o ID numérico do item (item_id)
                        'Item' => $item->aegisname, // Nome Aegis para o emulador
                        'Price' => (int) $item->price,
                    ];
                    // Adicionar 'Name' se o formato do YAML do seu emulador suportar/usar
                    // if (!empty($item->name)) {
                    //    $itemEntry['Name'] = $item->name;
                    // }
                    $itemsArray[] = $itemEntry;
                }
            }

            if (!empty($itemsArray)) {
                $body[] = [
                    'Tab' => $tab,
                    'Items' => $itemsArray,
                ];
            }
        }

        if (empty($body)) {
            return back()->with('warning', 'No items found to export.');
        }

        $yamlData = [
            'Header' => [
                'Type' => 'ITEM_CASH_DB',
                'Version' => 1, 
            ],
            'Body' => $body
        ];

        $dumper = new Dumper(2);
        $yamlString = $dumper->dump($yamlData, PHP_INT_MAX, 0, Yaml::DUMP_OBJECT_AS_MAP | Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);


        $localTempPath = storage_path('app/item_cash_temp.yml');
        file_put_contents($localTempPath, $yamlString);

        $sshUser = env('SCP_USER', 'root');
        $sshHost = env('SCP_HOST', '159.203.42.146');
        $remotePath = env('SCP_REMOTE_PATH', '/root/astroremote/db/import/item_cash.yml');
        $sshKeyPath = env('SCP_KEY_PATH', '/var/www/.ssh/id_ed25519_scp');

        if (!file_exists($sshKeyPath)) {
            \Log::error('SCP Failed: SSH Key not found at ' . $sshKeyPath);
            if (file_exists($localTempPath)) unlink($localTempPath);
            return back()->with('error', 'Falha ao enviar: Chave SSH não encontrada. Verifique a configuração.');
        }
        
        $scpCommand = [
            'scp',
            '-i', $sshKeyPath,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'UserKnownHostsFile=/dev/null',
            '-o', 'BatchMode=yes', // Evita prompts de senha
            $localTempPath,
            sprintf('%s@%s:%s', $sshUser, $sshHost, $remotePath)
        ];

        $process = new Process($scpCommand);

        try {
            $process->mustRun();
            unlink($localTempPath);
            return redirect()->route('cash.shop')->with('success', 'Arquivo YAML exportado e enviado para o servidor com sucesso!');
        } catch (ProcessFailedException $exception) {
            if (file_exists($localTempPath)) {
                unlink($localTempPath);
            }
            \Log::error('SCP Failed: ' . $exception->getMessage() . ' Output: ' . $process->getErrorOutput() . ' CMD: ' . $process->getCommandLine());
            return back()->with('error', 'Falha ao enviar o arquivo YAML para o servidor: ' . $exception->getMessage());
        }
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

        if (!in_array($tab, $this->tabs)) {
            return back()->with('error', 'Invalid tab selected.');
        }
        if (empty($raw)) {
            return back()->with('warning', 'No items provided to add.');
        }
        
        $pairs = explode(',', $raw);
        $inserted = 0;
        $errors = [];

        foreach ($pairs as $pair) {
            $parts = explode(':', trim($pair));
            if (count($parts) !== 2) {
                $errors[] = "Formato inválido: '$pair'. Use ID:PREÇO.";
                continue;
            }

            $itemId = trim($parts[0]);
            $price = trim($parts[1]);

            if (!is_numeric($itemId) || (int)$itemId <= 0) {
                $errors[] = "ID de item inválido: '$itemId' em '$pair'.";
                continue;
            }
            if (!is_numeric($price) || (int)$price < 0) {
                $errors[] = "Preço inválido: '$price' em '$pair'.";
                continue;
            }

            // Pega detalhes do item da tabela 'items_cash_db'
            $itemDetails = DB::connection('ragnarok')
                ->table('items_cash_db') 
                ->where('Id', (int)$itemId) // 'Id' é a coluna com o ID do item em items_cash_db
                ->first(['AegisName', 'Name']);

            if ($itemDetails && !empty($itemDetails->AegisName)) {
                // Verifica se o item (pelo item_id, que é a coluna 'id' em cash_shop) já existe NA MESMA ABA
                $exists = DB::connection('ragnarok')->table('cash_shop')
                    ->where('tab', $tab)
                    ->where('id', (int)$itemId) // 'id' na cash_shop é o item_id
                    ->exists();

                if ($exists) {
                    $errors[] = "Item ID ".(int)$itemId." (".$itemDetails->AegisName.") já existe na aba '$tab'.";
                    continue;
                }

                DB::connection('ragnarok')->table('cash_shop')->insert([
                    'id' => (int)$itemId, // Coluna 'id' na cash_shop armazena o item_id
                    'tab' => $tab,
                    'aegisname' => $itemDetails->AegisName,
                    'name' => $itemDetails->Name, // Nome legível do item
                    'price' => (int)$price,
                ]);
                $inserted++;
            } else {
                $errors[] = "Item ID ".(int)$itemId." não encontrado em items_cash_db ou não possui AegisName.";
            }
        }
        
        $message = "$inserted items added to $tab tab.";
        if (!empty($errors)) {
            // Constrói a mensagem de erro para o 'with'
            $errorString = implode(' ', $errors);
            return back()->withInput()->with('error', "$message Some items had issues: $errorString");
        }

        return redirect()->route('cash.shop')->with('success', $message);
    }

    /**
     * Remove um item específico (identificado por itemId) de uma aba específica (tabName) da cash shop.
     * A coluna 'id' na tabela 'cash_shop' armazena o itemId.
     */
    public function destroyItemFromTab(Request $request, $itemId, $tabName)
    {
        $userid = Session::get('astrocp_user.userid');
        if (!$userid) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'Authentication required.'], 401);
        }

        $groupId = DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->value('group_id');

        if ($groupId != 99) {
            return response()->json(['error' => 'Forbidden', 'message' => 'Administrator access required.'], 403);
        }

        // Validação do nome da aba
        if (!in_array($tabName, $this->tabs)) {
             return response()->json(['success' => false, 'message' => 'Invalid tab name specified for deletion.'], 400);
        }
        
        // Tenta deletar o item da aba especificada
        // 'id' na tabela cash_shop é o item_id
        $deleted = DB::connection('ragnarok')->table('cash_shop')
            ->where('id', $itemId) 
            ->where('tab', $tabName)
            ->delete();

        if ($deleted) {
            return response()->json(['success' => true, 'message' => "Item ID $itemId removed from tab '$tabName' successfully."]);
        }

        return response()->json(['success' => false, 'message' => "Item ID $itemId not found in tab '$tabName' or could not be removed."], 404);
    }

    public function clearTabItems(Request $request, $tabName)
    {
        $userid = Session::get('astrocp_user.userid');
        if (!$userid) {
            return response()->json(['error' => 'Unauthorized', 'message' => 'Authentication required.'], 401);
        }

        $groupId = DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->value('group_id');

        if ($groupId != 99) {
            return response()->json(['error' => 'Forbidden', 'message' => 'Administrator access required.'], 403);
        }
        
        if (!in_array($tabName, $this->tabs)) {
             return response()->json(['success' => false, 'message' => 'Invalid tab name specified.'], 400);
        }

        $deletedCount = DB::connection('ragnarok')->table('cash_shop')
            ->where('tab', $tabName)
            ->delete();

        if ($deletedCount > 0) {
            return response()->json(['success' => true, 'message' => "Successfully removed $deletedCount items from tab '$tabName'."]);
        }

        return response()->json(['success' => true, 'message' => "No items found in tab '$tabName' to remove."]);
    }
}