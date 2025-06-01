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
        $sshHost = '159.203.42.146'; // IP ou hostname do servidor remoto
        
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
}
