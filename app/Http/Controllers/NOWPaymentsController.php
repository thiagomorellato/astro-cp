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
     * Passo 1: Cria a fatura de doação na NOWPayments.
     * Esta função agora cria um registro local ANTES de contatar a API.
     */
    public function createDonation(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5', // Valor mínimo de 5 USD, por exemplo
            'pay_currency' => 'required|string',
            'account_id' => 'required|integer|exists:ragnarok.login,account_id', // Valida se a conta existe
        ]);

        $amount = $request->input('amount');
        $accountId = $request->input('account_id');

        // 1. Crie um registro local na sua tabela para rastrear a doação.
        // A "fonte da verdade" agora é o seu banco de dados.
        $donationId = DB::table('donations_np')->insertGetId([
            'account_id' => $accountId,
            'amount_usd' => $amount,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        // 2. Crie um Order ID único para enviar à NOWPayments.
        $orderId = 'astro-' . $donationId;

        // Atualiza o registro com o order_id gerado
        DB::table('donations_np')->where('id', $donationId)->update(['order_id' => $orderId]);
        
        // 3. Chame a API da NOWPayments para criar a fatura.
        $response = Http::withHeaders([
            'x-api-key' => config('services.nowpayments.key'),
        ])->post(config('services.nowpayments.url') . '/invoice', [
            'price_amount'     => $amount,
            'price_currency'   => 'usd',
            'pay_currency'     => $request->input('pay_currency'),
            'order_id'         => $orderId, // Nosso ID único
            'ipn_callback_url' => route('nowpayments.webhook'),
            'success_url'      => url('/donation/success'),
            'cancel_url'       => url('/donation/cancel'),
        ]);

        // 4. Verifique a resposta da API.
        if ($response->successful()) {
            $paymentData = $response->json();
            
            // 5. Salve o ID de pagamento da NOWPayments no nosso registro local.
            // Isso é crucial para vincular o webhook à doação correta.
            DB::table('donations_np')->where('id', $donationId)->update([
                'nowpayments_payment_id' => $paymentData['id'] ?? null,
            ]);

            Log::info("Fatura NOWPayments criada com sucesso para a doação ID: {$donationId}");
            return redirect()->away($paymentData['invoice_url']);
        }

        // Se a API falhar, marque a doação como 'failed' e registre o erro.
        DB::table('donations_np')->where('id', $donationId)->update(['status' => 'failed']);
        Log::error('Falha ao criar fatura na NOWPayments', [
            'donation_id' => $donationId,
            'response' => $response->body(),
        ]);

        return back()->with('error', 'Não foi possível criar a fatura de pagamento. Tente novamente mais tarde.');
    }

    /**
     * Passo 2: Recebe e processa o webhook da NOWPayments.
     * Esta função foi completamente reescrita para ser segura e idempotente.
     */
    public function webhook(Request $request)
    {
        // 1. VERIFICAÇÃO DE SEGURANÇA: Validar a assinatura do webhook (CRÍTICO!)
        $signature = $request->header('x-nowpayments-sig');
        $ipnSecret = config('services.nowpayments.ipn_secret');

        if (!$this->validateWebhookSignature($request->getContent(), $signature, $ipnSecret)) {
            Log::warning('Assinatura de webhook da NOWPayments inválida.');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();
        Log::info('Webhook da NOWPayments recebido e validado', $data);

        // 2. Processe apenas os eventos de pagamento que nos interessam ('finished')
        if (($data['payment_status'] ?? null) === 'finished') {
            $paymentId = $data['payment_id'] ?? null;

            if (!$paymentId) {
                Log::warning('Webhook da NOWPayments sem payment_id.');
                return response()->json(['error' => 'Missing payment ID'], 400);
            }

            // 3. Encontre a doação no SEU banco de dados usando o ID do pagamento.
            $donation = DB::table('donations_np')
                ->where('nowpayments_payment_id', $paymentId)
                ->first();

            if (!$donation) {
                Log::warning("Doação não encontrada para o payment_id da NOWPayments: {$paymentId}");
                return response()->json(['error' => 'Donation not found'], 404);
            }

            // 4. VERIFICAÇÃO DE IDEMPOTÊNCIA: Garante que não processemos o mesmo evento duas vezes.
            if ($donation->status === 'success') {
                Log::info("Webhook para a doação {$donation->id} já foi processado.");
                return response()->json(['message' => 'Event already processed'], 200);
            }

            // 5. Use os dados do SEU banco de dados como fonte da verdade.
            $accountId = $donation->account_id;
            $usdAmount = $donation->amount_usd; // Usamos o valor que salvamos
            $conversionRate = config('services.paypal.conversion_rate', 1000); // Reutilize a config
            $credits = (int) floor(floatval($usdAmount) * $conversionRate);

            // 6. Credite a conta do usuário usando uma função encapsulada (mesma lógica do PayPal).
            $this->addCreditsToAccount($accountId, $credits);

            // 7. Atualize o status da doação no seu banco de dados para 'success'.
            DB::table('donations_np')->where('id', $donation->id)->update([
                'status' => 'success',
                'credits' => $credits,
                'updated_at' => now(),
            ]);

            Log::info("Doação via NOWPayments processada: Doação ID {$donation->id}, Conta {$accountId}, USD {$usdAmount}, Créditos {$credits}");

            return response()->json(['message' => 'Payment processed successfully']);
        }

        return response()->json(['message' => 'Event ignored'], 200);
    }

    /**
     * Valida a assinatura do webhook usando a chave secreta de IPN.
     */
    private function validateWebhookSignature(?string $payload, ?string $signature, ?string $ipnSecret): bool
    {
        if (!$payload || !$signature || !$ipnSecret) {
            return false;
        }

        // A documentação da NOWPayments especifica o uso de hmac com sha512.
        $expectedSignature = hash_hmac('sha512', $payload, $ipnSecret);

        // Use hash_equals para prevenir ataques de "timing attack".
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Adiciona créditos à conta do usuário no registro '#CASHPOINTS'.
     * Esta função é idêntica à do seu PayPalController para manter a consistência.
     */
    private function addCreditsToAccount(int $accountId, int $credits)
    {
        $existing = DB::connection('ragnarok')->table('acc_reg_num')
            ->where('account_id', $accountId)
            ->where('key', '#CASHPOINTS')
            ->first();

        if ($existing) {
            DB::connection('ragnarok')->table('acc_reg_num')
                ->where('id', $existing->id)
                ->increment('value', $credits);
        } else {
            DB::connection('ragnarok')->table('acc_reg_num')->insert([
                'account_id' => $accountId,
                'key' => '#CASHPOINTS',
                'value' => $credits,
            ]);
        }
    }
}