<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class NOWPaymentsController extends Controller
{
    /**
     * Exibe o formulário de doação com criptomoedas.
     */
    public function showCryptoForm()
    {
        return view('donations.crypto');
    }

    /**
     * Passo 1: Cria a fatura na NOWPayments.
     * Lógica REFEITA para seguir o padrão do seu PayPalController.
     */
    public function createDonation(Request $request)
    {
        // 1. VERIFICAÇÃO DE SESSÃO (Igual ao PayPalController)
        $userid = session('astrocp_user.userid');
        if (!$userid) {
            // Usando as mesmas rotas de redirecionamento do seu PayPal
            return redirect('/donations/payment-failed')->with('error', 'User session expired. Please log in again.');
        }

        // 2. BUSCA DO ACCOUNT_ID (Igual ao PayPalController)
        $user = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();
        if (!$user || !isset($user->account_id)) {
            return redirect('/donations/payment-failed')->with('error', 'Unable to find account information.');
        }
        $accountId = $user->account_id;

        // 3. Obtenção dos dados do formulário (agora que sabemos que o usuário é válido)
        // Removido o Request::validate para usar o fluxo manual
        $amount = number_format((float) $request->input('amount', 20.00), 2, '.', '');
        $payCurrency = $request->input('pay_currency');

        if (empty($payCurrency)) {
            return back()->with('error', 'Please select a cryptocurrency.');
        }

        // 4. CHAMADA À API DA NOWPAYMENTS (Lógica mantida)
        $response = Http::withHeaders([
            'x-api-key' => config('services.nowpayments.key'),
        ])->post(config('services.nowpayments.url') . '/invoice', [
            'price_amount'     => $amount,
            'price_currency'   => 'usd',
            'pay_currency'     => $payCurrency,
            'ipn_callback_url' => route('nowpayments.webhook'),
            'success_url'      => url('/donations/payment-successful'), // Rota de sucesso genérica
            'cancel_url'       => url('/donation/payment-cancelled'),   // Rota de cancelamento genérica
        ]);

        // 5. TRATAMENTO DE FALHA NA API (Igual ao PayPalController)
        if ($response->failed()) {
            Log::error('Falha ao criar fatura na NOWPayments', [
                'account_id' => $accountId,
                'response' => $response->body(),
            ]);
            return redirect('/donations/payment-failed')->with('error', 'Failed to create payment invoice with our provider.');
        }

        $paymentData = $response->json();
        $invoiceUrl = $paymentData['invoice_url'] ?? null;
        $paymentId = $paymentData['id'] ?? null;
        $orderId = 'astro-np-' . $paymentId; // Criando um order_id único com o payment_id

        if (!$invoiceUrl || !$paymentId) {
            return redirect('/donations/payment-failed')->with('error', 'Invalid response from payment provider.');
        }

        // 6. SALVAR DOAÇÃO COMO "PENDING" (Igual ao PayPalController, feito APÓS a chamada da API)
        // Agora inserimos tudo de uma vez, de forma mais eficiente.
        DB::connection('ragnarok')->table('donations_np')->insert([
            'account_id' => $accountId,
            'amount_usd' => $amount,
            'status' => 'pending',
            'order_id' => $orderId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("Fatura NOWPayments criada com sucesso. Payment ID: {$paymentId}, Account ID: {$accountId}");

        // 7. REDIRECIONAR O USUÁRIO (Igual ao PayPalController)
        return redirect()->away($invoiceUrl);
    }

    /**
     * Passo 2: Recebe e processa o webhook da NOWPayments.
     * Função já estava boa, apenas uma pequena correção.
     */
    public function webhook(Request $request)
{
    $signature = $request->header('x-nowpayments-sig');
    $ipnSecret = config('services.nowpayments.ipn_secret');

    if (!$this->validateWebhookSignature($request->getContent(), $signature, $ipnSecret)) {
        Log::warning('Assinatura de webhook da NOWPayments inválida.');
        return response()->json(['error' => 'Invalid signature'], 401);
    }

    $data = $request->all();
    Log::info('Webhook da NOWPayments recebido e validado', $data);

    if (($data['payment_status'] ?? null) === 'finished') {
        $invoiceId = $data['invoice_id'] ?? null;
        $paymentId = $data['payment_id'] ?? null;

        if (!$invoiceId) {
            Log::warning('Webhook da NOWPayments sem invoice_id, não é possível encontrar a doação.');
            return response()->json(['error' => 'Missing invoice ID'], 400);
        }

        // <<< A MÁGICA ESTÁ AQUI >>>
        // Reconstruímos o order_id que salvamos no nosso banco de dados
        $orderIdToFind = 'astro-np-' . $invoiceId;

        // A busca agora é feita pelo order_id, na conexão correta.
        $donation = DB::connection('ragnarok')->table('donations_np')
            ->where('order_id', $orderIdToFind)
            ->first();

        if (!$donation) {
            Log::warning("Doação não encontrada para o order_id: {$orderIdToFind} (derivado do invoice_id: {$invoiceId})");
            return response()->json(['error' => 'Donation not found'], 404);
        }

        if ($donation->status === 'success') {
            Log::info("Webhook para a doação {$donation->id} (order_id: {$orderIdToFind}) já foi processado.");
            return response()->json(['message' => 'Event already processed'], 200);
        }

        $accountId = $donation->account_id;
        $usdAmount = $donation->amount_usd;
        $conversionRate = config('services.paypal.conversion_rate', 1000);
        $credits = (int) floor(floatval($usdAmount) * $conversionRate);

        $this->addCreditsToAccount($accountId, $credits);

        // Atualizamos o registro com o status e também com o payment_id para referência
        DB::connection('ragnarok')->table('donations_np')
            ->where('id', $donation->id)
            ->update([
                'status' => 'success',
                'credits' => $credits,
                'nowpayments_payment_id' => $paymentId, // Agora salvamos o payment_id
                'updated_at' => now(),
            ]);

        Log::info("Doação via NOWPayments processada: Doação ID {$donation->id}, Order ID {$orderIdToFind}, Conta {$accountId}, USD {$usdAmount}, Créditos {$credits}");
        return response()->json(['message' => 'Payment processed successfully']);
    }

    return response()->json(['message' => 'Event ignored'], 200);
}
    /**
     * Valida a assinatura do webhook.
     */
    private function validateWebhookSignature(?string $payload, ?string $signature, ?string $ipnSecret): bool
    {
        if (!$payload || !$signature || !$ipnSecret) {
            return false;
        }
        $expectedSignature = hash_hmac('sha512', $payload, $ipnSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Adiciona créditos à conta do usuário.
     */
    private function addCreditsToAccount(int $accountId, int $credits)
{
    $existing = DB::connection('ragnarok')->table('acc_reg_num')
        ->where('account_id', $accountId)
        ->where('key', '#CASHPOINTS')
        ->first();

    if ($existing) {
        // CORREÇÃO: Usamos os mesmos 'where' para encontrar e para atualizar.
        DB::connection('ragnarok')->table('acc_reg_num')
            ->where('account_id', $accountId)
            ->where('key', '#CASHPOINTS')
            ->increment('value', $credits);
    } else {
        // A lógica de inserir um novo registro já estava correta.
        DB::connection('ragnarok')->table('acc_reg_num')->insert([
            'account_id' => $accountId,
            'key' => '#CASHPOINTS',
            'value' => $credits,
        ]);
    }
}
}