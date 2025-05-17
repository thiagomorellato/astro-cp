<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    /**
     * Recebe o webhook do PayPal e processa o evento.
     */
    public function handle(Request $request)
    {
        $headers = [
            'paypal-transmission-id' => $request->header('Paypal-Transmission-Id'),
            'paypal-transmission-time' => $request->header('Paypal-Transmission-Time'),
            'paypal-cert-url' => $request->header('Paypal-Cert-Url'),
            'paypal-auth-algo' => $request->header('Paypal-Auth-Algo'),
            'paypal-transmission-sig' => $request->header('Paypal-Transmission-Sig'),
            'webhook-id' => config('services.paypal.webhook_id'), // Você deve configurar o ID do webhook do PayPal no .env/config
        ];

        $body = $request->getContent();

        // Validar webhook com a API do PayPal para garantir que é legítimo
        if (!$this->validateWebhook($headers, $body)) {
            Log::warning('Invalid PayPal webhook signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = json_decode($body, true);

        if (!$payload) {
            Log::warning('Empty or invalid PayPal webhook payload');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventType = $payload['event_type'] ?? null;

        // Trate apenas eventos de pagamento concluído
        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $payload['resource'] ?? [];

            $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
            $amount = $resource['amount']['value'] ?? null;
            $currency = $resource['amount']['currency_code'] ?? null;

            if (!$paypalOrderId || !$amount || !$currency) {
                Log::warning('Incomplete payment data in PayPal webhook');
                return response()->json(['error' => 'Incomplete payment data'], 400);
            }

            // Aqui você pode pegar o user_id / account_id do banco, relacionando pelo paypalOrderId,
            // se estiver salvando os pedidos criados na sua tabela donations_pp (ex: status pending).
            // Ou você pode usar a metadata/custom fields no pedido para guardar o user_id.

            // Exemplo: buscar doação pendente com esse paypalOrderId
            $donation = DB::connection('ragnarok')->table('donations_pp')
                ->where('paypal_order_id', $paypalOrderId)
                ->first();

            if (!$donation) {
                Log::warning("Donation record not found for PayPal Order ID: {$paypalOrderId}");
                return response()->json(['error' => 'Donation record not found'], 404);
            }

            if ($donation->status === 'success') {
                // Já processado, evita duplicação
                return response()->json(['message' => 'Donation already processed']);
            }

            $accountId = $donation->account_id;

            // Calcular créditos, baseado no valor recebido e sua taxa
            $credits = intval(floatval($amount) * config('services.paypal.conversion_rate', 1000));

            // Atualizar créditos
            $existing = DB::connection('ragnarok')->table('acc_reg_num')
                ->where('account_id', $accountId)
                ->where('key', '#CASHPOINTS')
                ->first();

            if ($existing) {
                DB::connection('ragnarok')->table('acc_reg_num')
                    ->where('account_id', $accountId)
                    ->where('key', '#CASHPOINTS')
                    ->update([
                        'value' => $existing->value + $credits,
                    ]);
            } else {
                DB::connection('ragnarok')->table('acc_reg_num')->insert([
                    'account_id' => $accountId,
                    'key' => '#CASHPOINTS',
                    'value' => $credits,
                ]);
            }

            // Atualiza o status da doação para sucesso e valores atualizados
            DB::connection('ragnarok')->table('donations_pp')
                ->where('id', $donation->id)
                ->update([
                    'amount_usd' => $amount,
                    'credits' => $credits,
                    'status' => 'success',
                    'updated_at' => now(),
                ]);

            Log::info("PayPal donation processed: Order ID {$paypalOrderId}, Account ID {$accountId}, Credits {$credits}");

            return response()->json(['message' => 'Payment processed successfully']);
        }

        // Ignorar outros eventos, mas responder 200 OK para PayPal não repetir
        return response()->json(['message' => 'Event ignored']);
    }

    /**
     * Valida o webhook usando a API do PayPal.
     * 
     * @param array $headers
     * @param string $body
     * @return bool
     */
    private function validateWebhook(array $headers, string $body): bool
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post(config('services.paypal.base_url') . '/v1/notifications/verify-webhook-signature', [
                'auth_algo' => $headers['paypal-auth-algo'],
                'cert_url' => $headers['paypal-cert-url'],
                'transmission_id' => $headers['paypal-transmission-id'],
                'transmission_sig' => $headers['paypal-transmission-sig'],
                'transmission_time' => $headers['paypal-transmission-time'],
                'webhook_id' => $headers['webhook-id'],
                'webhook_event' => json_decode($body, true),
            ]);

        if ($response->successful()) {
            return $response->json()['verification_status'] === 'SUCCESS';
        }

        Log::error('PayPal webhook validation failed: ' . $response->body());

        return false;
    }

    /**
     * Get PayPal OAuth2 Access Token
     */
    private function getAccessToken()
    {
        $response = Http::asForm()->withBasicAuth(
            config('services.paypal.client_id'),
            config('services.paypal.secret')
        )->post(config('services.paypal.base_url') . '/v1/oauth2/token', [
            'grant_type' => 'client_credentials',
        ]);

        if ($response->failed()) {
            Log::error('Failed to authenticate with PayPal: ' . $response->body());
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }
}
