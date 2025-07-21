<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AsaasController extends Controller
{
    // Taxa de conversão: R$5,20 para 1000 SC
    private const RATE_BRL_PER_1000_SC = 5.20;

    public function createDonation(Request $request)
    {
        // 1. Verifica sessão
        $userid = session('astrocp_user.userid');
        if (!$userid) {
            return redirect('/donations/payment-failed')->with('error', 'User session expired. Please log in again.');
        }

        // 2. Busca dados do usuário
        $user = DB::connection('ragnarok')->table('login')->where('userid', $userid)->first();
        if (!$user || !isset($user->account_id)) {
            return redirect('/donations/payment-failed')->with('error', 'Unable to find account information.');
        }
        $accountId = $user->account_id;
        $email = $user->email ?? 'no-reply@astrocp.fake';

        // 3. Captura valor e CPF
        $amount = number_format((float) $request->input('amount', 5.20), 2, '.', '');
        if ($amount < 1) {
            return back()->with('error', 'Invalid amount.');
        }

        $cpf = preg_replace('/\D/', '', $request->input('cpf'));
        if (strlen($cpf) !== 11) {
            return back()->with('error', 'Invalid CPF.');
        }

        // 4. Define dados da API
        $asaasApiKey = config('services.asaas.api_key');
        $asaasUrl = rtrim(config('services.asaas.base_url'), '/') . '/';

        // 5. Obtém ou cria customer_id
        $customerId = $this->getOrCreateAsaasCustomer($accountId, $cpf, $email, $userid, $asaasApiKey, $asaasUrl);
        if (!$customerId) {
            return redirect('/donations/payment-failed')->with('error', 'Failed to create or retrieve customer on Asaas.');
        }

        // 6. Cria cobrança
        $response = Http::withHeaders([
            'access_token' => $asaasApiKey,
            'Content-Type' => 'application/json',
        ])->post("{$asaasUrl}payments", [
            'customer' => $customerId,
            'billingType' => 'UNDEFINED',
            'value' => $amount,
            'dueDate' => now()->addDays(1)->format('Y-m-d'),
            'description' => "Donation for account ID {$accountId}",
            'externalReference' => "astro-asaas-{$accountId}-" . time(),
            'notificationDisabled' => false,
        ]);

        if ($response->failed()) {
            Log::error('Asaas payment creation failed', ['account_id' => $accountId, 'response' => $response->body()]);
            return redirect('/donations/payment-failed')->with('error', 'Failed to create payment invoice with Asaas.');
        }

        $paymentData = $response->json();
        $paymentId = $paymentData['id'] ?? null;
        $paymentLink = $paymentData['invoiceUrl'] ?? $paymentData['paymentLink'] ?? null;

        if (!$paymentId || !$paymentLink) {
            Log::error('Invalid Asaas payment response', $paymentData);
            return redirect('/donations/payment-failed')->with('error', 'Invalid response from Asaas.');
        }

        // 7. Salva no banco
        DB::connection('ragnarok')->table('donations_as')->insert([
            'account_id' => $accountId,
            'amount_brl' => $amount,
            'status' => 'pending',
            'asaas_payment_id' => $paymentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Log::info("Asaas payment created", ['payment_id' => $paymentId, 'account_id' => $accountId]);

        return redirect()->away($paymentLink);
    }

    public function webhook(Request $request)
    {
        // Valida token webhook
        $signature = $request->header('asaas-signature');
        $secret = config('services.asaas.webhook_token'); // usa o token como chave HMAC

        $calculatedSignature = base64_encode(
            hash_hmac('sha256', $request->getContent(), $secret, true)
        );

        if ($signature !== $calculatedSignature) {
            Log::warning('Invalid Asaas webhook signature.', [
                'received' => $signature,
                'calculated' => $calculatedSignature,
                'body' => $request->getContent(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->all();
        Log::info('Asaas webhook received', $data);

        if (!isset($data['event']) || !isset($data['payment']['id'])) {
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $event = $data['event'];
        $paymentId = $data['payment']['id'];

        $status = match ($event) {
            'PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED' => 'success',
            'PAYMENT_DELETED' => 'cancelled',
            default => null
        };

        if (!$status) {
            return response()->json(['message' => 'Event ignored'], 200);
        }

        // Busca doação no banco
        $donation = DB::connection('ragnarok')->table('donations_as')
            ->where('asaas_payment_id', $paymentId)
            ->first();

        if (!$donation) {
            Log::warning("Donation not found for Asaas payment ID: {$paymentId}");
            return response()->json(['error' => 'Donation not found'], 404);
        }

        if ($donation->status === 'success' && $status === 'success') {
            Log::info("Donation {$donation->id} already processed as success.");
            return response()->json(['message' => 'Event already processed'], 200);
        }

        // Se status é sucesso, adiciona créditos
        if ($status === 'success') {
            $accountId = $donation->account_id;
            $amount = $donation->amount_brl;

            // Calcula créditos: R$5,20 = 1000 SC
            $credits = (int) floor(($amount / self::RATE_BRL_PER_1000_SC) * 1000);

            $this->addCreditsToAccount($accountId, $credits);

            // Atualiza doação para sucesso + créditos
            DB::connection('ragnarok')->table('donations_as')
                ->where('id', $donation->id)
                ->update([
                    'status' => 'success',
                    'credits' => $credits,
                    'updated_at' => now(),
                ]);

            Log::info("Credits added to account {$accountId}: {$credits}");
        } else {
            // Atualiza status para cancelado ou outros
            DB::connection('ragnarok')->table('donations_as')
                ->where('id', $donation->id)
                ->update([
                    'status' => $status,
                    'updated_at' => now(),
                ]);
        }

        return response()->json(['message' => 'Webhook processed']);
    }

    private function getOrCreateAsaasCustomer($accountId, $cpf, $email, $username, $asaasApiKey, $asaasUrl): ?string
    {
        $existing = DB::connection('ragnarok')->table('asaas_customers')->where('account_id', $accountId)->first();
        if ($existing && isset($existing->asaas_customer_id)) {
            return $existing->asaas_customer_id;
        }

        $response = Http::withHeaders([
            'access_token' => $asaasApiKey,
            'Content-Type' => 'application/json',
        ])->post("{$asaasUrl}customers", [
            'name' => $username,
            'email' => $email,
            'cpfCnpj' => $cpf,
            'notificationDisabled' => true,
        ]);

        if ($response->failed()) {
            Log::error('Failed to create Asaas customer', ['account_id' => $accountId, 'response' => $response->body()]);
            return null;
        }

        $customerId = $response->json()['id'] ?? null;

        if ($customerId) {
            DB::connection('ragnarok')->table('asaas_customers')->insert([
                'account_id' => $accountId,
                'asaas_customer_id' => $customerId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $customerId;
    }

    private function addCreditsToAccount(int $accountId, int $credits)
    {
        $existing = DB::connection('ragnarok')->table('acc_reg_num')
            ->where('account_id', $accountId)
            ->where('key', '#CASHPOINTS')
            ->first();

        if ($existing) {
            DB::connection('ragnarok')->table('acc_reg_num')
                ->where('account_id', $accountId)
                ->where('key', '#CASHPOINTS')
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
