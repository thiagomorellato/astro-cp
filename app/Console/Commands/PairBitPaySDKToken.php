<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use BitPaySDK\Env;
use BitPaySDK\Exceptions\BitPayException;
use BitPaySDK\Model\Facade;
use BitPaySDK\Util\RESTcli\RESTcli;
use BitPaySDK\Client\TokenClient;
use BitPayKeyUtils\KeyHelper\PrivateKey;

class PairBitPaySDKToken extends Command
{
    /**
     * O comando agora só tem uma função principal: iniciar o pairing.
     * A geração da chave pode ser um comando separado ou parte do fluxo se não existir.
     * Para simplificar, vamos manter a lógica de carregar do .env.
     */
    protected $signature = 'bitpay:initiate-pairing';
    protected $description = 'Initiates the BitPay pairing process to get a Merchant pairing code.';

    public function handle()
    {
        $this->info("Initiating pairing process...");
        
        $privateKeyHex = env('BITPAY_SDK_PRIVATE_KEY_HEX_TEST');
        if (!$privateKeyHex) {
            $this->warn("Private Key not found in .env variable 'BITPAY_SDK_PRIVATE_KEY_HEX_TEST'. Generating a new one now.");
            try {
                $privateKeyObject = new PrivateKey();
                $privateKeyObject->generate();
                $privateKeyHex = $privateKeyObject->__toString();
                $this->info("A new key has been generated. Please add this to your .env file to use it consistently:");
                $this->comment("BITPAY_SDK_PRIVATE_KEY_HEX_TEST=\"{$privateKeyHex}\"");
            } catch (\Exception $e) {
                $this->error("Failed to generate new key: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        try {
            $privateKeyObject = new PrivateKey();
            $privateKeyObject->setHex($privateKeyHex);
            $clientId = $privateKeyObject->getPublicKey()->getSin()->__toString();
            $this->info('Private key loaded. Using SIN: ' . $clientId);

            $restCli = new RESTcli(Env::TEST, $privateKeyObject);
            $tokenClient = TokenClient::getInstance($restCli);

        } catch (\Exception $e) {
            $this->error("Initialization Error: " . $e->getMessage());
            return Command::FAILURE;
        }

        try {
            // --- A LÓGICA CORRETA ---
            // Usamos o TokenClient para solicitar um pairing code para a facade MERCHANT.
            // O SDK envia uma requisição para a API, que retorna um pairing code e cria uma
            // solicitação pendente no seu dashboard.
            $pairingCode = $tokenClient->create(Facade::MERCHANT);

        } catch (BitPayException $e) {
            $this->error('BitPay API Error while requesting pairing code: ' . $e->getMessage());
            $this->error("Full error details: \n" . $e->__toString());
            $this->comment("This could mean the method `create(Facade::MERCHANT)` is not correct. Please check TokenClient.php for a method like `requestPairingCode` or similar.");
            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('An unexpected error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->line("====================================================================================");
        $this->info('  PAIRING CODE REQUESTED SUCCESSFULLY!  ');
        $this->line("====================================================================================");
        $this->warn('ACTION REQUIRED:');
        $this->line("1. A pairing request has been sent to your BitPay Dashboard.");
        $this->info("   Your Pairing Code is: " . $pairingCode);
        $this->line("2. Go to your BitPay Dashboard (test.bitpay.com) -> Payment Settings -> API Keys.");
        $this->line("3. You should see a new pending request associated with your SIN: " . $clientId);
        $this->line("4. Click 'Approve' on that request.");
        $this->line("5. Your 'Merchant' token will now be listed on that page. You can copy it from there.");
        $this->line("------------------------------------------------------------------------------------");
        $this->info("The token you just approved is your 'BITPAY_SDK_API_TOKEN_TEST'. Add it to your .env file.");
        $this->line("====================================================================================");

        return Command::SUCCESS;
    }
}