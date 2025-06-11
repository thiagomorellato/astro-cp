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
        // Autenticação e Autorização (mantenha como está)
        $userid = Session::get('astrocp_user.userid');
        if (!$userid) {
            // Poderia retornar um erro JSON se for uma chamada AJAX, ou redirecionar
            return redirect('/login')->with('error', 'Unauthorized access.');
        }
        $groupId = DB::connection('ragnarok')->table('login')
            ->where('userid', $userid)
            ->value('group_id');
        if ($groupId != 99) {
            // Poderia retornar um erro JSON
            return redirect('/user')->with('error', 'Forbidden access.');
        }

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

            if (!empty($itemsArray)) { // Só adiciona a tab se tiver itens
                $body[] = [
                    'Tab' => $tab,
                    'Items' => $itemsArray,
                ];
            }
        }

        if (empty($body)) {
            return back()->with('warning', 'No items found to export.');
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

        // --- Salvar YAML e enviar via SCP ---
        $localTempPath = storage_path('app/item_cash_temp.yml'); // Salva temporariamente no storage do Laravel
        file_put_contents($localTempPath, $yamlString);

        // Configurações para SSH/SCP
        $sshUser = 'root'; // Usuário SSH no servidor remoto
        $sshHost = '159.203.15.99'; // IP ou hostname do servidor remoto
        
        // !!! IMPORTANTE: Defina o caminho completo onde o arquivo deve ser salvo no servidor REMOTO !!!
        // Exemplo: '/home/usuario_remoto/ragnarok/conf/item_cash.yml'
        // Exemplo: '/opt/gameserver/ragnarok/db/item_cash.yml'
        $remotePath = '/root/astroremote/db/import/item_cash.yml'; // MUDE ISSO!

        $sshKeyPath = '/var/www/.ssh/id_ed25519_scp'; // Caminho para a chave privada de www-data dentro do container

        // Comando SCP
        // -i: especifica a chave de identidade (privada)
        // -o StrictHostKeyChecking=no: não pergunta sobre a autenticidade do host
        // -o UserKnownHostsFile=/dev/null: não usa nem atualiza o known_hosts (cuidado em ambientes produtivos sem controle)
        $scpCommand = [
            'scp',
            '-i', $sshKeyPath,
            '-o', 'StrictHostKeyChecking=no',
            '-o', 'UserKnownHostsFile=/dev/null',
            $localTempPath, // Arquivo local
            sprintf('%s@%s:%s', $sshUser, $sshHost, $remotePath) // Destino remoto: user@host:path
        ];

        $process = new Process($scpCommand);
        
        try {
            $process->mustRun(); // Executa o comando; lança exceção em caso de erro

            unlink($localTempPath); // Remove o arquivo temporário local após o envio

            return redirect()->route('cash.shop')->with('success', 'Arquivo YAML exportado e enviado para o servidor com sucesso!');
        
        } catch (ProcessFailedException $exception) {
            // Limpa o arquivo temporário mesmo em caso de falha, se existir
            if (file_exists($localTempPath)) {
                unlink($localTempPath);
            }
            
            // Logar o erro é uma boa prática: \Log::error($exception->getMessage());
            return back()->with('error', 'Falha ao enviar o arquivo YAML para o servidor: ' . $exception->getMessage());
        }
        // --- Fim da seção SCP ---
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