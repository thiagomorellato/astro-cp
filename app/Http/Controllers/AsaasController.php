<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AsaasController extends Controller
{
    /**
     * Cria a cobrança no Asaas e redireciona para a página de pagamento.
     */
    public function createDonation(Request $request)
    {
        // 1. Verifica sessão
        $userid = session('astrocp_user.userid');
        if (!$userid) {
            return redirect('/donations/payment-failed')->with('error', 'User session expired. Please log in again.');
        }

        // 2. Busca account_id no banco
        $user = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();
        if (!$user || !isset($user->account_id)) {
            return redirect('/donations/payment-failed')->with('error', 'Unable to find account information.');
        }
        $accountId = $user->account_id;

        // 3. Captura o valor enviado pelo form (BRL)
        $amount = number_format((float) $request->input('amount', 5.20), 2, '.', '');
        if ($amount < 1) {
            return back()->with('error', 'Invalid amount.');
        }

        // 4. Cria a cobrança no Asaas via API
        $asaasApiKey = config('services.asaas.api_key');
        $asaasUrl = 'https://www.asaas.com/api/v3/payments';

        $response = Http::withHeaders([
            'access_token' => $asaasApiKey,
            'Content-Type' => 'application/json',
        ])->post($asaasUrl, [
            'customer' => $this->getAsaasCustomerId($accountId), // Você deve implementar a lógica pra obter o customer_id do Asaas aqui
            'billingType' => 'PIX', // Pode ser PIX, BOLETO, CREDIT_CARD, etc.  
            'value' => $amount,
            'dueDate' => now()->addDays(1)->format('Y-m-d'), // Vencimento para amanhã
            'description' => "Donation for account ID {$accountId}",
            'externalReference' => "astro-asaas-{$accountId}-" . time(),
            'notificationDisabled' => false,
            'paymentDate' => null,
            'postalService' => false,
        ]);

        // 5. Tratamento da resposta
        if ($response->failed()) {
            Log::error('Failed to create Asaas payment', [
                'account_id' => $accountId,
                'response' => $response->body(),
            ]);
            return redirect('/donations/payment-failed')->with('error', 'Failed to create payment invoice with Asaas.');
        }

        $paymentData = $response->json();

        $paymentId = $paymentData['id'] ?? null;
        $paymentLink = $paymentData['invoiceUrl'] ?? $paymentData['paymentLink'] ?? null;

        if (!$paymentId || !$paymentLink) {
            Log::error('Invalid Asaas payment response', $paymentData);
            return redirect('/donations/payment-failed')->with('error', 'Invalid response from Asaas.');
        }

        // 6. Salva no banco a doação como PENDING
        DB::connection('ragnarok')->table('donations_as')->insert([
            'account_id' => $accountId,
            'amount_usd' => null,  // Não aplicável no PIX (opcional você pode criar um campo amount_brl se quiser)
            'amount_brl' => $amount, // Se quiser, adicione essa coluna no SQL abaixo
            'status' => 'pending',
            'asaas_payment_id' => $paymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("Asaas payment created successfully. Payment ID: {$paymentId}, Account ID: {$accountId}");

        // 7. Redireciona o usuário para a página oficial de pagamento Asaas
        return redirect()->away($paymentLink);
    }

    /**
     * Webhook para processar notificações do Asaas (esqueleto).
     * Você precisa configurar o endpoint no painel Asaas e implementar a lógica.
     */
    public function webhook(Request $request)
    {
        $data = $request->all();
        Log::info('Asaas webhook received', $data);

        // TODO: validar assinatura, validar evento e atualizar status no banco
        // Exemplo: atualizar a doação para 'success' quando pagamento confirmado

        return response()->json(['message' => 'Webhook received']);
    }

    /**
     * Método para pegar o customer_id do Asaas baseado no account_id do Ragnarok.
     * Você precisa implementar a sua lógica para relacionar usuário do seu sistema com o customer do Asaas.
     */
    private function getAsaasCustomerId(int $accountId): ?string
    {
        // Exemplo: buscar na tabela que você salvar o customer_id do Asaas para o usuário
        $customer = DB::connection('ragnarok')->table('asaas_customers')
            ->where('account_id', $accountId)
            ->first();

        return $customer->asaas_customer_id ?? null;
    }
}

