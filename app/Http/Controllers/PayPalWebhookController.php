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
        // Loga o payload completo
        Log::info('Received PayPal webhook:', ['payload' => $request->all()]);

        $headers = [
            'paypal-transmission-id' => $request->header('paypal-transmission-id'),
            'paypal-transmission-time' => $request->header('paypal-transmission-time'),
            'paypal-cert-url' => $request->header('paypal-cert-url'),
            'paypal-auth-algo' => $request->header('paypal-auth-algo'),
            'paypal-transmission-sig' => $request->header('paypal-transmission-sig'),
            'webhook-id' => config('services.paypal.webhook_id'),
        ];

        $body = $request->getContent();

        if (!$this->validateWebhook($headers, $body)) {
            Log::warning('Invalid PayPal webhook signature.');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = json_decode($body, true);

        if (!$payload) {
            Log::warning('Invalid JSON payload from PayPal.');
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventType = $payload['event_type'] ?? null;

        if ($eventType === 'PAYMENT.CAPTURE.COMPLETED') {
            $resource = $payload['resource'] ?? [];

            $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;
            $amount = $resource['amount']['value'] ?? null;
            $currency = $resource['amount']['currency_code'] ?? null;

            if (!$paypalOrderId || !$amount || !$currency) {
                Log::warning('Missing order_id, amount or currency in webhook.');
                return response()->json(['error' => 'Incomplete payment data'], 400);
            }

            $donation = DB::connection('ragnarok')->table('donations_pp')
                ->where('paypal_order_id', $paypalOrderId)
                ->first();

            if (!$donation) {
                Log::warning("Donation not found. PayPal Order ID: {$paypalOrderId}");
                return response()->json(['error' => 'Donation record not found'], 404);
            }

            if ($donation->status === 'success') {
                return response()->json(['message' => 'Donation already processed'], 200);
            }

            $accountId = $donation->account_id;

            if (!$accountId) {
                Log::error("Invalid account ID for donation ID {$donation->id}");
                return response()->json(['error' => 'Invalid account ID'], 400);
            }

            $conversionRate = config('services.paypal.conversion_rate', 1000);
            $credits = (int) floor(floatval($amount) * $conversionRate);

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

            DB::connection('ragnarok')->table('donations_pp')
                ->where('id', $donation->id)
                ->update([
                    'amount_usd' => $amount,
                    'credits' => $credits,
                    'status' => 'success',
                    'updated_at' => now(),
                ]);

            Log::info("Donation processed: PayPal Order {$paypalOrderId}, Account {$accountId}, USD {$amount}, Credits {$credits}");

            return response()->json(['message' => 'Payment processed successfully']);
        }

        return response()->json(['message' => 'Event ignored'], 200);
    }

    /**
     * Valida o webhook com a API do PayPal.
     */
    private function validateWebhook(array $headers, string $body): bool
{
    $accessToken = $this->getAccessToken();

    if (!$accessToken) {
        Log::error('Failed to get PayPal access token for webhook validation.');
        return false;
    }

    $payload = [
        'auth_algo' => $headers['paypal-auth-algo'],
        'cert_url' => $headers['paypal-cert-url'],
        'transmission_id' => $headers['paypal-transmission-id'],
        'transmission_sig' => $headers['paypal-transmission-sig'],
        'transmission_time' => $headers['paypal-transmission-time'],
        'webhook_id' => $headers['webhook-id'],
        'webhook_event' => json_decode($body, true),
    ];

    Log::debug('Sending webhook validation payload to PayPal:', $payload);

    $response = Http::withToken($accessToken)->post(
        config('services.paypal.base_url') . '/v1/notifications/verify-webhook-signature',
        $payload
    );

    Log::debug('PayPal validation response', [
        'status' => $response->status(),
        'body' => $response->body(),
    ]);

    if ($response->successful()) {
        return $response->json()['verification_status'] === 'SUCCESS';
    }

    error_log('Webhook verification failed: ' . $response->body());

    return false;
}

    /**
     * Obtém o token OAuth do PayPal.
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
            Log::error('PayPal authentication failed: ' . $response->body());
            return null;
        }

        return $response->json()['access_token'] ?? null;
    }
}
